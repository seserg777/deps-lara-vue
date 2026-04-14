<?php

namespace Tests\Feature;

use App\Support\CatalogRemoteCredentials;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class CatalogProductSearchApiTest extends TestCase
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

    public function test_search_returns_mapped_products(): void
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
                        'products' => [
                            [
                                'product_id' => 501,
                                'name' => 'Cable A',
                                'product_price' => 5,
                                'image' => 'http://catalog.test/img/a.jpg',
                            ],
                        ],
                    ],
                ]),
        ]);

        $response = $this->getJson('/api/catalog/search?q='.rawurlencode('кабель'));

        $response->assertOk()
            ->assertJsonPath('data.0.id', 501)
            ->assertJsonPath('data.0.title', 'Cable A')
            ->assertJsonPath('data.0.price', 5)
            ->assertJsonPath('data.0.image_url', 'http://catalog.test/img/a.jpg')
            ->assertJsonPath('meta.query', 'кабель')
            ->assertJsonPath('meta.limit', 10);

        Http::assertSentCount(2);
    }

    public function test_search_short_query_returns_empty_without_remote_call(): void
    {
        Http::fake([
            'catalog.test/*' => Http::response([], 500),
        ]);

        $response = $this->getJson('/api/catalog/search?q=a');

        $response->assertOk()
            ->assertJsonPath('data', [])
            ->assertJsonPath('meta.limit', 0);

        Http::assertNothingSent();
    }

    public function test_search_empty_query_returns_empty(): void
    {
        Http::fake([
            'catalog.test/*' => Http::response([], 500),
        ]);

        $response = $this->getJson('/api/catalog/search');

        $response->assertOk()
            ->assertJsonPath('data', []);

        Http::assertNothingSent();
    }

    public function test_search_nested_result_object_extracts_rows(): void
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
                        'filters' => [],
                        'rows' => [
                            [
                                'product_id' => 9,
                                'name' => 'Nested item',
                                'product_price' => null,
                                'image' => '',
                            ],
                        ],
                    ],
                ]),
        ]);

        $response = $this->getJson('/api/catalog/search?q=test');

        $response->assertOk()
            ->assertJsonPath('data.0.id', 9)
            ->assertJsonPath('data.0.title', 'Nested item');
    }
}
