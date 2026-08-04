'use client';

import { createContext, useContext, useEffect, useMemo, useState } from 'react';
import { usePathname } from 'next/navigation';

import { buildSiteUiContent, resolveSiteUi } from '../../lib/resolveSiteUi';
import { getDefaultSiteUiContent, normalizeSiteUiLayout } from '../../lib/siteUiDefaults';
import type { SiteUiContent, SiteUiLayout, SiteUiLocale } from '../../types';
import { getCookie } from '../Translate';

type SiteUiProviderProps = {
	siteUi: SiteUiContent | null;
	children: React.ReactNode;
};

const SiteUiContext = createContext<SiteUiContent>(getDefaultSiteUiContent());

export function SiteUiProvider({ siteUi, children }: SiteUiProviderProps) {
	const value = useMemo(() => buildSiteUiContent(siteUi), [siteUi]);
	return <SiteUiContext.Provider value={value}>{children}</SiteUiContext.Provider>;
}

export function useSiteUiLayout(): SiteUiLayout {
	const siteUi = useContext(SiteUiContext);
	return useMemo(() => normalizeSiteUiLayout(siteUi.layout), [siteUi.layout]);
}

function localeFromPathname(pathname: string): 'en' | 'pt' {
	return pathname.startsWith('/PT') ? 'pt' : 'en';
}

function localeFromPathnameAndCookie(pathname: string): 'en' | 'pt' {
	if (pathname.startsWith('/PT')) return 'pt';
	return getCookie('language') === 'PT' ? 'pt' : 'en';
}

export function useSiteUi(): SiteUiLocale {
	const pathname = usePathname() ?? '';
	const siteUi = useContext(SiteUiContext);
	const [locale, setLocale] = useState<'en' | 'pt'>(() => localeFromPathname(pathname));

	useEffect(() => {
		setLocale(localeFromPathnameAndCookie(pathname));
	}, [pathname]);

	return useMemo(() => resolveSiteUi(siteUi, locale), [siteUi, locale]);
}

export function useSiteUiLocale(locale: 'en' | 'pt'): SiteUiLocale {
	const siteUi = useContext(SiteUiContext);
	return useMemo(() => resolveSiteUi(siteUi, locale), [siteUi, locale]);
}
