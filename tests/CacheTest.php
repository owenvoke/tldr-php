<?php

namespace BrainMaestro\Tldr\Tests;

use BrainMaestro\Tldr\Cache;
use PHPUnit\Framework\TestCase;

class CacheTest extends TestCase
{
    /**
     * @test
     */
    public function it_can_update_the_index_file_when_not_exists()
    {
        $this->assertTrue(Cache::update());
    }

    /**
     * @test
     */
    public function it_can_find_a_valid_page_in_the_index()
    {
        $this->assertFileExists(Cache::get('tar'));
    }

    /**
     * @test
     */
    public function it_returns_empty_string_for_invalid_page()
    {
        $this->assertEmpty(Cache::get('does-not-exist'));
    }

    /**
     * @test
     */
    public function it_returns_true_for_existing_page()
    {
        $this->assertEmpty(Cache::exists('tar'));
    }
}
