const base = '/api/catalog';

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

export async function fetchCategoryChildren(id: number): Promise<{ parent: CategoryItem | null; children: CategoryItem[] }> {
    const res = await fetch(`${base}/categories/${id}`, {
        headers: { Accept: 'application/json' },
    });
    if (!res.ok) {
        const err = await res.json().catch(() => ({}));
        throw new Error((err as { message?: string }).message ?? `HTTP ${res.status}`);
    }
    const body = (await res.json()) as CategoryShowResponse;

    return { parent: body.meta.parent, children: body.data };
}
