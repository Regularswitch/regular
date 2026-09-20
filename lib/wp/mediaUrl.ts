const LOCAL_WP_HOSTS = ['regularswitch-wp.local', 'localhost', '127.0.0.1'];
const PRODUCTION_WP_ORIGIN = 'https://wp.regularswitch.com';

/**
 * Reescreve URLs de mídia do WP local para outro host (ex.: produção).
 * Só ativa com WP_MEDIA_FALLBACK no .env — evita quebrar uploads locais novos.
 */
export function wpMediaUrl(url?: string | null): string | undefined {
	if (!url) return undefined;

	const fallback = process.env.WP_MEDIA_FALLBACK;
	if (!fallback) return url;

	try {
		const parsed = new URL(url);
		if (!LOCAL_WP_HOSTS.includes(parsed.hostname)) return url;

		const target = new URL(fallback);
		parsed.protocol = target.protocol;
		parsed.host = target.host;
		return parsed.toString();
	} catch {
		return url;
	}
}

/** Fallback de produção para mídia local (dev). */
export function wpMediaProductionUrl(url?: string | null): string | undefined {
	if (!url) return undefined;

	try {
		const parsed = new URL(url);
		if (!LOCAL_WP_HOSTS.includes(parsed.hostname)) return undefined;

		const prod = new URL(PRODUCTION_WP_ORIGIN);
		parsed.protocol = prod.protocol;
		parsed.host = prod.host;
		return parsed.toString();
	} catch {
		return undefined;
	}
}

export function mediaUrlCandidates(url?: string | null): string[] {
	if (!url) return [];

	const candidates = [url];
	const production = wpMediaProductionUrl(url);
	if (production && production !== url) candidates.push(production);

	const envFallback = wpMediaUrl(url);
	if (envFallback && !candidates.includes(envFallback)) candidates.push(envFallback);

	return candidates;
}

/** Testa qual URL de imagem carrega no browser (evita erro do FastAverageColor). */
export function resolveLoadableImageUrl(url: string): Promise<string | null> {
	if (typeof window === 'undefined') return Promise.resolve(url);

	const candidates = mediaUrlCandidates(url);

	return candidates.reduce<Promise<string | null>>(
		(chain, candidate) =>
			chain.then(
				(resolved) =>
					resolved ??
					new Promise<string | null>((resolve) => {
						const img = new Image();
						img.onload = () => resolve(candidate);
						img.onerror = () => resolve(null);
						img.src = candidate;
					}),
			),
		Promise.resolve(null),
	);
}
