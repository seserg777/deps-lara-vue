<?php

namespace Tests\Feature;

use App\Support\CatalogRemoteCredentials;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class CatalogCategoryApiTest extends TestCase
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

    public function test_root_categories_returns_json_list(): void
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
                            'name' => 'Electronics',
                            'category_parent_id' => 0,
                            'subcategories' => [
                                [
                                    'category_id' => 2,
                                    'name' => 'Phones',
                                    'category_parent_id' => 1,
                                ],
                            ],
                        ],
                    ],
                ]),
        ]);

        $response = $this->getJson('/api/catalog/categories');

        $response->assertOk()
            ->assertJsonPath('data.0.id', 2)
            ->assertJsonPath('data.0.title', 'Phones');

        Http::assertSentCount(2);
    }

    public function test_category_children_returns_subcategories(): void
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
                            'subcategories' => [
                                [
                                    'category_id' => 10,
                                    'name' => 'Alpha',
                                    'category_parent_id' => 1,
                                    'subcategories' => [
                                        [
                                            'category_id' => 11,
                                            'name' => 'Beta',
                                            'category_parent_id' => 10,
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ]),
        ]);

        $response = $this->getJson('/api/catalog/categories/10');

        $response->assertOk()
            ->assertJsonPath('meta.parent.id', 10)
            ->assertJsonPath('meta.parent.title', 'Alpha')
            ->assertJsonPath('meta.breadcrumb.0.id', 1)
            ->assertJsonPath('meta.breadcrumb.0.title', 'Root')
            ->assertJsonPath('meta.breadcrumb.1.id', 10)
            ->assertJsonPath('meta.breadcrumb.1.title', 'Alpha')
            ->assertJsonPath('data.0.id', 11)
            ->assertJsonPath('data.0.title', 'Beta');

        $nested = $this->getJson('/api/catalog/categories/11');

        $nested->assertOk()
            ->assertJsonPath('meta.breadcrumb.0.id', 1)
            ->assertJsonPath('meta.breadcrumb.1.id', 10)
            ->assertJsonPath('meta.breadcrumb.2.id', 11)
            ->assertJsonPath('meta.breadcrumb.2.title', 'Beta');
    }
}
