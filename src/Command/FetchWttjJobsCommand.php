<?php

namespace App\Command;

use App\Entity\JobOffer;
use App\Repository\JobOfferRepository;
use App\Service\WttjJobScraper;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:jobs:fetch-wttj',
    description: 'Fetch jobs from Welcome to the Jungle and store them.',
)]
final class FetchWttjJobsCommand extends Command
{
    public function __construct(
        private readonly WttjJobScraper $scraper,
        private readonly JobOfferRepository $jobOfferRepository,
        private readonly EntityManagerInterface $entityManager,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('query', null, InputOption::VALUE_REQUIRED, 'Search query', 'developpeur php')
            ->addOption('city', null, InputOption::VALUE_REQUIRED, 'City to search in', null)
            ->addOption('limit', null, InputOption::VALUE_REQUIRED, 'Max number of jobs to fetch', '30');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $query = (string) $input->getOption('query');
        $city = $input->getOption('city');
        $city = is_string($city) && $city !== '' ? $city : null;
        $limit = max(1, (int) $input->getOption('limit'));

        $searchDescription = $city !== null 
            ? sprintf('Fetching WTTJ jobs for query "%s" in "%s"...', $query, $city)
            : sprintf('Fetching WTTJ jobs for query "%s"...', $query);
        $io->note($searchDescription);

        try {
            $jobs = $this->scraper->fetchJobs($query, $city, $limit);
        } catch (\Throwable $exception) {
            $io->error('Could not fetch WTTJ jobs: '.$exception->getMessage());

            return Command::FAILURE;
        }

        $inserted = 0;
        $updated = 0;

        foreach ($jobs as $payload) {
            $jobOffer = $this->jobOfferRepository->findOneBy([
                'source' => 'wttj',
                'externalId' => $payload['external_id'],
            ]);

            if (!$jobOffer instanceof JobOffer) {
                $jobOffer = new JobOffer();
                $jobOffer
                    ->setSource('wttj')
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
            'WTTJ sync done. Fetched: %d | inserted: %d | updated: %d',
            count($jobs),
            $inserted,
            $updated
        ));

        return Command::SUCCESS;
    }
}
