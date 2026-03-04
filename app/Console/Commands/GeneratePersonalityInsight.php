<?php

namespace App\Console\Commands;

use App\Models\Setting;
use App\Services\PersonalityInsightService;
use Illuminate\Console\Command;

class GeneratePersonalityInsight extends Command
{
    protected $signature = 'personality:generate';

    protected $description = 'Generate and cache the AI personality insight from the collection';

    public function handle(PersonalityInsightService $personalityInsight): int
    {
        $hasToken = ! empty(config('services.huggingface.token'));

        if (! $hasToken) {
            $this->warn('No HuggingFace token configured. Skipping.');

            return 0;
        }

        $collectionSize = $personalityInsight->collectionSize();

        if ($collectionSize === 0) {
            $this->warn('Collection is empty. Skipping.');

            return 0;
        }

        $this->info('Generating personality insight…');

        $topStyles = $personalityInsight->topStyles();
        $topGenres = $personalityInsight->topGenres();
        $insight = $personalityInsight->generatePersonalityInsight($topStyles, $topGenres);

        if (empty($insight)) {
            $this->error('AI returned an empty response.');

            return 1;
        }

        Setting::set('personality_insight', $insight);

        $this->info('Insight saved.');

        return 0;
    }
}
