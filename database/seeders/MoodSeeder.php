<?php

namespace Database\Seeders;

use App\Models\Mood;
use App\Models\MoodExcludeStyle;
use App\Models\MoodGenre;
use App\Models\MoodStyle;
use Illuminate\Database\Seeder;

class MoodSeeder extends Seeder
{
    public function run(): void
    {
        $moods = [
            [
                'slug' => 'melancholy',
                'label' => 'Melancholy',
                'emoji' => '🌧',
                'genres' => ['Blues', 'Jazz', 'Folk, World, & Country'],
                'styles' => ['Slowcore', 'Soul', 'Acoustic', 'Ballad'],
                'exclude_styles' => [],
            ],
            [
                'slug' => 'energetic',
                'label' => 'Energetic',
                'emoji' => '⚡',
                'genres' => ['Rock', 'Electronic', 'Hip-Hop'],
                'styles' => ['Punk', 'Hardcore', 'Techno', 'Garage Rock'],
                'exclude_styles' => [],
            ],
            [
                'slug' => 'chill',
                'label' => 'Chill',
                'emoji' => '🌿',
                'genres' => ['Jazz', 'Electronic', 'Folk, World, & Country'],
                'styles' => ['Ambient', 'Downtempo', 'Bossa Nova', 'Lounge'],
                'exclude_styles' => [],
            ],
            [
                'slug' => 'dark',
                'label' => 'Dark',
                'emoji' => '🌑',
                'genres' => ['Rock', 'Electronic', 'Metal'],
                'styles' => ['Gothic Rock', 'Post-Punk', 'Industrial', 'Doom Metal', 'Darkwave'],
                'exclude_styles' => [],
            ],
            [
                'slug' => 'happy',
                'label' => 'Happy',
                'emoji' => '☀️',
                'genres' => ['Pop', 'Reggae', 'Funk / Soul'],
                'styles' => ['Disco', 'Funk', 'Pop Rock', 'Bubblegum'],
                'exclude_styles' => [],
            ],
            [
                'slug' => 'fast',
                'label' => 'Fast',
                'emoji' => '🔥',
                'genres' => [],
                'styles' => ['Speed Metal', 'Thrash Metal', 'Power Metal', 'Death Metal', 'Black Metal', 'Heavy Metal', 'Grindcore', 'Metalcore', 'Neoclassical', 'US Power Metal'],
                'exclude_styles' => ['Doom Metal', 'Stoner Rock', 'Ballad', 'Slowcore', 'Drone', 'Ambient', 'Funeral Doom', 'Lounge', 'Acoustic', 'Folk Rock', 'Soundtrack', 'Soft Rock', 'Singer-Songwriter', 'Stage & Screen'],
            ],
            [
                'slug' => 'focus',
                'label' => 'Focus',
                'emoji' => '🎯',
                'genres' => ['Classical', 'Electronic', 'Jazz'],
                'styles' => ['Ambient', 'Post-Rock', 'Instrumental', 'Modern Classical'],
                'exclude_styles' => [],
            ],
            [
                'slug' => 'party',
                'label' => 'Party',
                'emoji' => '🎉',
                'genres' => ['Electronic', 'Hip-Hop', 'Funk / Soul'],
                'styles' => ['Punk', 'House', 'Techno', 'Disco', 'Funk', 'Dance'],
                'exclude_styles' => [],
            ],
        ];

        foreach ($moods as $i => $data) {
            $exclude = $data['exclude_styles'];
            unset($data['exclude_styles']);
            $genres = $data['genres'];
            $styles = $data['styles'];
            unset($data['genres'], $data['styles']);

            $data['sort_order'] = $i;
            $mood = Mood::create($data);

            foreach ($genres as $name) {
                MoodGenre::create(['mood_id' => $mood->id, 'genre_name' => $name]);
            }
            foreach ($styles as $name) {
                MoodStyle::create(['mood_id' => $mood->id, 'style_name' => $name]);
            }
            foreach ($exclude as $name) {
                MoodExcludeStyle::create(['mood_id' => $mood->id, 'style_name' => $name]);
            }
        }
    }
}
