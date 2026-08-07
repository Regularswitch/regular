'use client';

import { useEffect, useState } from 'react';

import type { FooterContent } from '../../types';
import { DEFAULT_FOOTER_EN, DEFAULT_FOOTER_PT } from '../Footer/footerDefaults';
import LegalPolicyModal from '../Legal/LegalPolicyModal';
import { getCookie, setCookie } from '../Translate';

const CONSENT_COOKIE = 'rs_cookie_consent';

type CookieConsentProps = {
	footerEn?: FooterContent | null;
	footerPt?: FooterContent | null;
};

export default function CookieConsent({ footerEn = null, footerPt = null }: CookieConsentProps) {
	const [visible, setVisible] = useState(false);
	const [locale, setLocale] = useState<'en' | 'pt'>('en');
	const [modal, setModal] = useState<'privacy' | 'cookies' | null>(null);

	useEffect(() => {
		const lang = getCookie('language') === 'PT' ? 'pt' : 'en';
		setLocale(lang);
		if (!getCookie(CONSENT_COOKIE)) {
			setVisible(true);
		}
	}, []);

	const fallback = locale === 'pt' ? DEFAULT_FOOTER_PT : DEFAULT_FOOTER_EN;
	const fromWp = locale === 'pt' ? footerPt : footerEn;
	const legal = fromWp?.legal ?? fallback.legal;
	const isPt = locale === 'pt';

	const privacyBody = (legal.privacyBody?.trim() || fallback.legal.privacyBody || '').trim();
	const cookiesBody = (legal.cookiesBody?.trim() || fallback.legal.cookiesBody || '').trim();

	const accept = () => {
		setCookie(CONSENT_COOKIE, '1');
		setVisible(false);
	};

	if (!visible && !modal) return null;

	return (
		<>
			{visible ? (
				<div className="cookie-consent" role="dialog" aria-live="polite" aria-label="Cookies">
					<div className="cookie-consent-inner">
						<p className="cookie-consent-text font-hk">
							{isPt
								? 'Usamos cookies para melhorar a experiência. Ao continuar, você concorda com nossa '
								: 'We use cookies to improve your experience. By continuing, you agree to our '}
							<button
								type="button"
								className="cookie-consent-link"
								onClick={() => setModal('privacy')}
							>
								{legal.privacy}
							</button>
							{isPt ? ' e ' : ' and '}
							<button
								type="button"
								className="cookie-consent-link"
								onClick={() => setModal('cookies')}
							>
								{legal.cookies}
							</button>
							{isPt ? ' (LGPD).' : ' (LGPD).'}
						</p>
						<button type="button" className="cookie-consent-btn font-hk" onClick={accept}>
							{isPt ? 'Aceitar' : 'Accept'}
						</button>
					</div>
				</div>
			) : null}

			<LegalPolicyModal
				open={modal !== null}
				title={modal === 'privacy' ? legal.privacy : legal.cookies}
				bodyHtml={modal === 'privacy' ? privacyBody : cookiesBody}
				onClose={() => setModal(null)}
				closeLabel={isPt ? 'Fechar' : 'Close'}
			/>
		</>
	);
}
