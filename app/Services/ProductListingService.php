<?php

namespace App\Services;

class ProductListingService
{
    public const PER_PAGE = 20;

    public const SEARCH_MAX_LIMIT = 20;

    public function __construct(
        private CatalogApiClient $client,
        private CategoryDirectoryService $categories,
    ) {}

    /**
     * @return array{data: list<array{id:int, title:string, price: float|string|null, image_url: string|null}>, meta: array{limit: int, query: string}}
     */
    public function searchByQuery(string $query, int $limit): array
    {
        $query = trim($query);
        $limit = max(1, min(self::SEARCH_MAX_LIMIT, $limit));

        $raw = $this->client->call('product', 'search', [
            'search' => $query,
            'limit' => $limit,
            'limitstart' => 0,
        ]);

        $rows = $this->normalizeSearchRows($raw);
        $rows = array_slice($rows, 0, $limit);
        $data = array_map(fn (array $row): array => $this->mapProductRow($row), $rows);

        return [
            'data' => $data,
            'meta' => [
                'limit' => $limit,
                'query' => $query,
            ],
        ];
    }

    /**
     * @return array{data: list<array{id:int, title:string, price: float|string|null, image_url: string|null}>, meta: array{current_page: int, per_page: int, has_more: bool}}
     */
    public function productsInCategory(int $category_id, int $page): array
    {
        $payload = $this->categories->childrenOf($category_id);
        if ($payload['parent'] === null) {
            abort(404);
        }

        $page = max(1, $page);
        $per_page = self::PER_PAGE;
        $limitstart = ($page - 1) * $per_page;

        // Addon API: product/search supports category filter; product/list has no category filter.
        $raw = $this->client->call('product', 'search', [
            'categories' => [$category_id],
            'include_subcat' => false,
            'limit' => $per_page,
            'limitstart' => $limitstart,
        ]);

        $rows = $this->normalizeSearchRows($raw);
        $data = array_map(fn (array $row): array => $this->mapProductRow($row), $rows);

        return [
            'data' => $data,
            'meta' => [
                'current_page' => $page,
                'per_page' => $per_page,
                'has_more' => count($rows) === $per_page,
            ],
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function normalizeSearchRows(mixed $raw): array
    {
        if (! is_array($raw)) {
            return [];
        }

        if (array_is_list($raw)) {
            return array_values(array_filter($raw, 'is_array'));
        }

        foreach (['products', 'list', 'items', 'data', 'rows', 'rows2'] as $key) {
            if (! isset($raw[$key]) || ! is_array($raw[$key])) {
                continue;
            }

            $inner = $raw[$key];
            if (array_is_list($inner)) {
                return array_values(array_filter($inner, 'is_array'));
            }

            $nested = $this->normalizeSearchRows($inner);
            if ($nested !== []) {
                return $nested;
            }
        }

        if (isset($raw['product_id']) || isset($raw['id'])) {
            return [$raw];
        }

        return [];
    }

    /**
     * @param  array<string, mixed>  $row
     * @return array{id: int, title: string, price: float|string|null, image_url: string|null}
     */
    private function mapProductRow(array $row): array
    {
        $id = (int) ($row['product_id'] ?? $row['id'] ?? 0);
        $title = (string) ($row['name'] ?? $row['product_name'] ?? $row['title'] ?? '');
        $price = $row['product_price'] ?? $row['price'] ?? null;
        if (is_string($price)) {
            $trimmed = trim($price);
            $price = $trimmed === '' ? null : (is_numeric($trimmed) ? (float) $trimmed : $trimmed);
        } elseif (is_int($price) || is_float($price)) {
            $price = (float) $price;
        } else {
            $price = null;
        }

        $image = $row['image'] ?? $row['product_thumb_image'] ?? $row['thumb_image'] ?? null;
        $image_url = is_string($image) && $image !== '' ? $this->resolveImageUrl($image) : null;

        return [
            'id' => $id,
            'title' => $title,
            'price' => $price,
            'image_url' => $image_url,
        ];
    }

    private function resolveImageUrl(string $image): string
    {
        if (str_starts_with($image, 'http://') || str_starts_with($image, 'https://')) {
            return $image;
        }

        $base = rtrim((string) config('catalog.remote_base_url', ''), '/');

        return $base === '' ? $image : $base.'/'.ltrim($image, '/');
    }
}
