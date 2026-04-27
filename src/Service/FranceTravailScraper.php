<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

final class FranceTravailScraper
{
    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly string $franceTravailClientId = '',
        private readonly string $franceTravailClientSecret = '',
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
        if ($this->franceTravailClientId === '' || $this->franceTravailClientSecret === '') {
            return [];
        }

        // 1. Obtenir le token d'accès
        $accessToken = $this->getAccessToken();
        if ($accessToken === null) {
            return [];
        }

        // 2. Rechercher des offres d'emploi
        return $this->searchJobs($accessToken, $query, $location, $limit);
    }

    private function getAccessToken(): ?string
    {
        try {
            $response = $this->httpClient->request('POST', 'https://entreprise.francetravail.fr/connexion/oauth2/access_token', [
                'query' => [
                    'realm' => '/partenaire',
                ],
                'headers' => [
                    'Content-Type' => 'application/x-www-form-urlencoded',
                ],
                'body' => http_build_query([
                    'grant_type' => 'client_credentials',
                    'client_id' => $this->franceTravailClientId,
                    'client_secret' => $this->franceTravailClientSecret,
                    'scope' => 'api_offresdemploiv2 o2dsoffre',
                ]),
                'timeout' => 8,
            ]);

            if (200 !== $response->getStatusCode()) {
                return null;
            }

            $data = $response->toArray(false);

            return $data['access_token'] ?? null;
        } catch (\Throwable $e) {
            error_log('France Travail OAuth error: ' . $e->getMessage());
            return null;
        }
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
    private function searchJobs(string $accessToken, string $query, ?string $location, int $limit): array
    {
        try {
            $params = [
                'motsCles' => $query,
                'range' => sprintf('0-%d', min($limit - 1, 149)), // Max 150 résultats par requête
            ];

            // Ajouter la localisation dans les mots-clés au lieu du paramètre commune
            if ($location !== null && $location !== '') {
                $params['motsCles'] .= ' ' . $location;
            }

            $response = $this->httpClient->request('GET', 'https://api.francetravail.io/partenaire/offresdemploi/v2/offres/search', [
                'query' => $params,
                'headers' => [
                    'Authorization' => 'Bearer ' . $accessToken,
                    'Accept' => 'application/json',
                ],
                'timeout' => 8,
            ]);

            $statusCode = $response->getStatusCode();
            if (!in_array($statusCode, [200, 206], true)) {
                return [];
            }

            $data = $response->toArray(false);
            $results = $data['resultats'] ?? [];

            return array_map(function (array $offer): array {
                $publishedAt = null;
                if (isset($offer['dateCreation']) && is_string($offer['dateCreation'])) {
                    try {
                        $publishedAt = new \DateTimeImmutable($offer['dateCreation']);
                    } catch (\Exception) {
                        // Ignore invalid dates
                    }
                }

                // Construire l'adresse
                $location = null;
                if (isset($offer['lieuTravail']['libelle'])) {
                    $location = $offer['lieuTravail']['libelle'];
                }

                return [
                    'external_id' => (string) $offer['id'],
                    'title' => (string) ($offer['intitule'] ?? 'Unknown'),
                    'company' => (string) ($offer['entreprise']['nom'] ?? 'Entreprise confidentielle'),
                    'location' => $location,
                    'url' => (string) ($offer['origineOffre']['urlOrigine'] ?? 'https://candidat.francetravail.fr/offres/recherche'),
                    'published_at' => $publishedAt,
                ];
            }, $results);
        } catch (\Throwable) {
            return [];
        }
    }
}
