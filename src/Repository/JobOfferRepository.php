<?php

namespace App\Repository;

use App\Entity\JobOffer;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<JobOffer>
 */
class JobOfferRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, JobOffer::class);
    }

    /**
     * @return list<JobOffer>
     */
    public function findLatest(int $limit = 50): array
    {
        return $this->createQueryBuilder('job')
            ->orderBy('job.publishedAt', 'DESC')
            ->addOrderBy('job.updatedAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * @return list<JobOffer>
     */
    public function findLatestFiltered(?string $jobTitle, ?string $city, ?\DateTimeImmutable $publishedAfter, int $limit = 100): array
    {
        $qb = $this->createQueryBuilder('job')
            ->orderBy('job.publishedAt', 'DESC')
            ->addOrderBy('job.updatedAt', 'DESC')
            ->setMaxResults($limit);

        if (null !== $jobTitle && '' !== $jobTitle) {
            $qb->andWhere('LOWER(job.title) LIKE :jobTitle')
                ->setParameter('jobTitle', '%' . mb_strtolower($jobTitle) . '%');
        }

        if (null !== $city && '' !== $city) {
            $qb->andWhere('LOWER(job.location) LIKE :city')
                ->setParameter('city', '%' . mb_strtolower($city) . '%');
        }

        if (null !== $publishedAfter) {
            $qb->andWhere('job.publishedAt >= :publishedAfter')
                ->setParameter('publishedAfter', $publishedAfter);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * @return list<string>
     */
    public function findDistinctLocations(): array
    {
        $rows = $this->createQueryBuilder('job')
            ->select('DISTINCT job.location AS location')
            ->andWhere('job.location IS NOT NULL')
            ->orderBy('job.location', 'ASC')
            ->getQuery()
            ->getArrayResult();

        return array_values(array_filter(array_map(
            static fn (array $row): ?string => isset($row['location']) && is_string($row['location']) && $row['location'] !== '' ? $row['location'] : null,
            $rows
        )));
    }
}
