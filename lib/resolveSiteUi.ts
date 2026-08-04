import { getDefaultSiteUiContent, normalizeSiteUiLayout } from './siteUiDefaults';
import type { SiteUiContent, SiteUiLabels, SiteUiLocale, SiteUiNavLink } from '../types';

function mergeLabels(defaults: SiteUiLabels, fromWp?: Partial<SiteUiLabels>): SiteUiLabels {
	const merged = { ...defaults };
	if (!fromWp) return merged;

	for (const key of Object.keys(defaults) as Array<keyof SiteUiLabels>) {
		const value = fromWp[key]?.trim();
		if (value) merged[key] = value;
	}

	return merged;
}

function mergeNav(defaults: SiteUiNavLink[], fromWp?: SiteUiNavLink[]): SiteUiNavLink[] {
	if (!fromWp?.length) return defaults;
	return fromWp.filter((item) => item.label && item.href);
}

function mergeLocale(defaults: SiteUiLocale, fromWp?: Partial<SiteUiLocale>): SiteUiLocale {
	return {
		labels: mergeLabels(defaults.labels, fromWp?.labels),
		nav: mergeNav(defaults.nav, fromWp?.nav),
	};
}

export function buildSiteUiContent(fromWp: SiteUiContent | null | undefined): SiteUiContent {
	const defaults = getDefaultSiteUiContent();

	if (!fromWp) return defaults;

	return {
		en: mergeLocale(defaults.en, fromWp.en),
		pt: mergeLocale(defaults.pt, fromWp.pt),
		layout: normalizeSiteUiLayout(fromWp.layout ?? defaults.layout),
	};
}

export type HeaderNavContent = {
	en: SiteUiNavLink[];
	pt: SiteUiNavLink[];
};

export function buildSiteUiWithHeaderNav(
	siteUi: SiteUiContent | null | undefined,
	headerNav: HeaderNavContent | null | undefined,
): SiteUiContent {
	const base = buildSiteUiContent(siteUi);

	if (!headerNav) return base;

	return {
		en: {
			labels: base.en.labels,
			nav: headerNav.en.length > 0 ? headerNav.en : base.en.nav,
		},
		pt: {
			labels: base.pt.labels,
			nav: headerNav.pt.length > 0 ? headerNav.pt : base.pt.nav,
		},
		layout: base.layout,
	};
}

export function resolveSiteUi(siteUi: SiteUiContent, locale: 'en' | 'pt'): SiteUiLocale {
	return siteUi[locale];
}

export function withLocalePrefix(href: string, locale: 'en' | 'pt') {
	if (href.startsWith('http') || href.startsWith('mailto:') || href.startsWith('tel:')) return href;
	const normalized = href.startsWith('/') ? href : `/${href}`;
	const prefix = locale === 'pt' ? '/PT' : '';
	return `${prefix}${normalized}`.replace(/^\/\//, '/') || normalized;
}
