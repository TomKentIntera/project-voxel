<?php

namespace Tests\Feature;

use Tests\TestCase;

class FaqApiTest extends TestCase
{
    public function test_faqs_index_returns_all_faqs_without_filter(): void
    {
        config()->set('faqs', [
            [
                'title' => 'First',
                'content' => 'A',
                'showOnHome' => true,
            ],
            [
                'title' => 'Second',
                'content' => 'B',
                'showOnHome' => false,
            ],
        ]);

        $this->getJson('/api/faqs')
            ->assertOk()
            ->assertJsonCount(2, 'faqs')
            ->assertJsonPath('faqs.0.title', 'First')
            ->assertJsonPath('faqs.0.showOnHome', true)
            ->assertJsonPath('faqs.1.title', 'Second')
            ->assertJsonPath('faqs.1.showOnHome', false);
    }

    public function test_faqs_index_can_filter_homepage_only_entries(): void
    {
        config()->set('faqs', [
            [
                'title' => 'First',
                'content' => 'A',
                'showOnHome' => true,
            ],
            [
                'title' => 'Second',
                'content' => 'B',
                'showOnHome' => false,
            ],
            [
                'title' => 'Third',
                'content' => 'C',
                'showOnHome' => true,
            ],
        ]);

        $this->getJson('/api/faqs?homepage_only=1')
            ->assertOk()
            ->assertJsonCount(2, 'faqs')
            ->assertJsonPath('faqs.0.title', 'First')
            ->assertJsonPath('faqs.0.showOnHome', true)
            ->assertJsonPath('faqs.1.title', 'Third')
            ->assertJsonPath('faqs.1.showOnHome', true);
    }
}
