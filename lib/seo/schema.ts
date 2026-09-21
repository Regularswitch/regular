import type { SeoContent } from '../../types';
import { CANONICAL_PRODUCTION_URL, getBaseUrl } from '../config/getBaseUrl';
import type { SeoLocale } from './metadata';

export type SeoOrgSchemaFields = {
	name: string;
	descriptionEn: string;
	descriptionPt: string;
	foundingDate: string;
	url: string;
	telephone: string;
	email: string;
	locality: string;
	region: string;
	country: string;
	areaServed: string;
	knowsAbout: string;
	sameAs: string;
};

export type SeoOrgSchemaResponse = {
	fields: SeoOrgSchemaFields;
	jsonLdEn: Record<string, unknown>;
	jsonLdPt: Record<string, unknown>;
};

const ORG_LOGO_PATH = '/logo-blanc.svg';

function orgLogoUrl(base = getBaseUrl()): string {
	return `${base.replace(/\/$/, '')}${ORG_LOGO_PATH}`;
}

const FALLBACK_JSON_LD_EN: Record<string, unknown> = {
	'@context': 'https://schema.org',
	'@type': 'ProfessionalService',
	name: 'RegularSwitch',
	description:
		'Franco-Brazilian design studio based in São Paulo, founded in 2013. Brand strategy, visual identity, branding and generative design for brands and cultural institutions.',
	foundingDate: '2013',
	url: CANONICAL_PRODUCTION_URL,
	telephone: '+5511945408448',
	email: 'contact@regularswitch.com',
	address: {
		'@type': 'PostalAddress',
		addressLocality: 'São Paulo',
		addressRegion: 'SP',
		addressCountry: 'BR',
	},
	areaServed: ['BR', 'FR'],
	knowsAbout: [
		'Visual identity',
		'Branding',
		'Rebranding',
		'Generative design',
		'Editorial design',
		'Exhibition design',
		'Digital experiences',
	],
	sameAs: [
		'https://www.instagram.com/regular.switch/',
		'https://www.behance.net/regular-switch',
	],
};

const FALLBACK_JSON_LD_PT: Record<string, unknown> = {
	...FALLBACK_JSON_LD_EN,
	description:
		'Estúdio de design franco-brasileiro em São Paulo, fundado em 2013. Estratégia de marca, identidade visual, branding e design generativo para marcas e instituições.',
	knowsAbout: [
		'Identidade visual',
		'Branding',
		'Rebranding',
		'Design generativo',
		'Design editorial',
		'Expografia',
		'Experiências digitais',
		'Plataforma de marca',
	],
};

export async function fetchSeoOrgSchema(): Promise<SeoOrgSchemaResponse | null> {
	const api = process.env?.API;
	if (!api) return null;

	try {
		const response = await fetch(`${api}/wp-json/rs/v1/seo-schema`, { cache: 'no-store' });
		if (!response.ok) return null;
		const payload = await response.json();
		if (!payload || typeof payload !== 'object') return null;
		return payload as SeoOrgSchemaResponse;
	} catch {
		return null;
	}
}

/** Garante Organization explícita + url canônica + logo (checagens AEO). */
export function enrichOrgJsonLd(
	data: Record<string, unknown>,
	base = getBaseUrl(),
): Record<string, unknown> {
	const logo = orgLogoUrl(base);
	let url =
		typeof data.url === 'string' && data.url.trim() && !/\.vercel\.app/i.test(data.url)
			? data.url.replace(/\/$/, '')
			: base;

	// Preferir domínio canônico do site (não .com.br legado do CMS).
	if (/regularswitch\.com\.br/i.test(url)) {
		url = base;
	}

	const existingType = data['@type'];
	const types = new Set<string>(['Organization', 'ProfessionalService']);
	if (typeof existingType === 'string') types.add(existingType);
	if (Array.isArray(existingType)) {
		for (const t of existingType) {
			if (typeof t === 'string') types.add(t);
		}
	}

	return {
		...data,
		'@context': 'https://schema.org',
		'@type': [...types],
		'@id': `${base}/#organization`,
		name: typeof data.name === 'string' && data.name.trim() ? data.name : 'RegularSwitch',
		url,
		logo: {
			'@type': 'ImageObject',
			url: logo,
		},
		image: logo,
	};
}

export function resolveOrgJsonLd(
	schema: SeoOrgSchemaResponse | null,
	locale: SeoLocale = 'en',
): Record<string, unknown> {
	const raw =
		locale === 'pt'
			? ((schema?.jsonLdPt as Record<string, unknown>) ?? FALLBACK_JSON_LD_PT)
			: ((schema?.jsonLdEn as Record<string, unknown>) ?? FALLBACK_JSON_LD_EN);

	return enrichOrgJsonLd(raw);
}

/**
 * Organization com `@type` string literal.
 * aeo.js remote check: `s["@type"] === "Organization"` (array não passa).
 */
