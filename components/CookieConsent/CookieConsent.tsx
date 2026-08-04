'use client';

import Link from 'next/link';
import { useEffect, useState } from 'react';

import { COOKIES_POLICY_PAGE_SLUG, PRIVACY_POLICY_PAGE_SLUG, pagePath } from '../../lib/pageSlugs';
import { withLocalePrefix } from '../../lib/resolveSiteUi';
import { getCookie, setCookie } from '../Translate';

const CONSENT_COOKIE = 'rs_cookie_consent';

export default function CookieConsent() {
	const [visible, setVisible] = useState(false);
	const [locale, setLocale] = useState<'en' | 'pt'>('en');

	useEffect(() => {
		const lang = getCookie('language') === 'PT' ? 'pt' : 'en';
		setLocale(lang);
		if (!getCookie(CONSENT_COOKIE)) {
			setVisible(true);
		}
	}, []);

	if (!visible) return null;

	const isPt = locale === 'pt';
	const privacyHref = withLocalePrefix(pagePath(PRIVACY_POLICY_PAGE_SLUG), locale);
	const cookiesHref = withLocalePrefix(pagePath(COOKIES_POLICY_PAGE_SLUG), locale);

	const accept = () => {
		setCookie(CONSENT_COOKIE, '1');
		setVisible(false);
	};

	return (
		<div className="cookie-consent" role="dialog" aria-live="polite" aria-label="Cookies">
			<div className="cookie-consent-inner">
				<p className="cookie-consent-text font-hk">
					{isPt
						? 'Usamos cookies para melhorar a experiência. Ao continuar, você concorda com nossa '
						: 'We use cookies to improve your experience. By continuing, you agree to our '}
					<Link href={privacyHref} className="cookie-consent-link">
						{isPt ? 'Política de Privacidade' : 'Privacy Policy'}
					</Link>
					{isPt ? ' e ' : ' and '}
					<Link href={cookiesHref} className="cookie-consent-link">
						{isPt ? 'Política de Cookies' : 'Cookies Policy'}
					</Link>
					{isPt ? ' (LGPD).' : ' (LGPD).'}
				</p>
				<button type="button" className="cookie-consent-btn font-hk" onClick={accept}>
					{isPt ? 'Aceitar' : 'Accept'}
				</button>
			</div>
		</div>
	);
}
