'use client';

import {
	createContext,
	useCallback,
	useContext,
	useEffect,
	useMemo,
	useState,
	type ReactNode,
} from 'react';
import { usePathname } from 'next/navigation';

import type { FooterContent, FooterLegal } from '../../types';
import { DEFAULT_FOOTER_EN, DEFAULT_FOOTER_PT } from '../Footer/footerDefaults';
import { getCookie } from '../Translate';

type PolicyKind = 'privacy' | 'cookies';

type LegalPoliciesContextValue = {
	locale: 'en' | 'pt';
	legal: FooterLegal;
	policyOpen: boolean;
	openPolicy: (kind: PolicyKind) => void;
	closePolicy: () => void;
};

const LegalPoliciesContext = createContext<LegalPoliciesContextValue | null>(null);

type LegalPoliciesProviderProps = {
	footerEn: FooterContent | null;
	footerPt: FooterContent | null;
	children: ReactNode;
};

function resolveLocale(pathname: string): 'en' | 'pt' {
	if (pathname.startsWith('/PT')) return 'pt';
	return getCookie('language') === 'PT' ? 'pt' : 'en';
}

export function LegalPoliciesProvider({
	footerEn,
	footerPt,
	children,
}: LegalPoliciesProviderProps) {
	const pathname = usePathname() ?? '';
	const [locale, setLocale] = useState<'en' | 'pt'>(() =>
		pathname.startsWith('/PT') ? 'pt' : 'en',
	);
	const [openKind, setOpenKind] = useState<PolicyKind | null>(null);

	useEffect(() => {
		setLocale(resolveLocale(pathname));
	}, [pathname]);

	const fallback = locale === 'pt' ? DEFAULT_FOOTER_PT : DEFAULT_FOOTER_EN;
	const fromWp = locale === 'pt' ? footerPt : footerEn;

	const legal = useMemo<FooterLegal>(() => {
		const wpLegal = fromWp?.legal;
		return {
			...fallback.legal,
			...(wpLegal ?? {}),
			privacyBody: wpLegal?.privacyBody?.trim() || fallback.legal.privacyBody || '',
			cookiesBody: wpLegal?.cookiesBody?.trim() || fallback.legal.cookiesBody || '',
		};
	}, [fallback.legal, fromWp?.legal]);

	const openPolicy = useCallback((kind: PolicyKind) => setOpenKind(kind), []);
	const closePolicy = useCallback(() => setOpenKind(null), []);

	const value = useMemo(
		() => ({
			locale,
			legal,
			policyOpen: openKind !== null,
			openPolicy,
			closePolicy,
		}),
		[locale, legal, openKind, openPolicy, closePolicy],
	);

	const isPt = locale === 'pt';
	const title = openKind === 'privacy' ? legal.privacy : legal.cookies;
	const bodyHtml =
		openKind === 'privacy'
			? (legal.privacyBody || '').trim()
			: (legal.cookiesBody || '').trim();

	return (
		<LegalPoliciesContext.Provider value={value}>
			{children}
			{openKind && bodyHtml ? (
				<div
					className="cookie-consent cookie-consent--policy"
					role="dialog"
					aria-modal="true"
					aria-label={title}
				>
					<div className="cookie-consent-inner">
						<div className="cookie-consent-policy-header">
							<p className="cookie-consent-policy-title font-hk">{title}</p>
							<button
								type="button"
								className="cookie-consent-policy-close custom-cursor-target"
								onClick={closePolicy}
								aria-label={isPt ? 'Fechar' : 'Close'}
							>
								×
							</button>
						</div>
						<div
							className="cookie-consent-policy-body font-hk"
							dangerouslySetInnerHTML={{ __html: bodyHtml }}
						/>
						<button
							type="button"
							className="cookie-consent-btn font-hk"
							onClick={closePolicy}
						>
							{isPt ? 'Fechar' : 'Close'}
						</button>
					</div>
				</div>
			) : null}
		</LegalPoliciesContext.Provider>
	);
}

export function useLegalPolicies(): LegalPoliciesContextValue {
	const ctx = useContext(LegalPoliciesContext);
	if (!ctx) {
		throw new Error('useLegalPolicies must be used within LegalPoliciesProvider');
	}
	return ctx;
}
