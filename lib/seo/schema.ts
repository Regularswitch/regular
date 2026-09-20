import type { SeoContent } from '../../types';
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

const FALLBACK_JSON_LD_EN: Record<string, unknown> = {
	'@context': 'https://schema.org',
	'@type': 'ProfessionalService',
	name: 'RegularSwitch',
	description:
		'Franco-Brazilian design studio based in São Paulo, founded in 2013. Brand strategy, visual identity, branding and generative design for brands and cultural institutions.',
	foundingDate: '2013',
	url: 'https://regularswitch.com.br',
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

export function resolveOrgJsonLd(
	schema: SeoOrgSchemaResponse | null,
	locale: SeoLocale = 'en',
): Record<string, unknown> {
	if (locale === 'pt') {
		return (schema?.jsonLdPt as Record<string, unknown>) ?? FALLBACK_JSON_LD_PT;
	}
	return (schema?.jsonLdEn as Record<string, unknown>) ?? FALLBACK_JSON_LD_EN;
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

	return {
		'@context': 'https://schema.org',
		'@type': 'CreativeWork',
		name,
		...(description ? { description } : {}),
		...(input.url ? { url: input.url } : {}),
		...(input.dateCreated ? { dateCreated: input.dateCreated } : {}),
		creator: {
			'@type': 'Organization',
			name: 'RegularSwitch',
		},
	};
}
