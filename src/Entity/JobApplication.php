<?php

namespace App\Entity;

use App\Repository\JobApplicationRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: JobApplicationRepository::class)]
#[ORM\Table(name: 'job_application')]
#[ORM\UniqueConstraint(name: 'uniq_job_application_user_job', columns: ['user_id', 'job_offer_id'])]
class JobApplication
{
    public const STATUS_TO_APPLY = 'to_apply';
    public const STATUS_APPLIED = 'applied';
    public const STATUS_HR_INTERVIEW = 'hr_interview';
    public const STATUS_TECH_INTERVIEW = 'tech_interview';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_NOT_TARGET = 'not_target';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?User $user = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?JobOffer $jobOffer = null;

    #[ORM\Column(length: 20)]
    private string $status = self::STATUS_TO_APPLY;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $appliedAt = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $updatedAt;

    public function __construct()
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

    public static function statuses(): array
    {
        return [
            self::STATUS_TO_APPLY,
            self::STATUS_APPLIED,
            self::STATUS_HR_INTERVIEW,
            self::STATUS_TECH_INTERVIEW,
            self::STATUS_REJECTED,
            self::STATUS_NOT_TARGET,
        ];
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(User $user): static
    {
        $this->user = $user;

        return $this;
    }

    public function getJobOffer(): ?JobOffer
    {
        return $this->jobOffer;
    }

    public function setJobOffer(JobOffer $jobOffer): static
    {
        $this->jobOffer = $jobOffer;

        return $this;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        if (!in_array($status, self::statuses(), true)) {
            $status = self::STATUS_TO_APPLY;
        }

        $this->status = $status;
        $this->updatedAt = new \DateTimeImmutable();

        return $this;
    }

    public function getAppliedAt(): ?\DateTimeImmutable
    {
        return $this->appliedAt;
    }

    public function setAppliedAt(?\DateTimeImmutable $appliedAt): static
    {
        $this->appliedAt = $appliedAt;
        $this->updatedAt = new \DateTimeImmutable();

        return $this;
    }
}
