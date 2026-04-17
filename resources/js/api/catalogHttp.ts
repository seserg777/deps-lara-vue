/**
 * Shared JSON client for `/api/catalog` — stable success (`data` / optional `meta`) and error bodies.
 */

export class CatalogApiError extends Error {
    readonly status: number;

    readonly code?: string;

    readonly errors?: Record<string, string[]>;

    constructor(
        message: string,
        status: number,
        code?: string,
        errors?: Record<string, string[]>,
    ) {
        super(message);
        this.name = 'CatalogApiError';
        this.status = status;
        this.code = code;
        this.errors = errors;
    }
}

function parseJsonBody(text: string): unknown {
    if (!text) {
        return {};
    }
    try {
        return JSON.parse(text) as unknown;
    } catch {
        return {};
    }
}

/**
 * GET JSON from catalog API. Success responses must include a `data` key.
 */
export async function requestCatalogJson<T extends { data: unknown }>(
    url: string,
    init?: RequestInit,
): Promise<T> {
    const res = await fetch(url, {
        ...init,
        headers: {
            Accept: 'application/json',
            ...(init?.headers ?? {}),
        },
    });

    const text = await res.text();
    const body = parseJsonBody(text) as Record<string, unknown>;

    if (!res.ok) {
        const message = typeof body.message === 'string' ? body.message : `HTTP ${res.status}`;
        const code = typeof body.code === 'string' ? body.code : undefined;
        const rawErrors = body.errors;
        const errors =
            rawErrors !== null &&
            rawErrors !== undefined &&
            typeof rawErrors === 'object' &&
            !Array.isArray(rawErrors)
                ? (rawErrors as Record<string, string[]>)
                : undefined;

        throw new CatalogApiError(message, res.status, code, errors);
    }

    if (!('data' in body) || body.data === undefined) {
        throw new CatalogApiError('Invalid API response: missing data', res.status, 'invalid_response');
    }

    return body as T;
}
