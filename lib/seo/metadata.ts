import type { Metadata } from 'next';

import type { SeoContent } from '../../types';
import { localePathPair } from './localePaths';

export const DEFAULT_SITE_NAME = 'RegularSwitch';

export type SeoLocale = 'en' | 'pt';

/** CPT WordPress que expõem `seo_data`. */
export type SeoPostType =
	| 'intro'
	| 'about'
	| 'capabilities'
	| 'education'
	| 'contact'
	| 'projects-page'
	| 'project';

export function normalizeSeo(value: unknown): SeoContent {
	if (!value || typeof value !== 'object') return {};
	const item = value as Record<string, unknown>;
	const title = typeof item.title === 'string' ? item.title.trim() : '';
	const description = typeof item.description === 'string' ? item.description.trim() : '';
	return {
		...(title ? { title } : {}),
		...(description ? { description } : {}),
	};
}

export function buildPageMetadata(
	seo: SeoContent | null | undefined,
	options: {
		fallbackTitle: string;
		fallbackDescription?: string;
		locale?: SeoLocale;
		path?: string;
	},
): Metadata {
	const title = seo?.title?.trim() || options.fallbackTitle;
	const description = seo?.description?.trim() || options.fallbackDescription?.trim() || undefined;
	const locale = options.locale ?? 'en';

	const pathPair = options.path ? localePathPair(options.path) : null;
	const canonical = pathPair ? (locale === 'pt' ? pathPair.pt : pathPair.en) : undefined;

	const metadata: Metadata = {
		title,
		...(description ? { description } : {}),
		...(pathPair && canonical
			? {
					alternates: {
						canonical,
						languages: {
							en: pathPair.en,
							'pt-BR': pathPair.pt,
							'x-default': pathPair.en,
						},
					},
				}
			: {}),
		openGraph: {
			title,
			...(description ? { description } : {}),
			locale: locale === 'pt' ? 'pt_BR' : 'en_US',
			siteName: DEFAULT_SITE_NAME,
			type: 'website',
			...(canonical ? { url: canonical } : {}),
		},
		twitter: {
			card: 'summary_large_image',
			title,
			...(description ? { description } : {}),
		},
	};

	return metadata;
}
