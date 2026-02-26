<?php

namespace Tests;

use Database\Seeders\MoodSeeder;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed([MoodSeeder::class]);
    }
}
