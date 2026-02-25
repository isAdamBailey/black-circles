<?php

namespace Database\Factories;

use App\Models\DiscogsRelease;
use Illuminate\Database\Eloquent\Factories\Factory;

class DiscogsReleaseFactory extends Factory
{
    protected $model = DiscogsRelease::class;

    public function definition(): array
    {
        return [
            'discogs_id' => $this->faker->unique()->numberBetween(1000000, 9999999),
            'title' => $this->faker->words(3, true),
            'artist' => $this->faker->name(),
            'label' => $this->faker->company(),
            'catalog_number' => strtoupper($this->faker->bothify('??-###')),
            'year' => $this->faker->year(),
            'cover_image' => null,
            'thumb' => null,
            'formats' => [['name' => 'Vinyl', 'qty' => '1', 'descriptions' => ['LP', 'Album']]],
            'genres' => $this->faker->randomElements(['Rock', 'Electronic', 'Jazz', 'Classical', 'Hip Hop'], 2),
            'styles' => $this->faker->randomElements(['Alternative Rock', 'Indie', 'Post-Punk', 'Ambient'], 2),
            'tracklist' => null,
            'videos' => null,
            'lowest_price' => $this->faker->randomFloat(2, 1, 20),
            'median_price' => $this->faker->randomFloat(2, 5, 50),
            'highest_price' => $this->faker->randomFloat(2, 20, 200),
            'discogs_uri' => 'https://www.discogs.com/release/' . $this->faker->numberBetween(1000000, 9999999),
            'release_data_cached_at' => null,
        ];
    }
}
