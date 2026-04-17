<?php

namespace Tests\Feature;

use App\Support\CatalogRemoteCredentials;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class CatalogApiErrorEnvelopeTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $credentials = CatalogRemoteCredentials::fromRemoteApiKey(
            base64_encode('user@test.dev:secret')
        );

        config([
            'catalog.remote_base_url' => 'http://catalog.test',
            'catalog.email' => $credentials['email'],
            'catalog.password' => $credentials['password'],
            'catalog.timeout' => 10,
            'catalog.token_cache_ttl' => 3600,
            'catalog.tree_cache_ttl' => 60,
        ]);

        Cache::flush();
    }

    public function test_not_found_includes_message_and_code(): void
    {
        Http::fake([
            'catalog.test/*' => Http::sequence()
                ->push([
                    'status' => 'ok',
                    'code' => 1,
                    'report' => 'No errors. Success',
                    'result' => 'test-bearer-token',
                ])
                ->push([
                    'status' => 'ok',
                    'code' => 1,
                    'report' => 'No errors. Success',
                    'result' => [
                        [
                            'category_id' => 1,
                            'name' => 'Root',
                            'category_parent_id' => 0,
                            'subcategories' => [],
                        ],
                    ],
                ]),
        ]);

        $response = $this->getJson('/api/catalog/categories/404/products');

        $response->assertNotFound()
            ->assertJsonStructure(['message', 'code'])
            ->assertJsonPath('code', 'not_found')
            ->assertJsonMissingPath('errors');

        Http::assertSentCount(2);
    }

    public function test_validation_error_includes_message_code_and_errors(): void
    {
        $response = $this->getJson('/api/catalog/search?q=test&limit=99');

        $response->assertStatus(422)
            ->assertJsonStructure([
                'message',
                'code',
                'errors' => [
                    'limit',
                ],
            ])
            ->assertJsonPath('code', 'validation_failed');
    }
}
