<?php

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:jobs:fetch-all',
    description: 'Fetch jobs from all sources (WTTJ, France Travail, JSearch, Indeed).',
)]
final class FetchAllJobsCommand extends Command
{
    protected function configure(): void
    {
        $this
            ->addOption('query', null, InputOption::VALUE_REQUIRED, 'Search query', 'php developer')
            ->addOption('location', null, InputOption::VALUE_REQUIRED, 'Location/City to search in', null)
            ->addOption('limit', null, InputOption::VALUE_REQUIRED, 'Max number of jobs per source', '30');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $query = (string) $input->getOption('query');
        $location = $input->getOption('location');
        $location = is_string($location) && $location !== '' ? $location : null;
        $limit = max(1, (int) $input->getOption('limit'));

        $io->title('Fetching jobs from all sources');

        $totalInserted = 0;
        $totalUpdated = 0;
        $totalFetched = 0;

        // Fetch from WTTJ
        $io->section('1/4 - Welcome to the Jungle');
        $wttjCommand = $this->getApplication()?->find('app:jobs:fetch-wttj');
        if ($wttjCommand) {
            $wttjInput = new ArrayInput([
                '--query' => $query,
                '--city' => $location,
                '--limit' => (string) $limit,
            ]);
            $wttjCommand->run($wttjInput, $output);
        }

        // Fetch from France Travail (ex-Pôle Emploi)
        $io->section('2/4 - France Travail (ex-Pôle Emploi)');
        $ftCommand = $this->getApplication()?->find('app:jobs:fetch-francetravail');
        if ($ftCommand) {
            $ftInput = new ArrayInput([
                '--query' => $query,
                '--location' => $location,
                '--limit' => (string) $limit,
            ]);
            $ftCommand->run($ftInput, $output);
        }

        // Fetch from JSearch (LinkedIn, Indeed, Google Jobs aggregator)
        $io->section('3/4 - JSearch (LinkedIn + Indeed + Google)');
        $jsearchCommand = $this->getApplication()?->find('app:jobs:fetch-jsearch');
        if ($jsearchCommand) {
            $jsearchInput = new ArrayInput([
                '--query' => $query,
                '--location' => $location,
                '--limit' => (string) $limit,
            ]);
            $jsearchCommand->run($jsearchInput, $output);
        }

        // Fetch from Indeed
        $io->section('4/4 - Indeed');
        $indeedCommand = $this->getApplication()?->find('app:jobs:fetch-indeed');
        if ($indeedCommand) {
            $indeedInput = new ArrayInput([
                '--query' => $query,
                '--location' => $location ?? 'France',
                '--limit' => (string) $limit,
            ]);
            $indeedCommand->run($indeedInput, $output);
        }

        $io->success('All sources processed!');

        return Command::SUCCESS;
    }
}
