'use client';

import {
	createContext,
	useContext,
	useEffect,
	useMemo,
	useState,
	type ReactNode,
} from 'react';

import type { FooterContent, FooterSocialLink } from '../../types';

type FooterSocialContextValue = {
	links: FooterSocialLink[];
};

const FooterSocialContext = createContext<FooterSocialContextValue>({ links: [] });

function pickSocialLinks(
	footerEn: FooterContent | null,
	footerPt: FooterContent | null,
): FooterSocialLink[] {
	for (const fromWp of [footerEn, footerPt]) {
		if (!fromWp || !Array.isArray(fromWp.socialLinks)) continue;
		const links = fromWp.socialLinks.filter((item) => Boolean(item.href?.trim()));
		if (links.length > 0) return links;
	}
	return [];
}

type FooterSocialProviderProps = {
	footerEn: FooterContent | null;
	footerPt: FooterContent | null;
	children: ReactNode;
};

/**
 * Social é compartilhado (EN=PT). Props do layout + refresh via /api/footer
 * para não ficar preso a cache/RSC com Instagram/LinkedIn antigos.
 */
export function FooterSocialProvider({ footerEn, footerPt, children }: FooterSocialProviderProps) {
	const initial = useMemo(() => pickSocialLinks(footerEn, footerPt), [footerEn, footerPt]);
	const [links, setLinks] = useState<FooterSocialLink[]>(initial);

	useEffect(() => {
		setLinks(initial);
	}, [initial]);

	useEffect(() => {
		let cancelled = false;
		(async () => {
			try {
				const res = await fetch('/api/footer', { cache: 'no-store' });
				if (!res.ok) return;
				const data = (await res.json()) as FooterContent | null;
				if (cancelled || !data || !Array.isArray(data.socialLinks)) return;
				setLinks(data.socialLinks.filter((item) => Boolean(item.href?.trim())));
			} catch {
				/* mantém props do layout */
			}
		})();
		return () => {
			cancelled = true;
		};
	}, []);

	const value = useMemo(() => ({ links }), [links]);
	return <FooterSocialContext.Provider value={value}>{children}</FooterSocialContext.Provider>;
}

export function useFooterSocialLinks(): FooterSocialLink[] {
	return useContext(FooterSocialContext).links;
}
