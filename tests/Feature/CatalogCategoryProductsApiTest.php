<?php

namespace Tests\Feature;

use App\Support\CatalogRemoteCredentials;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class CatalogCategoryProductsApiTest extends TestCase
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

    public function test_category_products_returns_mapped_list(): void
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
                    'result' => $this->sampleCategoryTree(),
                ])
                ->push([
                    'status' => 'ok',
                    'code' => 1,
                    'report' => 'No errors. Success',
                    'result' => [
                        [
                            'product_id' => 101,
                            'name' => 'Item A',
                            'product_price' => 12.5,
                            'image' => 'http://catalog.test/img/a.jpg',
                        ],
                        [
                            'product_id' => 102,
                            'name' => 'Item B',
                            'product_price' => '9.99',
                            'image' => 'relative/b.jpg',
                        ],
                    ],
                ]),
        ]);

        $response = $this->getJson('/api/catalog/categories/11/products?page=1');

        $response->assertOk()
            ->assertJsonPath('data.0.id', 101)
            ->assertJsonPath('data.0.title', 'Item A')
            ->assertJsonPath('data.0.price', 12.5)
            ->assertJsonPath('data.0.image_url', 'http://catalog.test/img/a.jpg')
            ->assertJsonPath('data.1.image_url', 'http://catalog.test/relative/b.jpg')
            ->assertJsonPath('meta.current_page', 1)
            ->assertJsonPath('meta.per_page', 20)
            ->assertJsonPath('meta.has_more', false);

        Http::assertSentCount(3);
    }

    public function test_category_products_unknown_category_returns_404(): void
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
                    'result' => $this->sampleCategoryTree(),
                ]),
        ]);

        $response = $this->getJson('/api/catalog/categories/99999/products');

        $response->assertNotFound();

        Http::assertSentCount(2);
    }

    public function test_category_products_page_2_sets_has_more_when_full_page(): void
    {
        $full_page = [];
        for ($i = 0; $i < 20; $i++) {
            $full_page[] = [
                'product_id' => 200 + $i,
                'name' => 'Bulk '.$i,
                'product_price' => 1,
                'image' => '',
            ];
        }

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
                    'result' => $this->sampleCategoryTree(),
                ])
                ->push([
                    'status' => 'ok',
                    'code' => 1,
                    'report' => 'No errors. Success',
                    'result' => $full_page,
                ]),
        ]);

        $response = $this->getJson('/api/catalog/categories/11/products?page=1');

        $response->assertOk()
            ->assertJsonPath('meta.has_more', true)
            ->assertJsonCount(20, 'data');
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function sampleCategoryTree(): array
    {
        return [
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
        ];
    }
}