export function buildOrganizationJsonLd(
	schema: SeoOrgSchemaResponse | null = null,
	locale: SeoLocale = 'en',
): Record<string, unknown> {
	const enriched = resolveOrgJsonLd(schema, locale);
	return {
		...enriched,
		'@type': 'Organization',
	};
}

export function buildWebSiteJsonLd(locale: SeoLocale = 'en'): Record<string, unknown> {
	const base = getBaseUrl();
	const isPt = locale === 'pt';
	const pageUrl = isPt ? `${base}/PT` : base;

	return {
		'@context': 'https://schema.org',
		'@type': 'WebSite',
		'@id': `${base}/#website`,
		name: 'RegularSwitch',
		url: pageUrl,
		inLanguage: isPt ? 'pt-BR' : 'en',
		publisher: { '@id': `${base}/#organization` },
	};
}

export function buildWebPageJsonLd(locale: SeoLocale = 'en', path = '/'): Record<string, unknown> {
	const base = getBaseUrl();
	const url = path.startsWith('http') ? path : `${base}${path === '/' ? '' : path}`;
	const isPt = locale === 'pt';

	return {
		'@context': 'https://schema.org',
		'@type': 'WebPage',
		'@id': `${url}#webpage`,
		url,
		name: 'RegularSwitch',
		inLanguage: isPt ? 'pt-BR' : 'en',
		isPartOf: { '@id': `${base}/#website` },
		about: { '@id': `${base}/#organization` },
		publisher: { '@id': `${base}/#organization` },
	};
}

/** FAQ institucional para AEO (home / site-wide). */
export function buildStudioFaqJsonLd(locale: SeoLocale = 'en'): Record<string, unknown> {
	const base = getBaseUrl();
	const isPt = locale === 'pt';
	const pageUrl = isPt ? `${base}/PT` : base;

	const items = isPt
		? [
				{
					question: 'O que é a RegularSwitch?',
					answer:
						'A RegularSwitch é um estúdio de design franco-brasileiro em São Paulo, fundado em 2013. Atua em estratégia de marca, identidade visual, branding e design generativo para marcas e instituições culturais.',
				},
				{
					question: 'Onde fica a RegularSwitch?',
					answer:
						'O estúdio fica em São Paulo, Brasil, e mantém presença também em Paris. Contato: contact@regularswitch.com.',
				},
				{
					question: 'Quais serviços a RegularSwitch oferece?',
					answer:
						'Identidade visual, branding, rebranding, design generativo, design editorial, expografia e experiências digitais.',
				},
			]
		: [
				{
					question: 'What is RegularSwitch?',
					answer:
						'RegularSwitch is a Franco-Brazilian design studio based in São Paulo, founded in 2013. We work on brand strategy, visual identity, branding and generative design for brands and cultural institutions.',
				},
				{
					question: 'Where is RegularSwitch located?',
					answer:
						'The studio is based in São Paulo, Brazil, with a presence in Paris. Contact: contact@regularswitch.com.',
				},
				{
					question: 'What services does RegularSwitch offer?',
					answer:
						'Visual identity, branding, rebranding, generative design, editorial design, exhibition design and digital experiences.',
				},
			];

	return {
		'@context': 'https://schema.org',
		'@type': 'FAQPage',
		'@id': `${pageUrl}#faq`,
		url: pageUrl,
		mainEntity: items.map((item) => ({
			'@type': 'Question',
			name: item.question,
			acceptedAnswer: {
				'@type': 'Answer',
				text: item.answer,
			},
		})),
	};
}

export function buildFaqPageJsonLd(
	faq: Array<{ question: string; answer: string }> | undefined,
	pageUrl?: string,
): Record<string, unknown> | null {
	const items = (faq ?? []).filter((item) => item.question?.trim() && item.answer?.trim());
	if (!items.length) return null;

	return {
		'@context': 'https://schema.org',
		'@type': 'FAQPage',
		...(pageUrl ? { url: pageUrl } : {}),
		mainEntity: items.map((item) => ({
			'@type': 'Question',
			name: item.question.trim(),
			acceptedAnswer: {
				'@type': 'Answer',
				text: item.answer.replace(/<[^>]+>/g, ' ').replace(/\s+/g, ' ').trim(),
			},
		})),
	};
}

export function buildCreativeWorkJsonLd(input: {
	name: string;
	description?: string;
	url?: string;
	dateCreated?: string;
	seo?: SeoContent | null;
}): Record<string, unknown> {
	const name = input.seo?.title?.trim() || input.name;
	const description = input.seo?.description?.trim() || input.description?.trim() || undefined;
	const base = getBaseUrl();

	return {
		'@context': 'https://schema.org',
		'@type': 'CreativeWork',
		name,
		...(description ? { description } : {}),
		...(input.url
			? { url: input.url.startsWith('http') ? input.url : `${base}${input.url}` }
			: {}),
		...(input.dateCreated ? { dateCreated: input.dateCreated } : {}),
		creator: {
			'@type': 'Organization',
			name: 'RegularSwitch',
			url: base,
		},
	};
}
