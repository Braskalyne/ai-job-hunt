<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

final class WttjJobScraper
{
    public function __construct(
        private readonly HttpClientInterface $httpClient,
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
    public function fetchJobs(string $query = 'developpeur php', ?string $city = null, int $limit = 30): array
    {
        $jobs = $this->fetchFromAlgolia($query, $city, $limit);
        if ($jobs !== []) {
            return $jobs;
        }

        $queryParams = ['query' => $query];
        if (null !== $city && '' !== $city) {
            $queryParams['query'] .= ' ' . $city;
        }

        $response = $this->httpClient->request('GET', 'https://www.welcometothejungle.com/fr/jobs', [
            'query' => $queryParams,
            'headers' => [
                'User-Agent' => 'AiJobHuntBot/1.0 (+local-dev)',
                'Accept' => 'text/html,application/xhtml+xml',
            ],
            'timeout' => 8,
        ]);

        $html = $response->getContent(false);

        return $this->extractFromJsonLd($html, $limit);
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
    private function extractFromJsonLd(string $html, int $limit): array
    {
        preg_match_all('/<script[^>]*type="application\/ld\+json"[^>]*>(.*?)<\/script>/si', $html, $matches);
        $scripts = $matches[1] ?? [];

        $jobs = [];

        foreach ($scripts as $scriptContent) {
            $decoded = json_decode(trim($scriptContent), true);

            if (!is_array($decoded)) {
                continue;
            }

            $items = [];
            if (($decoded['@type'] ?? null) === 'ItemList' && isset($decoded['itemListElement'])) {
                $items = $decoded['itemListElement'];
            } elseif (isset($decoded[0]) && is_array($decoded[0])) {
                foreach ($decoded as $entry) {
                    if (($entry['@type'] ?? null) === 'ItemList' && isset($entry['itemListElement'])) {
                        $items = array_merge($items, (array) $entry['itemListElement']);
                    }
                }
            }

            foreach ($items as $item) {
                $job = $item['item'] ?? null;
                if (!is_array($job)) {
                    continue;
                }

                $url = (string) ($job['url'] ?? '');
                $title = (string) ($job['title'] ?? $job['name'] ?? '');
                $company = (string) ($job['hiringOrganization']['name'] ?? 'Unknown company');

                if ($url === '' || $title === '') {
                    continue;
                }

                $jobs[] = [
                    'external_id' => sha1($url),
                    'title' => $title,
                    'company' => $company,
                    'location' => $this->extractLocation($job),
                    'url' => $url,
                    'published_at' => $this->parseDate($job['datePosted'] ?? null),
                ];
            }
        }

        $unique = [];
        foreach ($jobs as $job) {
            $unique[$job['external_id']] = $job;
        }

        return array_slice(array_values($unique), 0, $limit);
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
    private function fetchFromAlgolia(string $query, ?string $city, int $limit): array
    {
        $searchQuery = $query;
        if (null !== $city && '' !== $city) {
            $searchQuery .= ' ' . $city;
        }

        $response = $this->httpClient->request('POST', 'https://CSEKHVMS53-dsn.algolia.net/1/indexes/wttj_jobs_production_fr/query', [
            'json' => [
                'params' => sprintf('query=%s&hitsPerPage=%d', urlencode($searchQuery), $limit),
            ],
            'headers' => [
                'Accept' => 'application/json',
                'User-Agent' => 'AiJobHuntBot/1.0 (+local-dev)',
                'X-Algolia-API-Key' => '4bd8f6215d0cc52b26430765769e65a0',
                'X-Algolia-Application-Id' => 'CSEKHVMS53',
                'Referer' => 'https://www.welcometothejungle.com/',
                'Origin' => 'https://www.welcometothejungle.com',
            ],
            'timeout' => 8,
        ]);

        if (200 !== $response->getStatusCode()) {
            return [];
        }

        $data = $response->toArray(false);
        $hits = $data['hits'] ?? $data['results'] ?? [];
        if (!is_array($hits)) {
            return [];
        }

        $jobs = [];
        foreach ($hits as $hit) {
            if (!is_array($hit)) {
                continue;
            }

            $title = (string) ($hit['name'] ?? '');
            $company = (string) ($hit['organization']['name'] ?? 'Unknown company');
            $slug = (string) ($hit['slug'] ?? '');
            $reference = (string) ($hit['website']['reference'] ?? 'fr');
            $organizationReference = (string) ($hit['organization']['reference'] ?? '');
            $url = ($slug !== '' && $organizationReference !== '')
                ? sprintf('https://www.welcometothejungle.com/%s/companies/%s/jobs/%s', $reference, $organizationReference, $slug)
                : '';

            if ($title === '' || $url === '') {
                continue;
            }

            $externalId = (string) ($hit['objectID'] ?? sha1($url));
            $jobs[] = [
                'external_id' => $externalId,
                'title' => $title,
                'company' => $company,
                'location' => $this->extractAlgoliaLocation($hit),
                'url' => $url,
                'published_at' => $this->parseDate($hit['published_at'] ?? null),
            ];
        }

        return $jobs;
    }

    /**
     * @param array<string, mixed> $hit
     */
    private function extractAlgoliaLocation(array $hit): ?string
    {
        $offices = $hit['offices'] ?? null;
        if (!is_array($offices) || $offices === []) {
            return null;
        }

        $firstOffice = $offices[0] ?? null;
        if (!is_array($firstOffice)) {
            return null;
        }

        $city = $firstOffice['city'] ?? null;
        return is_string($city) && $city !== '' ? $city : null;
    }

    /**
     * @param array<string, mixed> $job
     */
    private function extractLocation(array $job): ?string
    {
        $location = $job['jobLocation']['address']['addressLocality'] ?? null;
        if (is_string($location) && $location !== '') {
            return $location;
        }

        $fallback = $job['jobLocation']['address']['addressRegion'] ?? null;
        if (is_string($fallback) && $fallback !== '') {
            return $fallback;
        }

        return null;
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
