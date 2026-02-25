<?php

namespace Tests\Feature;

use App\Models\DiscogsCollectionItem;
use App\Models\DiscogsRelease;
use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CollectionTest extends TestCase
{
    use RefreshDatabase;

    public function test_collection_index_renders(): void
    {
        $response = $this->get(route('collection.index'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->component('Collection/Index'));
    }

    public function test_collection_index_shows_releases(): void
    {
        $release = DiscogsRelease::factory()->create();
        DiscogsCollectionItem::factory()->for($release, 'release')->create();

        $response = $this->get(route('collection.index'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Collection/Index')
            ->has('releases.data', 1)
        );
    }

    public function test_collection_show_renders(): void
    {
        $release = DiscogsRelease::factory()->create([
            'release_data_cached_at' => now(),
        ]);

        $response = $this->get(route('collection.show', $release->discogs_id));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->component('Collection/Show'));
    }

    public function test_collection_can_be_filtered_by_genre(): void
    {
        DiscogsRelease::factory()->create(['genres' => ['Rock']]);
        DiscogsRelease::factory()->create(['genres' => ['Electronic']]);

        $response = $this->get(route('collection.index', ['genres' => 'Rock']));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Collection/Index')
            ->has('releases.data', 1)
        );
    }

    public function test_collection_can_be_searched(): void
    {
        DiscogsRelease::factory()->create(['title' => 'Dark Side of the Moon', 'artist' => 'Pink Floyd']);
        DiscogsRelease::factory()->create(['title' => 'Rumours', 'artist' => 'Fleetwood Mac']);

        $response = $this->get(route('collection.index', ['search' => 'Pink Floyd']));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Collection/Index')
            ->has('releases.data', 1)
        );
    }

    public function test_settings_index_renders(): void
    {
        $response = $this->get(route('settings.index'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->component('Settings/Index'));
    }

    public function test_settings_can_save_username(): void
    {
        $response = $this->post(route('settings.update'), [
            'discogs_username' => 'testuser',
        ]);

        $response->assertRedirect();
        $this->assertEquals('testuser', Setting::get('discogs_username'));
    }

    public function test_settings_username_is_required(): void
    {
        $response = $this->post(route('settings.update'), [
            'discogs_username' => '',
        ]);

        $response->assertSessionHasErrors('discogs_username');
    }
}
