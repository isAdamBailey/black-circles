<?php

namespace App\Services;

use App\Models\DiscogsCollectionItem;
use App\Models\DiscogsRelease;
use App\Models\Setting;
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

                DiscogsRelease::updateOrCreate(
                    ['discogs_id' => $releaseId],
                    [
                        'title' => $basicInfo['title'] ?? 'Unknown',
                        'artist' => collect($basicInfo['artists'] ?? [])->pluck('name')->implode(', '),
                        'label' => collect($basicInfo['labels'] ?? [])->first()['name'] ?? null,
                        'catalog_number' => collect($basicInfo['labels'] ?? [])->first()['catno'] ?? null,
                        'year' => $basicInfo['year'] ?? null,
                        'cover_image' => $basicInfo['cover_image'] ?? null,
                        'thumb' => $basicInfo['thumb'] ?? null,
                        'formats' => $basicInfo['formats'] ?? null,
                        'genres' => $basicInfo['genres'] ?? null,
                        'styles' => $basicInfo['styles'] ?? null,
                        'discogs_uri' => "https://www.discogs.com/release/{$releaseId}",
                    ]
                );

                DiscogsCollectionItem::updateOrCreate(
                    ['instance_id' => $item['instance_id']],
                    [
                        'discogs_release_id' => $releaseId,
                        'folder_name' => $item['folder_id'] ?? null,
                        'rating' => $item['rating'] ?? null,
                        'notes' => $item['notes'] ?? null,
                        'date_added' => isset($item['date_added']) ? \Carbon\Carbon::parse($item['date_added']) : null,
                    ]
                );

                $synced++;
            }

            $page++;
            if ($page <= $totalPages) {
                usleep(500000);
            }
        } while ($page <= $totalPages);

        Setting::set('discogs_username', $username);
        Setting::set('collection_last_synced', now()->toISOString());

        return ['synced' => $synced, 'username' => $username];
    }

    public function enrichRelease(DiscogsRelease $release): DiscogsRelease
    {
        if ($release->release_data_cached_at && $release->release_data_cached_at->diffInDays(now()) < 7) {
            return $release;
        }

        $data = $this->getRelease($release->discogs_id);
        if ($data) {
            $release->update([
                'tracklist' => $data['tracklist'] ?? null,
                'videos' => $data['videos'] ?? null,
                'notes' => isset($data['notes']) ? strip_tags($data['notes']) : null,
                'release_data_cached_at' => now(),
            ]);
        }

        return $release->fresh();
    }
}
