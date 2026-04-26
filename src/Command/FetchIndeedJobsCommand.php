<?php

namespace App\Command;

use App\Entity\JobOffer;
use App\Repository\JobOfferRepository;
use App\Service\IndeedJobScraper;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:jobs:fetch-indeed',
    description: 'Fetch jobs from Indeed via RapidAPI and store them.',
)]
final class FetchIndeedJobsCommand extends Command
{
    public function __construct(
        private readonly IndeedJobScraper $scraper,
        private readonly JobOfferRepository $jobOfferRepository,
        private readonly EntityManagerInterface $entityManager,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('query', null, InputOption::VALUE_REQUIRED, 'Search query', 'php developer')
            ->addOption('location', null, InputOption::VALUE_REQUIRED, 'Location to search in', 'France')
            ->addOption('limit', null, InputOption::VALUE_REQUIRED, 'Max number of jobs to fetch', '30');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $query = (string) $input->getOption('query');
        $location = $input->getOption('location');
        $location = is_string($location) && $location !== '' ? $location : 'France';
        $limit = max(1, (int) $input->getOption('limit'));

        $io->note(sprintf('Fetching Indeed jobs for query "%s" in "%s"...', $query, $location));

        try {
            $jobs = $this->scraper->fetchJobs($query, $location, $limit);
        } catch (\Throwable $exception) {
            $io->error('Could not fetch Indeed jobs: '.$exception->getMessage());

            return Command::FAILURE;
        }

        if ($jobs === []) {
            $io->warning('No jobs found. Make sure your RAPIDAPI_KEY is configured in .env');
            return Command::SUCCESS;
        }

        $inserted = 0;
        $updated = 0;

        foreach ($jobs as $payload) {
            $jobOffer = $this->jobOfferRepository->findOneBy([
                'source' => 'indeed',
                'externalId' => $payload['external_id'],
            ]);

            if (!$jobOffer instanceof JobOffer) {
                $jobOffer = new JobOffer();
                $jobOffer
                    ->setSource('indeed')
                    ->setExternalId($payload['external_id']);
                $this->entityManager->persist($jobOffer);
                ++$inserted;
            } else {
                ++$updated;
            }

            $jobOffer
                ->setTitle($payload['title'])
                ->setCompany($payload['company'])
                ->setLocation($payload['location'])
                ->setUrl($payload['url'])
                ->setPublishedAt($payload['published_at'])
                ->touch();
        }

        $this->entityManager->flush();

        $io->success(sprintf(
            'Indeed sync done. Fetched: %d | inserted: %d | updated: %d',
            count($jobs),
            $inserted,
            $updated
        ));

        return Command::SUCCESS;
    }
}
