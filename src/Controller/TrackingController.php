<?php

namespace App\Controller;

use App\Entity\JobApplication;
use App\Entity\JobOffer;
use App\Entity\User;
use App\Repository\JobApplicationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

final class TrackingController extends AbstractController
{
    #[Route('/tracking', name: 'app_tracking')]
    #[IsGranted('ROLE_USER')]
    public function index(JobApplicationRepository $jobApplicationRepository): Response
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException();
        }

        // Colonnes Kanban (on exclut 'to_apply' car on veut voir seulement le process actif)
        $columns = [
            JobApplication::STATUS_APPLIED => 'Candidature Envoyée',
            JobApplication::STATUS_HR_INTERVIEW => 'Entretien RH Fait',
            JobApplication::STATUS_TECH_INTERVIEW => 'Entretien Technique Fait',
            JobApplication::STATUS_REJECTED => 'Refusé',
            JobApplication::STATUS_NOT_TARGET => 'Pas ma cible',
        ];

        $applicationsByStatus = [];
        foreach (array_keys($columns) as $status) {
            $applicationsByStatus[$status] = $jobApplicationRepository->findBy(
                ['user' => $user, 'status' => $status],
                ['updatedAt' => 'DESC']
            );
        }

        return $this->render('tracking/index.html.twig', [
            'columns' => $columns,
            'applicationsByStatus' => $applicationsByStatus,
        ]);
    }

    #[Route('/tracking/{id}/move', name: 'app_tracking_move', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function moveCard(
        Request $request,
        JobApplication $application,
        EntityManagerInterface $entityManager
    ): JsonResponse {
        $user = $this->getUser();
        if (!$user instanceof User || $application->getUser()?->getId() !== $user->getId()) {
            return new JsonResponse(['error' => 'Access denied'], 403);
        }

        $data = json_decode($request->getContent(), true);
        $newStatus = (string) ($data['status'] ?? '');

        if (!in_array($newStatus, JobApplication::statuses(), true)) {
            return new JsonResponse(['error' => 'Invalid status'], 400);
        }

        $application->setStatus($newStatus);
        if ($newStatus === JobApplication::STATUS_APPLIED && null === $application->getAppliedAt()) {
            $application->setAppliedAt(new \DateTimeImmutable());
        }
        if ($newStatus !== JobApplication::STATUS_APPLIED) {
            $application->setAppliedAt(null);
        }

        $entityManager->flush();

        return new JsonResponse(['success' => true]);
    }
}
