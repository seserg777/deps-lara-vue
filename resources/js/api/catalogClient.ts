const base = '/api/catalog';

export const CATEGORY_PATH_PREFIX = '/categories';

/**
 * Parse `/categories/1/2/3` param into positive integer IDs, or null if invalid.
 */
export function parseCategoryPathParam(path_match: string | string[] | undefined): number[] | null {
    const raw = Array.isArray(path_match) ? path_match.join('/') : (path_match ?? '');
    const segments = raw.split('/').filter(Boolean);
    if (segments.length === 0) {
        return null;
    }
    const ids: number[] = [];
    for (const s of segments) {
        if (!/^\d+$/.test(s)) {
            return null;
        }
        const n = Number(s);
        if (!Number.isInteger(n) || n < 1) {
            return null;
        }
        ids.push(n);
    }

    return ids;
}

export function categoryPathFromIds(ids: number[]): string {
    if (ids.length === 0) {
        return CATEGORY_PATH_PREFIX;
    }

    return `${CATEGORY_PATH_PREFIX}/${ids.join('/')}`;
}

export type CategoryItem = {
    id: number;
    title: string;
};

export type CategoriesIndexResponse = {
    data: CategoryItem[];
};

export type CategoryShowResponse = {
    data: CategoryItem[];
    meta: {
        parent: CategoryItem | null;
        breadcrumb: CategoryItem[];
    };
};

export async function fetchRootCategories(): Promise<CategoryItem[]> {
    const res = await fetch(`${base}/categories`, {
        headers: { Accept: 'application/json' },
    });
    if (!res.ok) {
        const err = await res.json().catch(() => ({}));
        throw new Error((err as { message?: string }).message ?? `HTTP ${res.status}`);
    }
    const body = (await res.json()) as CategoriesIndexResponse;

    return body.data;
}

export async function fetchCategoryChildren(id: number): Promise<{
    parent: CategoryItem | null;
    breadcrumb: CategoryItem[];
    children: CategoryItem[];
}> {
    const res = await fetch(`${base}/categories/${id}`, {
        headers: { Accept: 'application/json' },
    });
    if (!res.ok) {
        const err = await res.json().catch(() => ({}));
        throw new Error((err as { message?: string }).message ?? `HTTP ${res.status}`);
    }
    const body = (await res.json()) as CategoryShowResponse;

    return {
        parent: body.meta.parent,
        breadcrumb: body.meta.breadcrumb ?? [],
        children: body.data,
    };
}
