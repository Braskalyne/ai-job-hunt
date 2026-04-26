<?php

namespace App\Repository;

use App\Entity\JobApplication;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<JobApplication>
 */
class JobApplicationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, JobApplication::class);
    }

    /**
     * @param list<int> $jobIds
     * @return array<int, JobApplication>
     */
    public function mapByJobIdsForUser(User $user, array $jobIds): array
    {
        if ($jobIds === []) {
            return [];
        }

        $applications = $this->createQueryBuilder('application')
            ->andWhere('application.user = :user')
            ->andWhere('application.jobOffer IN (:jobIds)')
            ->setParameter('user', $user)
            ->setParameter('jobIds', $jobIds)
            ->getQuery()
            ->getResult();

        $map = [];
        foreach ($applications as $application) {
            $job = $application->getJobOffer();
            if (null !== $job && null !== $job->getId()) {
                $map[$job->getId()] = $application;
            }
        }

        return $map;
    }
}
