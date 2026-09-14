/** Normaliza path de rota (`/` ou `/foo`, sem trailing slash). */
export function normalizePath(path: string): string {
	if (!path || path === '/') return '/';
	const withSlash = path.startsWith('/') ? path : `/${path}`;
	return withSlash.replace(/\/+$/, '') || '/';
}

/** Par EN ↔ PT a partir de qualquer path localizado. */
export function localePathPair(path: string): { en: string; pt: string } {
	const normalized = normalizePath(path);

	if (normalized === '/PT' || normalized.startsWith('/PT/')) {
		const en = normalized === '/PT' ? '/' : normalized.slice(3) || '/';
		return { en: normalizePath(en), pt: normalized };
	}

	return {
		en: normalized,
		pt: normalized === '/' ? '/PT' : `/PT${normalized}`,
	};
}

export function absoluteUrl(base: string, path: string): string {
	const origin = base.replace(/\/$/, '');
	const normalized = normalizePath(path);
	return normalized === '/' ? `${origin}/` : `${origin}${normalized}`;
}
