<?php

namespace Tests\Feature;

use Tests\TestCase;

class BannerApiTest extends TestCase
{
    public function test_banner_index_returns_values_from_config(): void
    {
        config()->set('banner.visible', true);
        config()->set('banner.content', '<strong>Winter discount</strong>');

        $this->getJson('/api/banner')
            ->assertOk()
            ->assertExactJson([
                'visible' => true,
                'content' => '<strong>Winter discount</strong>',
            ]);
    }

    public function test_banner_index_casts_visibility_and_content_values(): void
    {
        config()->set('banner.visible', 0);
        config()->set('banner.content', 12345);

        $this->getJson('/api/banner')
            ->assertOk()
            ->assertJsonPath('visible', false)
            ->assertJsonPath('content', '12345');
    }
}
