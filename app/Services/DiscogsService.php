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

    protected function headers(): array
    {
        $token = config('services.discogs.token');
        $headers = ['User-Agent' => $this->userAgent];
        if ($token) {
            $headers['Authorization'] = "Discogs token={$token}";
        }
        return $headers;
    }

    /**
     * Format an artist display name, preferring the ANV (Artist Name Variation)
     * when set. The ANV is Discogs' way of showing a clean display name without
     * disambiguation suffixes like "(2)" on artist names.
     */
    protected function artistName(array $artist): string
    {
        return trim($artist['anv'] ?? '') ?: ($artist['name'] ?? '');
    }

    public function getCollection(string $username, int $page = 1, int $perPage = 100): ?array
    {
        try {
            $response = Http::withHeaders($this->headers())
                ->get("{$this->baseUrl}/users/{$username}/collection/folders/0/releases", [
                    'page' => $page,
                    'per_page' => $perPage,
                    'sort' => 'added',
                    'sort_order' => 'desc',
                ]);

            if ($response->successful()) {
                return $response->json();
            }
            Log::error('Discogs collection fetch failed', ['status' => $response->status(), 'body' => $response->body()]);
        } catch (\Exception $e) {
            Log::error('Discogs API exception', ['message' => $e->getMessage()]);
        }
        return null;
    }

    public function getRelease(int $releaseId): ?array
    {
        try {
            $response = Http::withHeaders($this->headers())
                ->get("{$this->baseUrl}/releases/{$releaseId}");

            if ($response->successful()) {
                return $response->json();
            }
        } catch (\Exception $e) {
            Log::error('Discogs release fetch failed', ['id' => $releaseId, 'message' => $e->getMessage()]);
        }
        return null;
    }

    /**
     * Fetch marketplace stats for a release. No authentication required.
     * Returns lowest listed price and number of copies for sale.
     *
     * Response: {"lowest_price": {"currency": "USD", "value": 9.99}, "num_for_sale": 3, ...}
     */
    public function getMarketplaceStats(int $releaseId, string $currency = 'USD'): ?array
    {
        try {
            $response = Http::withHeaders($this->headers())
                ->get("{$this->baseUrl}/marketplace/stats/{$releaseId}", [
                    'curr_abbr' => $currency,
                ]);

            if ($response->successful()) {
                return $response->json();
            }
        } catch (\Exception $e) {
            Log::error('Discogs marketplace stats fetch failed', ['id' => $releaseId, 'message' => $e->getMessage()]);
        }
        return null;
    }

    /**
     * Fetch suggested prices per condition for a release. Requires an auth token.
     *
     * Response keys are condition names like "Very Good Plus (VG+)", "Near Mint (NM or M-)", etc.
     * Each value is: {"currency": "USD", "value": 12.00}
     */
    public function getPriceSuggestions(int $releaseId): ?array
    {
        try {
            $response = Http::withHeaders($this->headers())
                ->get("{$this->baseUrl}/marketplace/price_suggestions/{$releaseId}");

            if ($response->successful()) {
                return $response->json();
            }
        } catch (\Exception $e) {
            Log::error('Discogs price suggestion fetch failed', ['id' => $releaseId, 'message' => $e->getMessage()]);
        }
        return null;
    }

    public function syncCollection(string $username): array
    {
        $synced = 0;
        $page = 1;
        $totalPages = 1;

        do {
            $data = $this->getCollection($username, $page, 100);
            if (!$data || empty($data['releases'])) {
                break;
            }

            $totalPages = $data['pagination']['pages'] ?? 1;

            foreach ($data['releases'] as $item) {
                $basicInfo = $item['basic_information'] ?? [];
                $releaseId = $basicInfo['id'] ?? null;

                if (!$releaseId) continue;

                // Use ANV (Artist Name Variation) when available — it provides the
                // clean display name without Discogs disambiguation suffixes like "(2)".
                $artistName = collect($basicInfo['artists'] ?? [])
                    ->map(fn($a) => $this->artistName($a))
                    ->implode(', ');

                DiscogsRelease::updateOrCreate(
                    ['discogs_id' => $releaseId],
                    [
                        'title' => $basicInfo['title'] ?? 'Unknown',
                        'artist' => $artistName,
                        'label' => collect($basicInfo['labels'] ?? [])->first()['name'] ?? null,
                        'catalog_number' => collect($basicInfo['labels'] ?? [])->first()['catno'] ?? null,
                        'year' => $basicInfo['year'] ?? null,
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
                    ->map(fn($name) => Genre::firstOrCreate(['name' => $name])->id);
                $release->genres()->sync($genreIds);

                $styleIds = collect($basicInfo['styles'] ?? [])
                    ->map(fn($name) => Style::firstOrCreate(['name' => $name])->id);
                $release->styles()->sync($styleIds);

                // The collection API returns folder_id (integer) and notes as an array
                // of field-value objects: [{"field_id": 1, "value": "my note"}]
                DiscogsCollectionItem::updateOrCreate(
                    ['instance_id' => $item['instance_id']],
                    [
                        'discogs_release_id' => $releaseId,
                        'folder_id' => $item['folder_id'] ?? null,
                        'rating' => $item['rating'] ?? null,
                        'notes' => $item['notes'] ?: null,
                        'date_added' => isset($item['date_added']) ? \Carbon\Carbon::parse($item['date_added']) : null,
                    ]
                );

                $synced++;
            }

            $page++;
            if ($page <= $totalPages) {
                usleep(500000); // 0.5s delay between pages to respect rate limits
            }
        } while ($page <= $totalPages);

        Setting::set('discogs_username', $username);
        Setting::set('collection_last_synced', now()->toISOString());

        return ['synced' => $synced, 'username' => $username];
    }

    public function enrichRelease(DiscogsRelease $release): DiscogsRelease
    {
        // Only re-fetch if data is stale (older than 7 days) or missing
        if ($release->release_data_cached_at && $release->release_data_cached_at->diffInDays(now()) < 7) {
            return $release;
        }

        $data = $this->getRelease($release->discogs_id);
        if ($data) {
            $updateData = [
                // Tracklist: each track has position (string), type_ (track|heading|subtrack),
                // title (string), duration (string "M:SS" — already formatted, not seconds).
                'tracklist' => $data['tracklist'] ?? null,

                // Videos: each item has uri, title, description, duration (int seconds), embed (bool).
                // The embed flag is checked in the frontend before rendering iframes.
                'videos' => $data['videos'] ?? null,

                // Notes on the full release endpoint is a HTML string (unlike collection notes
                // which are an array). Strip tags to get plain text.
                'notes' => isset($data['notes']) ? strip_tags($data['notes']) : null,

                // Use the API-provided URI which includes the proper title slug,
                // e.g. "/Sex-Pistols-Never-Mind.../release/249504"
                'discogs_uri' => $data['uri'] ?? $release->discogs_uri,

                'release_data_cached_at' => now(),
            ];

            // Update cover image to the primary high-resolution version from the full release.
            // images[] is ordered with the primary image first (type = "primary").
            $primaryImage = collect($data['images'] ?? [])->firstWhere('type', 'primary')
                ?? collect($data['images'] ?? [])->first();
            if ($primaryImage) {
                $updateData['cover_image'] = $primaryImage['uri'] ?? $release->cover_image;
                $updateData['thumb'] = $primaryImage['uri150'] ?? $release->thumb;
            }

            $release->update($updateData);
        }

        // Fetch marketplace stats for lowest listed price (no auth required).
        $stats = $this->getMarketplaceStats($release->discogs_id);
        $priceUpdate = [];
        if ($stats) {
            $priceUpdate['lowest_price'] = $stats['lowest_price']['value'] ?? null;
        }

        // Fetch per-condition price suggestions for median/high estimates (requires auth token).
        // Map: median = VG+ (the standard collector reference condition),
        //      highest = Near Mint (the premium benchmark).
        if (config('services.discogs.token')) {
            $suggestions = $this->getPriceSuggestions($release->discogs_id);
            if ($suggestions) {
                $prices = collect($suggestions)
                    ->map(fn($p) => $p['value'] ?? null)
                    ->filter()
                    ->sort()
                    ->values();

                $priceUpdate['median_price'] = $prices->count() > 0 ? $prices->median() : null;
                $priceUpdate['highest_price'] = $prices->last() ?? null;
            }
        }

        if ($priceUpdate) {
            $release->update($priceUpdate);
        }

        return $release->fresh();
    }
}
