<?php

namespace App\Controller;

use App\Entity\JobApplication;
use App\Entity\JobOffer;
use App\Entity\User;
use App\Repository\JobApplicationRepository;
use App\Repository\JobOfferRepository;
use App\Service\WttjJobScraper;
use App\Service\JSearchScraper;
use App\Service\IndeedJobScraper;
use App\Service\FranceTravailScraper;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

final class JobController extends AbstractController
{
    #[Route('/jobs', name: 'app_jobs')]
    #[IsGranted('ROLE_USER')]
    public function index(
        Request $request,
        JobOfferRepository $jobOfferRepository,
        JobApplicationRepository $jobApplicationRepository,
        WttjJobScraper $wttjScraper,
        FranceTravailScraper $franceTravailScraper,
        JSearchScraper $jSearchScraper,
        IndeedJobScraper $indeedScraper,
        EntityManagerInterface $entityManager,
    ): Response {
        $job = trim((string) $request->query->get('job', ''));
        $city = trim((string) $request->query->get('city', ''));
        $dateFilterRaw = trim((string) $request->query->get('date', ''));
        $publishedAfter = $this->parsePublishedAfter($dateFilterRaw);

        // Si ville ET métier sont demandés, on vérifie si on a des résultats
        // Si non, on déclenche automatiquement un fetch depuis les sources actives
        if ($city !== '' && $job !== '') {
            $existingJobs = $jobOfferRepository->findLatestFiltered($job, $city, null, 5);
            
            if (count($existingJobs) < 5) {
                // Fetch from WTTJ (avec gestion d'erreur)
                try {
                    $this->fetchAndSaveJobs(
                        $wttjScraper->fetchJobs($job, $city, 15),
                        'wttj',
                        $jobOfferRepository,
                        $entityManager
                    );
                } catch (\Throwable $e) {
                    // Log l'erreur mais continue avec les autres sources
                    error_log('WTTJ fetch failed for ' . $city . ': ' . $e->getMessage());
                }

                // Fetch from France Travail (avec gestion d'erreur)
                try {
                    $this->fetchAndSaveJobs(
                        $franceTravailScraper->fetchJobs($job, $city, 15),
                        'francetravail',
                        $jobOfferRepository,
                        $entityManager
                    );
                } catch (\Throwable $e) {
                    error_log('France Travail fetch failed for ' . $city . ': ' . $e->getMessage());
                }

                // Note: JSearch et Indeed désactivés pour éviter les timeouts
                // (pas de clés API configurées actuellement)

                $entityManager->flush();
            }
        }

        $jobs = $jobOfferRepository->findLatestFiltered(
            $job !== '' ? $job : null,
            $city !== '' ? $city : null,
            $publishedAfter,
            100
        );
        $jobIds = array_values(array_filter(array_map(static fn (JobOffer $job): ?int => $job->getId(), $jobs)));

        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException();
        }

        $applicationsByJobId = $jobApplicationRepository->mapByJobIdsForUser($user, $jobIds);

        return $this->render('jobs/index.html.twig', [
            'jobs' => $jobs,
            'applicationsByJobId' => $applicationsByJobId,
            'selectedJob' => $job,
            'selectedCity' => $city,
            'selectedDate' => $dateFilterRaw,
            'statuses' => JobApplication::statuses(),
        ]);
    }

    #[Route('/jobs/{id}/apply', name: 'app_jobs_apply', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function apply(
        Request $request,
        JobOffer $jobOffer,
        JobApplicationRepository $jobApplicationRepository,
        EntityManagerInterface $entityManager,
    ): RedirectResponse {
        if (!$this->isCsrfTokenValid('apply_job_'.$jobOffer->getId(), (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Invalid CSRF token.');
        }

        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException();
        }

        $application = $jobApplicationRepository->findOneBy(['user' => $user, 'jobOffer' => $jobOffer]);
        if (!$application instanceof JobApplication) {
            $application = (new JobApplication())
                ->setUser($user)
                ->setJobOffer($jobOffer);
            $entityManager->persist($application);
        }

        $application
            ->setStatus(JobApplication::STATUS_APPLIED)
            ->setAppliedAt(new \DateTimeImmutable());
        $entityManager->flush();

        return $this->redirectToRoute('app_jobs', $request->query->all());
    }

    #[Route('/jobs/{id}/status', name: 'app_jobs_status', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function updateStatus(
        Request $request,
        JobOffer $jobOffer,
        JobApplicationRepository $jobApplicationRepository,
        EntityManagerInterface $entityManager,
    ): RedirectResponse {
        if (!$this->isCsrfTokenValid('status_job_'.$jobOffer->getId(), (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Invalid CSRF token.');
        }

        $status = (string) $request->request->get('status', JobApplication::STATUS_TO_APPLY);

        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException();
        }

        $application = $jobApplicationRepository->findOneBy(['user' => $user, 'jobOffer' => $jobOffer]);
        if (!$application instanceof JobApplication) {
            $application = (new JobApplication())
                ->setUser($user)
                ->setJobOffer($jobOffer);
            $entityManager->persist($application);
        }

        $application->setStatus($status);
        if ($application->getStatus() === JobApplication::STATUS_APPLIED && null === $application->getAppliedAt()) {
            $application->setAppliedAt(new \DateTimeImmutable());
        }
        if ($application->getStatus() !== JobApplication::STATUS_APPLIED) {
            $application->setAppliedAt(null);
        }

        $entityManager->flush();

        return $this->redirectToRoute('app_jobs', $request->query->all());
    }

    /**
     * @param list<array{external_id: string, title: string, company: string, location: ?string, url: string, published_at: ?\DateTimeImmutable}> $jobs
     */
    private function fetchAndSaveJobs(
        array $jobs,
        string $source,
        JobOfferRepository $jobOfferRepository,
        EntityManagerInterface $entityManager
    ): void {
        try {
            foreach ($jobs as $payload) {
                $jobOffer = $jobOfferRepository->findOneBy([
                    'source' => $source,
                    'externalId' => $payload['external_id'],
                ]);

                if (!$jobOffer instanceof JobOffer) {
                    $jobOffer = new JobOffer();
                    $jobOffer
                        ->setSource($source)
                        ->setExternalId($payload['external_id']);
                    $entityManager->persist($jobOffer);
                }

                $jobOffer
                    ->setTitle($payload['title'])
                    ->setCompany($payload['company'])
                    ->setLocation($payload['location'])
                    ->setUrl($payload['url'])
                    ->setPublishedAt($payload['published_at'])
                    ->touch();
            }
        } catch (\Throwable $e) {
            // En cas d'erreur, on continue silencieusement
        }
    }

    private function parsePublishedAfter(string $raw): ?\DateTimeImmutable
    {
        if ($raw === '') {
            return null;
        }

        try {
            return new \DateTimeImmutable($raw.' 00:00:00');
        } catch (\Exception) {
            return null;
        }
    }
}
