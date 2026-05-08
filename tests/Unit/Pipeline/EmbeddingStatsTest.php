<?php

declare(strict_types=1);

namespace Tests\Unit\Pipeline;

use App\Pipeline\EmbeddingStats;
use Tests\Support\TestCase;

class EmbeddingStatsTest extends TestCase
{
    public function test_stats_defaults_are_zero(): void
    {
        $stats = new EmbeddingStats();

        $this->assertEquals(0, $stats->total);
        $this->assertEquals(0, $stats->inserted);
        $this->assertEquals(0, $stats->skipped);
    }

    public function test_stats_can_be_incremented(): void
    {
        $stats = new EmbeddingStats();
        $stats->total++;
        $stats->inserted++;
        $stats->skipped++;

        $this->assertEquals(1, $stats->total);
        $this->assertEquals(1, $stats->inserted);
        $this->assertEquals(1, $stats->skipped);
    }

    public function test_stats_multiple_increments(): void
    {
        $stats = new EmbeddingStats();

        for ($i = 0; $i < 100; $i++) {
            $stats->total++;
        }
        for ($i = 0; $i < 80; $i++) {
            $stats->inserted++;
        }
        for ($i = 0; $i < 20; $i++) {
            $stats->skipped++;
        }

        $this->assertEquals(100, $stats->total);
        $this->assertEquals(80, $stats->inserted);
        $this->assertEquals(20, $stats->skipped);
    }

    public function test_stats_total_equals_inserted_plus_skipped(): void
    {
        $stats = new EmbeddingStats();

        $stats->total = 500;
        $stats->inserted = 450;
        $stats->skipped = 50;

        $this->assertEquals($stats->inserted + $stats->skipped, $stats->total);
    }

    public function test_stats_inserted_can_be_zero(): void
    {
        $stats = new EmbeddingStats();
        $stats->total = 100;
        $stats->skipped = 100;

        $this->assertEquals(0, $stats->inserted);
        $this->assertEquals(100, $stats->total);
    }

    public function test_stats_skipped_can_be_zero(): void
    {
        $stats = new EmbeddingStats();
        $stats->total = 100;
        $stats->inserted = 100;

        $this->assertEquals(0, $stats->skipped);
        $this->assertEquals(100, $stats->total);
    }

    public function test_stats_all_can_be_zero(): void
    {
        $stats = new EmbeddingStats();

        $this->assertEquals(0, $stats->total);
        $this->assertEquals(0, $stats->inserted);
        $this->assertEquals(0, $stats->skipped);
    }

    public function test_stats_large_numbers(): void
    {
        $stats = new EmbeddingStats();
        $stats->total = 999999;
        $stats->inserted = 999900;
        $stats->skipped = 99;

        $this->assertEquals(999999, $stats->total);
        $this->assertEquals(999900, $stats->inserted);
        $this->assertEquals(99, $stats->skipped);
    }

    public function test_stats_properties_are_public(): void
    {
        $reflection = new \ReflectionClass(EmbeddingStats::class);

        foreach (['total', 'inserted', 'skipped'] as $property) {
            $prop = $reflection->getProperty($property);
            $this->assertTrue($prop->isPublic());
        }
    }

    public function test_stats_properties_are_integers(): void
    {
        $stats = new EmbeddingStats();
        $stats->total = 10;
        $stats->inserted = 8;
        $stats->skipped = 2;

        $this->assertIsInt($stats->total);
        $this->assertIsInt($stats->inserted);
        $this->assertIsInt($stats->skipped);
    }

    public function test_stats_can_be_reset(): void
    {
        $stats = new EmbeddingStats();
        $stats->total = 100;
        $stats->inserted = 90;
        $stats->skipped = 10;

        $stats->total = 0;
        $stats->inserted = 0;
        $stats->skipped = 0;

        $this->assertEquals(0, $stats->total);
        $this->assertEquals(0, $stats->inserted);
        $this->assertEquals(0, $stats->skipped);
    }

    public function test_stats_has_correct_class(): void
    {
        $stats = new EmbeddingStats();

        $this->assertInstanceOf(EmbeddingStats::class, $stats);
    }
}
