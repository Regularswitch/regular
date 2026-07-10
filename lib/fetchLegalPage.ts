import { GetApi } from '../components/ApiWp';
import { getBaseUrl } from './getBaseUrl';

const LEGAL_SLUGS = new Set(['privacy-policy', 'cookies-policy']);

export function isLegalPageSlug(slug: string) {
	return LEGAL_SLUGS.has(slug);
}

export async function fetchLegalPage(slug: string, locale: 'en' | 'pt') {
	const query: Record<string, string | number> = { _embed: '' };
	if (locale === 'pt') query.translate = 'PT';

	if (locale === 'en') {
		const pages = await GetApi('/pages', { slug, ...query });
		return pages[0] ?? null;
	}

	const base = getBaseUrl();
	const pages = await fetch(`${base}/api/${slug}`, {
		headers: { Cookie: 'language=PT' },
	}).then((r) => r.json() as Promise<Array<{ title?: string; content?: string }>>);

	return pages[0] ?? null;
}
