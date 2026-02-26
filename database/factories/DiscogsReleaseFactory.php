<?php

namespace Database\Factories;

use App\Models\DiscogsRelease;
use App\Models\Genre;
use App\Models\Style;
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
            'tracklist' => null,
            'videos' => null,
            'lowest_price' => $this->faker->randomFloat(2, 1, 20),
            'median_price' => null,
            'highest_price' => null,
            'discogs_uri' => 'https://www.discogs.com/release/'.$this->faker->numberBetween(1000000, 9999999),
            'release_data_cached_at' => null,
        ];
    }

    /**
     * Attach specific genres (by name) after the release is created.
     */
    public function withGenres(array $genreNames): static
    {
        return $this->afterCreating(function (DiscogsRelease $release) use ($genreNames) {
            $ids = collect($genreNames)
                ->map(fn ($name) => Genre::firstOrCreate(['name' => $name])->id);
            $release->genres()->sync($ids);
        });
    }

    /**
     * Attach specific styles (by name) after the release is created.
     */
    public function withStyles(array $styleNames): static
    {
        return $this->afterCreating(function (DiscogsRelease $release) use ($styleNames) {
            $ids = collect($styleNames)
                ->map(fn ($name) => Style::firstOrCreate(['name' => $name])->id);
            $release->styles()->sync($ids);
        });
    }
}
