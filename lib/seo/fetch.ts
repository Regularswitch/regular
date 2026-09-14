import type { SeoContent } from '../../types';
import {
	normalizeSeo,
	type SeoLocale,
	type SeoPostType,
} from './metadata';

function localeQuery(locale: SeoLocale): Record<string, string> {
	return locale === 'pt' ? { translate: 'PT' } : {};
}

/**
 * Lê `seo_data` do primeiro post do CPT (páginas singulares do CMS).
 */
export async function fetchSectionSeo(
	postType: Exclude<SeoPostType, 'project'>,
	locale: SeoLocale,
): Promise<SeoContent> {
	const api = process.env?.API;
	if (!api) return {};

	try {
		const url = new URL(`${api}/wp-json/wp/v2/${postType}`);
		url.search = new URLSearchParams({
			per_page: '1',
			...localeQuery(locale),
		}).toString();

		const response = await fetch(url, { cache: 'no-store' });
		if (!response.ok) return {};

		const payload = await response.json();
		if (!Array.isArray(payload) || !payload[0]) return {};

		return normalizeSeo((payload[0] as { seo_data?: unknown }).seo_data);
	} catch {
		return {};
	}
}

/**
 * SEO de um projeto pelo slug (respeita translate=PT).
 */
export async function fetchProjectSeo(
	slug: string,
	locale: SeoLocale,
): Promise<SeoContent & { projectTitle?: string }> {
	const api = process.env?.API;
	if (!api || !slug) return {};

	try {
		const url = new URL(`${api}/wp-json/wp/v2/project`);
		url.search = new URLSearchParams({
			slug,
			per_page: '1',
			...localeQuery(locale),
		}).toString();

		const response = await fetch(url, { cache: 'no-store' });
		if (!response.ok) return {};

		const payload = await response.json();
		if (!Array.isArray(payload) || !payload[0]) return {};

		const item = payload[0] as {
			seo_data?: unknown;
			title?: { rendered?: string };
		};
		const seo = normalizeSeo(item.seo_data);
		const projectTitle =
			typeof item.title?.rendered === 'string'
				? item.title.rendered.replace(/<[^>]+>/g, '').trim()
				: '';

		return {
			...seo,
			...(projectTitle ? { projectTitle } : {}),
		};
	} catch {
		return {};
	}
}

/** Fallback de title/description por página (quando CMS está vazio). */
export function sectionSeoFallbacks(
	section: Exclude<SeoPostType, 'project'>,
	locale: SeoLocale,
): { title: string; description?: string } {
	const isPt = locale === 'pt';

	switch (section) {
		case 'intro':
			return {
				title: isPt
					? 'RegularSwitch | Estúdio de Design e Branding em São Paulo'
					: 'RegularSwitch | Design & Branding Studio',
				description: isPt
					? 'Estúdio de design franco-brasileiro em São Paulo, fundado em 2013. Estratégia de marca, identidade visual, branding e design generativo para marcas e instituições.'
					: 'Franco-Brazilian design studio based in São Paulo, founded in 2013. Brand strategy, visual identity, branding and generative design.',
			};
		case 'capabilities':
			return {
				title: isPt
					? 'Capacidades | Branding, Identidade Visual e Design Digital'
					: 'Capabilities | Branding, Visual Identity & Digital Design',
			};
		case 'education':
			return {
				title: isPt
					? 'Workshops e Mentorias de Design | RegularSwitch'
					: 'Design Workshops & Mentoring | RegularSwitch',
			};
		case 'about':
			return {
				title: isPt
					? 'Sobre | Estúdio de Design Franco-Brasileiro desde 2013'
					: 'About | Franco-Brazilian Design Studio since 2013',
			};
		case 'contact':
			return {
				title: isPt ? 'Contato | RegularSwitch' : 'Contact | RegularSwitch',
			};
		case 'projects-page':
			return {
				title: isPt
					? 'Projetos | Branding, Identidade Visual e Design Cultural'
					: 'Projects | Branding, Visual Identity & Cultural Design',
			};
		default:
			return { title: 'RegularSwitch' };
	}
}
