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
import type { LegalContent } from '../../lib/content/legal/defaults';
import { getDefaultLegalContent } from '../../lib/content/legal/defaults';
import { DEFAULT_FOOTER_EN, DEFAULT_FOOTER_PT } from '../Footer/footerDefaults';
import CookiePreferencesModal, { type CookiePrefs } from '../CookieConsent/CookiePreferencesModal';
import PolicyModalShell from './PolicyModalShell';
import { getCookie, setCookie } from '../Translate';

const CONSENT_COOKIE = 'rs_cookie_consent';

type PolicyKind = 'privacy' | 'cookies' | null;

type LegalPoliciesContextValue = {
	locale: 'en' | 'pt';
	legal: FooterLegal;
	legalContent: LegalContent;
	policyOpen: boolean;
	openPolicy: (kind: 'privacy' | 'cookies') => void;
	closePolicy: () => void;
	openCookiesPreferences: () => void;
};

const LegalPoliciesContext = createContext<LegalPoliciesContextValue | null>(null);

type LegalPoliciesProviderProps = {
	footerEn: FooterContent | null;
	footerPt: FooterContent | null;
	legalEn: LegalContent | null;
	legalPt: LegalContent | null;
	children: ReactNode;
};

function resolveLocale(pathname: string): 'en' | 'pt' {
	if (pathname.startsWith('/PT')) return 'pt';
	return getCookie('language') === 'PT' ? 'pt' : 'en';
}

function readStoredPrefs(): CookiePrefs | null {
	const raw = getCookie(CONSENT_COOKIE);
	if (!raw) return null;
	try {
		const parsed = JSON.parse(decodeURIComponent(raw)) as Partial<CookiePrefs>;
		if (!parsed || typeof parsed !== 'object') return null;
		return {
			necessary: true,
			performance: Boolean(parsed.performance),
			functional: Boolean(parsed.functional),
			marketing: Boolean(parsed.marketing),
		};
	} catch {
		if (raw === 'accepted') {
			return { necessary: true, performance: true, functional: true, marketing: true };
		}
		if (raw === 'denied') {
			return { necessary: true, performance: false, functional: false, marketing: false };
		}
		return null;
	}
}

function persistPrefs(prefs: CookiePrefs) {
	setCookie(CONSENT_COOKIE, encodeURIComponent(JSON.stringify(prefs)));
}

export function LegalPoliciesProvider({
	footerEn,
	footerPt,
	legalEn,
	legalPt,
	children,
}: LegalPoliciesProviderProps) {
	const pathname = usePathname() ?? '';
	const [locale, setLocale] = useState<'en' | 'pt'>(() =>
		pathname.startsWith('/PT') ? 'pt' : 'en',
	);
	const [openKind, setOpenKind] = useState<PolicyKind>(null);
	const [consentPrompt, setConsentPrompt] = useState(false);
	const [storedPrefs, setStoredPrefs] = useState<CookiePrefs | null>(null);

	useEffect(() => {
		setLocale(resolveLocale(pathname));
	}, [pathname]);

	useEffect(() => {
		const prefs = readStoredPrefs();
		setStoredPrefs(prefs);
		if (!prefs) {
			setConsentPrompt(true);
			setOpenKind('cookies');
		}
	}, []);

	const footerFallback = locale === 'pt' ? DEFAULT_FOOTER_PT : DEFAULT_FOOTER_EN;
	const footerFromWp = locale === 'pt' ? footerPt : footerEn;
	const legalFromWp = locale === 'pt' ? legalPt : legalEn;

	const legalContent = useMemo<LegalContent>(
		() => legalFromWp ?? getDefaultLegalContent(locale),
		[legalFromWp, locale],
	);

	const legal = useMemo<FooterLegal>(() => {
		const wpLegal = footerFromWp?.legal;
		return {
			...footerFallback.legal,
			...(wpLegal ?? {}),
			privacy: wpLegal?.privacy || legalContent.privacyTitle || footerFallback.legal.privacy,
			cookies: wpLegal?.cookies || footerFallback.legal.cookies,
			privacyBody: legalContent.privacyBody,
		};
	}, [footerFallback.legal, footerFromWp?.legal, legalContent.privacyBody, legalContent.privacyTitle]);

	const openPolicy = useCallback((kind: 'privacy' | 'cookies') => {
		setOpenKind(kind);
	}, []);

	const closePolicy = useCallback(() => {
		// Sem consentimento ainda: volta ao modal de cookies (obrigatório).
		if (!readStoredPrefs()) {
			setConsentPrompt(true);
			setOpenKind('cookies');
			return;
		}
		setOpenKind(null);
	}, []);

	const openCookiesPreferences = useCallback(() => {
		setOpenKind('cookies');
	}, []);

	const rejectAll = useCallback(() => {
		const prefs: CookiePrefs = {
			necessary: true,
			performance: false,
			functional: false,
			marketing: false,
		};
		persistPrefs(prefs);
		setStoredPrefs(prefs);
		setConsentPrompt(false);
		setOpenKind(null);
	}, []);

	const submitPrefs = useCallback((prefs: CookiePrefs) => {
		const next = { ...prefs, necessary: true };
		persistPrefs(next);
		setStoredPrefs(next);
		setConsentPrompt(false);
		setOpenKind(null);
	}, []);

	const value = useMemo(
		() => ({
			locale,
			legal,
			legalContent,
			policyOpen: openKind !== null,
			openPolicy,
			closePolicy,
			openCookiesPreferences,
		}),
		[locale, legal, legalContent, openKind, openPolicy, closePolicy, openCookiesPreferences],
	);

	const isPt = locale === 'pt';
	const showCookiesModal = openKind === 'cookies';
	const showPrivacyModal = openKind === 'privacy';

	return (
		<LegalPoliciesContext.Provider value={value}>
			{children}

			<CookiePreferencesModal
				open={showCookiesModal}
				content={legalContent}
				initialPrefs={storedPrefs}
				dismissible={!(consentPrompt && !storedPrefs)}
				onClose={() => {
					if (consentPrompt && !readStoredPrefs()) return;
					setOpenKind(null);
				}}
				onRejectAll={rejectAll}
				onSubmit={submitPrefs}
			/>

			<PolicyModalShell
				open={showPrivacyModal}
				onClose={closePolicy}
				label={legalContent.privacyTitle}
				panelClassName="cookie-prefs-modal-panel--privacy"
			>
				<div className="cookie-prefs-modal-header">
					<h2 className="cookie-prefs-modal-title font-hk">{legalContent.privacyTitle}</h2>
					<button
						type="button"
						className="cookie-prefs-modal-close custom-cursor-target"
						onClick={closePolicy}
						aria-label={isPt ? 'Fechar' : 'Close'}
					>
						×
					</button>
				</div>
				<div
					className="cookie-prefs-privacy-body font-hk"
					dangerouslySetInnerHTML={{ __html: legalContent.privacyBody }}
				/>
				<div className="cookie-prefs-modal-actions">
					<button
						type="button"
						className="cookie-prefs-btn cookie-prefs-btn--solid font-hk"
						onClick={closePolicy}
					>
						{isPt ? 'Fechar' : 'Close'}
					</button>
				</div>
			</PolicyModalShell>
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
