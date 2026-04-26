<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

final class IndeedJobScraper
{
    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly string $rapidApiKey = '',
    ) {
    }

    /**
     * @return list<array{
     *   external_id: string,
     *   title: string,
     *   company: string,
     *   location: ?string,
     *   url: string,
     *   published_at: ?\DateTimeImmutable
     * }>
     */
    public function fetchJobs(string $query = 'php developer', ?string $location = null, int $limit = 30): array
    {
        if ($this->rapidApiKey === '') {
            return [];
        }

        $params = [
            'query' => $query,
            'location' => $location ?? 'France',
            'page' => '1',
        ];

        try {
            $response = $this->httpClient->request('GET', 'https://indeed12.p.rapidapi.com/jobs/search', [
                'query' => $params,
                'headers' => [
                    'X-RapidAPI-Key' => $this->rapidApiKey,
                    'X-RapidAPI-Host' => 'indeed12.p.rapidapi.com',
                ],
                'timeout' => 30,
            ]);

            if (200 !== $response->getStatusCode()) {
                return [];
            }

            $data = $response->toArray(false);
            $jobs = $data['hits'] ?? $data['jobs'] ?? $data['data'] ?? [];

            if (!is_array($jobs)) {
                return [];
            }

            $results = [];
            foreach ($jobs as $job) {
                if (!is_array($job)) {
                    continue;
                }

                $title = (string) ($job['title'] ?? $job['jobTitle'] ?? '');
                $company = (string) ($job['company'] ?? $job['companyName'] ?? 'Unknown');
                $url = (string) ($job['url'] ?? $job['link'] ?? '');
                $jobLocation = (string) ($job['location'] ?? '');

                if ($title === '' || $url === '') {
                    continue;
                }

                // Ensure full Indeed URL
                if (!str_starts_with($url, 'http')) {
                    $url = 'https://www.indeed.com' . $url;
                }

                $externalId = (string) ($job['id'] ?? $job['jobkey'] ?? sha1($url));

                $results[] = [
                    'external_id' => $externalId,
                    'title' => $title,
                    'company' => $company,
                    'location' => $jobLocation !== '' ? $jobLocation : null,
                    'url' => $url,
                    'published_at' => $this->parseDate($job['date'] ?? $job['datePosted'] ?? null),
                ];

                if (count($results) >= $limit) {
                    break;
                }
            }

            return $results;
        } catch (\Throwable $e) {
            return [];
        }
    }

    private function parseDate(mixed $raw): ?\DateTimeImmutable
    {
        if (!is_string($raw) || $raw === '') {
            return null;
        }

        try {
            return new \DateTimeImmutable($raw);
        } catch (\Exception) {
            return null;
        }
    }
}
