export function sanitizeStoredAssetUrl(value: string | null | undefined): string | null {
    const trimmed = typeof value === 'string' ? value.trim() : '';

    if (trimmed.length === 0 || trimmed.startsWith('data:')) {
        return null;
    }

    if (/@[1-4]\b/.test(trimmed)) {
        return trimmed;
    }

    if (trimmed.startsWith('/')) {
        if (trimmed.startsWith('/storage/')) {
            return `/media/${trimmed.slice('/storage/'.length)}`;
        }

        return trimmed;
    }

    if (trimmed.startsWith('storage/')) {
        return `/media/${trimmed.slice('storage/'.length)}`;
    }

    if (trimmed.startsWith('/media/') || trimmed.startsWith('media/')) {
        return trimmed.startsWith('/') ? trimmed : `/${trimmed}`;
    }

    if (/^https?:\/\//i.test(trimmed)) {
        try {
            const url = new URL(trimmed);

            if (url.pathname.startsWith('/storage/')) {
                return `/media/${url.pathname.slice('/storage/'.length)}${url.search}${url.hash}`;
            }

            return trimmed;
        } catch {
            return trimmed;
        }
    }

    if (!trimmed.includes(' ')) {
        return `/media/${trimmed.replace(/^\/+/, '')}`;
    }

    return trimmed;
}
