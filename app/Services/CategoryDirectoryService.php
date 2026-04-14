<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;

class CategoryDirectoryService
{
    public function __construct(
        private CatalogApiClient $client,
    ) {}

    /**
     * @return list<array{id:int, title:string}>
     */
    public function rootCategories(): array
    {
        $flat = $this->getFlatCategories();
        $roots = $this->detectRoots($flat);
        $roots = $this->unwrapSingleContainerRoot($flat, $roots);

        return $this->mapPublicList($roots);
    }

    /**
     * @return array{parent: array{id:int, title:string}|null, breadcrumb: list<array{id:int, title:string}>, children: list<array{id:int, title:string}>}
     */
    public function childrenOf(int $category_id): array
    {
        $flat = $this->getFlatCategories();
        $by_id = [];
        foreach ($flat as $row) {
            $by_id[$row['id']] = $row;
        }
        $parent = $by_id[$category_id] ?? null;
        $children = array_values(array_filter($flat, fn (array $n): bool => (int) $n['parent_id'] === $category_id));

        return [
            'parent' => $parent !== null ? ['id' => $parent['id'], 'title' => $parent['title']] : null,
            'breadcrumb' => $this->breadcrumbPath($by_id, $category_id),
            'children' => $this->mapPublicList($children),
        ];
    }

    /**
     * @param  array<int, array{id:int, parent_id:int|null, title:string}>  $by_id
     * @return list<array{id:int, title:string}>
     */
    private function breadcrumbPath(array $by_id, int $category_id): array
    {
        if (! isset($by_id[$category_id])) {
            return [];
        }

        $segments = [];
        $id = $category_id;
        for ($guard = 0; $guard < 1000; $guard++) {
            $row = $by_id[$id];
            array_unshift($segments, [
                'id' => $row['id'],
                'title' => $row['title'],
            ]);
            $pid = $row['parent_id'];
            if ($pid === null || $pid === 0) {
                break;
            }
            if (! isset($by_id[$pid])) {
                break;
            }
            $id = $pid;
        }

        return $segments;
    }

    /**
     * @return list<array{id:int, parent_id:int|null, title:string}>
     */
    private function getFlatCategories(): array
    {
        $ttl = max(30, (int) config('catalog.tree_cache_ttl', 300));
        $key = 'catalog.category_tree_flat';

        return Cache::remember($key, $ttl, function (): array {
            $tree = $this->client->call('category', 'tree');

            return $this->flattenTree($tree);
        });
    }

    /**
     * @return list<array{id:int, parent_id:int|null, title:string}>
     */
    private function flattenTree(mixed $tree_result): array
    {
        $acc = [];
        if (is_array($tree_result) && array_is_list($tree_result)) {
            foreach ($tree_result as $node) {
                $this->walkCategoryNode($node, null, $acc);
            }
        } elseif (is_array($tree_result)) {
            $this->walkCategoryNode($tree_result, null, $acc);
        }

        return $acc;
    }

    /**
     * @param  list<array{id:int, parent_id:int|null, title:string}>  $acc
     */
    private function walkCategoryNode(mixed $node, ?int $parent_id, array &$acc): void
    {
        if (! is_array($node)) {
            return;
        }

        $id = $node['category_id'] ?? $node['id'] ?? null;
        if ($id === null) {
            foreach ($node as $child) {
                if (is_array($child)) {
                    $this->walkCategoryNode($child, $parent_id, $acc);
                }
            }

            return;
        }

        $id = (int) $id;
        $title = (string) ($node['name'] ?? $node['category_name'] ?? $node['title'] ?? $id);
        if (isset($node['category_parent_id'])) {
            $parent_int = (int) $node['category_parent_id'];
        } elseif (isset($node['parent_id'])) {
            $parent_int = (int) $node['parent_id'];
        } else {
            $parent_int = $parent_id;
        }

        $acc[] = [
            'id' => $id,
            'parent_id' => $parent_int,
            'title' => $title,
        ];

        $nested = $node['subcategories'] ?? $node['child'] ?? $node['children'] ?? $node['categories'] ?? $node['subcat'] ?? null;
        if ($nested === null) {
            return;
        }

        $list = is_array($nested) && array_is_list($nested) ? $nested : [$nested];
        foreach ($list as $child) {
            if (is_array($child)) {
                $this->walkCategoryNode($child, $id, $acc);
            }
        }
    }

    /**
     * @param  list<array{id:int, parent_id:int|null, title:string}>  $flat
     * @return list<array{id:int, parent_id:int|null, title:string}>
     */
    private function detectRoots(array $flat): array
    {
        $ids = array_map(fn (array $n): int => $n['id'], $flat);
        $id_set = array_fill_keys($ids, true);
        $roots = array_values(array_filter($flat, function (array $n) use ($id_set): bool {
            $p = $n['parent_id'];

            return $p === null || $p === 0 || ! isset($id_set[$p]);
        }));

        if ($roots !== []) {
            return $roots;
        }

        return $flat;
    }

    /**
     * If the tree exposes a single top node that only wraps the real first level, show its children on the home page.
     *
     * @param  list<array{id:int, parent_id:int|null, title:string}>  $flat
     * @param  list<array{id:int, parent_id:int|null, title:string}>  $roots
     * @return list<array{id:int, parent_id:int|null, title:string}>
     */
    private function unwrapSingleContainerRoot(array $flat, array $roots): array
    {
        if (count($roots) !== 1) {
            return $roots;
        }

        $rid = $roots[0]['id'];
        $children = array_values(array_filter($flat, fn (array $n): bool => (int) ($n['parent_id'] ?? 0) === $rid));

        return $children !== [] ? $children : $roots;
    }

    /**
     * @param  list<array{id:int, parent_id:int|null, title:string}>  $rows
     * @return list<array{id:int, title:string}>
     */
    private function mapPublicList(array $rows): array
    {
        return array_map(fn (array $n): array => [
            'id' => $n['id'],
            'title' => $n['title'],
        ], $rows);
    }
}
