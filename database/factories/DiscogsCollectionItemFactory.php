<?php

namespace Database\Factories;

use App\Models\DiscogsCollectionItem;
use App\Models\DiscogsRelease;
use Illuminate\Database\Eloquent\Factories\Factory;

class DiscogsCollectionItemFactory extends Factory
{
    protected $model = DiscogsCollectionItem::class;

    public function definition(): array
    {
        return [
            'instance_id' => $this->faker->unique()->numberBetween(100000, 999999),
            'discogs_release_id' => DiscogsRelease::factory(),
            'folder_id' => 1,
            'rating' => $this->faker->numberBetween(0, 5),
            'notes' => null,
            'date_added' => $this->faker->dateTimeBetween('-5 years', 'now'),
        ];
    }
}
