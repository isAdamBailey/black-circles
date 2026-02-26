<?php

namespace App\Services;

use App\Models\DiscogsCollectionItem;
use App\Models\DiscogsRelease;
use App\Models\Genre;
use App\Models\Setting;
use App\Models\Style;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class DiscogsService
{
    protected string $baseUrl = 'https://api.discogs.com';

    protected string $userAgent = 'BlackCirclesApp/1.0 +https://github.com/isAdamBailey/black-circles';

    protected ?\Closure $progressCallback = null;

    public function setProgressCallback(?\Closure $callback): void
    {
        $this->progressCallback = $callback;
    }

    protected function headers(): array
    {
        $token = config('services.discogs.token');
        $headers = ['User-Agent' => $this->userAgent];
        if ($token) {
            $headers['Authorization'] = "Discogs token={$token}";
        }

        return $headers;
    }

    protected function requestWithRetry(callable $request, string $logMessage, array $logContext = [], int $maxRetries = 5): ?array
    {
        $attempt = 0;

        while (true) {
            try {
                $response = $request();

                if ($response->successful()) {
                    return $response->json();
                }

                if ($response->status() === 429 && $attempt < $maxRetries) {
                    $retryAfter = (int) ($response->header('Retry-After') ?? 60);
                    $wait = min($retryAfter, 120);
                    Log::warning('Discogs rate limited (429), waiting before retry', array_merge($logContext, ['wait_seconds' => $wait, 'attempt' => $attempt + 1]));
                    sleep($wait);
                    $attempt++;

                    continue;
                }

                Log::error($logMessage, array_merge($logContext, ['status' => $response->status(), 'body' => $response->body()]));

                return null;
            } catch (\Exception $e) {
                Log::error('Discogs API exception', array_merge($logContext, ['message' => $e->getMessage()]));

                return null;
            }
        }
    }

    protected function artistName(array $artist): string
    {
        return trim($artist['anv'] ?? '') ?: ($artist['name'] ?? '');
    }

    public function getCollection(string $username, int $page = 1, int $perPage = 100): ?array
    {
        return $this->requestWithRetry(
            fn () => Http::withHeaders($this->headers())
                ->timeout(30)
                ->get("{$this->baseUrl}/users/{$username}/collection/folders/0/releases", [
                    'page' => $page,
                    'per_page' => $perPage,
                    'sort' => 'added',
                    'sort_order' => 'desc',
                ]),
            'Discogs collection fetch failed',
        );
    }

    public function getRelease(int $releaseId): ?array
    {
        $response = $this->requestWithRetry(
            fn () => Http::withHeaders($this->headers())->timeout(30)->get("{$this->baseUrl}/releases/{$releaseId}"),
            'Discogs release fetch failed',
            ['id' => $releaseId],
        );

        return $response;
    }

    /**
     * Fetch marketplace stats for a release. No authentication required.
     * Returns lowest listed price and number of copies for sale.
     *
     * Response: {"lowest_price": {"currency": "USD", "value": 9.99}, "num_for_sale": 3, ...}
     */
    public function getMarketplaceStats(int $releaseId, string $currency = 'USD'): ?array
    {
        return $this->requestWithRetry(
            fn () => Http::withHeaders($this->headers())
                ->timeout(30)
                ->get("{$this->baseUrl}/marketplace/stats/{$releaseId}", ['curr_abbr' => $currency]),
            'Discogs marketplace stats fetch failed',
            ['id' => $releaseId],
        );
    }

    public function syncCollection(string $username, bool $skipPrices = false): array
    {
        $synced = 0;
        $page = 1;
        $totalPages = 1;

        do {
            $data = $this->getCollection($username, $page, 100);
            if (! $data || empty($data['releases'])) {
                if ($page === 1 && (! $data || (isset($data['pagination']) && empty($data['releases'])))) {
                    $this->progressCallback && ($this->progressCallback)('No data from Discogs API (page 1). Check username and rate limits.');
                }
                break;
            }

            $totalPages = $data['pagination']['pages'] ?? 1;

            if ($this->progressCallback) {
                ($this->progressCallback)("Page {$page}/{$totalPages} — ".count($data['releases']).' releases');
            }

            foreach ($data['releases'] as $item) {
                $basicInfo = $item['basic_information'] ?? [];
                $releaseId = $basicInfo['id'] ?? null;

                if (! $releaseId) {
                    continue;
                }

                // Use ANV (Artist Name Variation) when available — it provides the
                // clean display name without Discogs disambiguation suffixes like "(2)".
                $artistName = collect($basicInfo['artists'] ?? [])
                    ->map(fn ($a) => $this->artistName($a))
                    ->implode(', ');

                $year = isset($basicInfo['year']) && (int) $basicInfo['year'] > 0 ? (int) $basicInfo['year'] : null;

                DiscogsRelease::updateOrCreate(
                    ['discogs_id' => $releaseId],
                    [
                        'title' => $basicInfo['title'] ?? 'Unknown',
                        'artist' => $artistName,
                        'label' => collect($basicInfo['labels'] ?? [])->first()['name'] ?? null,
                        'catalog_number' => collect($basicInfo['labels'] ?? [])->first()['catno'] ?? null,
                        'year' => $year,
                        'cover_image' => $basicInfo['cover_image'] ?? null,
                        'thumb' => $basicInfo['thumb'] ?? null,
                        'formats' => $basicInfo['formats'] ?? null,
                        'discogs_uri' => "https://www.discogs.com/release/{$releaseId}",
                    ]
                );

                // Sync genres and styles through pivot tables using firstOrCreate so that
                // shared genre names (e.g. "Rock") are stored only once.
                $release = DiscogsRelease::where('discogs_id', $releaseId)->first();

                $genreIds = collect($basicInfo['genres'] ?? [])
                    ->map(fn ($name) => Genre::firstOrCreate(['name' => $name])->id);
                $release->genres()->sync($genreIds);

                $styleIds = collect($basicInfo['styles'] ?? [])
                    ->map(fn ($name) => Style::firstOrCreate(['name' => $name])->id);
                $release->styles()->sync($styleIds);

                // The collection API returns folder_id (integer) and notes as an array
                // of field-value objects: [{"field_id": 1, "value": "my note"}]
                DiscogsCollectionItem::updateOrCreate(
                    ['instance_id' => $item['instance_id']],
                    [
                        'discogs_release_id' => $releaseId,
                        'folder_id' => $item['folder_id'] ?? null,
                        'rating' => $item['rating'] ?? null,
                        'notes' => $item['notes'] ?? null,
                        'date_added' => isset($item['date_added']) ? \Carbon\Carbon::parse($item['date_added']) : null,
                    ]
                );

                if (! $skipPrices) {
                    $stats = $this->getMarketplaceStats($releaseId);
                    $lowest = ($stats && isset($stats['lowest_price']['value'])) ? $stats['lowest_price']['value'] : null;
                    $release->update(['lowest_price' => $lowest]);
                }

                $synced++;
                usleep($skipPrices ? 500_000 : 1_500_000);
            }

            $page++;
            if ($page <= $totalPages) {
                sleep(2);
            }
        } while ($page <= $totalPages);

        Setting::set('collection_last_synced', now()->toISOString());

        return ['synced' => $synced, 'username' => $username];
    }

    public function enrichRelease(DiscogsRelease $release): DiscogsRelease
    {
        $data = null;
        $cacheFresh = $release->release_data_cached_at && $release->release_data_cached_at->diffInDays(now()) < 7;

        if (! $cacheFresh) {
            $data = $this->getRelease($release->discogs_id);
            if ($data) {
                $year = isset($data['year']) && $data['year'] > 0 ? (int) $data['year'] : null;
                $updateData = [
                    'tracklist' => $data['tracklist'] ?? null,
                    'videos' => $data['videos'] ?? null,
                    'notes' => isset($data['notes']) ? strip_tags($data['notes']) : null,
                    'discogs_uri' => $data['uri'] ?? $release->discogs_uri,
                    'year' => $year,
                    'release_data_cached_at' => now(),
                ];
                $images = $data['images'] ?? null;
                $updateData['images'] = $images;
                $primaryImage = collect($images ?? [])->firstWhere('type', 'primary')
                    ?? collect($images ?? [])->first();
                if ($primaryImage) {
                    $updateData['cover_image'] = $primaryImage['uri'] ?? $release->cover_image;
                    $updateData['thumb'] = $primaryImage['uri150'] ?? $release->thumb;
                }
                $release->update($updateData);
            }
        }

        $stats = $this->getMarketplaceStats($release->discogs_id);
        $lowest = ($stats && isset($stats['lowest_price']['value'])) ? $stats['lowest_price']['value'] : null;
        if ($lowest === null && $data !== null && isset($data['lowest_price']) && is_numeric($data['lowest_price'])) {
            $lowest = (float) $data['lowest_price'];
        }
        $release->update(['lowest_price' => $lowest]);

        return $release->fresh();
    }
}
