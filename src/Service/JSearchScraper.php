<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

final class JSearchScraper
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
            'num_pages' => '1',
        ];

        if (null !== $location && '' !== $location) {
            $params['query'] .= ' in ' . $location;
        }

        try {
            $response = $this->httpClient->request('GET', 'https://jsearch.p.rapidapi.com/search', [
                'query' => $params,
                'headers' => [
                    'X-RapidAPI-Key' => $this->rapidApiKey,
                    'X-RapidAPI-Host' => 'jsearch.p.rapidapi.com',
                ],
                'timeout' => 30,
            ]);

            if (200 !== $response->getStatusCode()) {
                return [];
            }

            $data = $response->toArray(false);
            $jobs = $data['data'] ?? [];

            if (!is_array($jobs)) {
                return [];
            }

            $results = [];
            foreach ($jobs as $job) {
                if (!is_array($job)) {
                    continue;
                }

                $title = (string) ($job['job_title'] ?? '');
                $company = (string) ($job['employer_name'] ?? 'Unknown');
                $url = (string) ($job['job_apply_link'] ?? $job['job_google_link'] ?? '');
                $jobLocation = (string) ($job['job_city'] ?? '');
                if ($jobLocation === '' && isset($job['job_country'])) {
                    $jobLocation = (string) $job['job_country'];
                }

                if ($title === '' || $url === '') {
                    continue;
                }

                $externalId = (string) ($job['job_id'] ?? sha1($url));

                $results[] = [
                    'external_id' => $externalId,
                    'title' => $title,
                    'company' => $company,
                    'location' => $jobLocation !== '' ? $jobLocation : null,
                    'url' => $url,
                    'published_at' => $this->parseDate($job['job_posted_at_datetime_utc'] ?? null),
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
