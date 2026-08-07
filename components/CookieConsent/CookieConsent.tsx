'use client';

import { useEffect, useState } from 'react';

import { useLegalPolicies } from '../Legal/LegalPoliciesProvider';
import { getCookie, setCookie } from '../Translate';

const CONSENT_COOKIE = 'rs_cookie_consent';

export default function CookieConsent() {
	const [visible, setVisible] = useState(false);
	const { locale, legal, openPolicy, policyOpen } = useLegalPolicies();
	const isPt = locale === 'pt';

	useEffect(() => {
		const choice = getCookie(CONSENT_COOKIE);
		// Só abre se ainda não aceitou nem negou.
		if (!choice) {
			setVisible(true);
		}
	}, []);

	const accept = () => {
		setCookie(CONSENT_COOKIE, 'accepted');
		setVisible(false);
	};

	const decline = () => {
		setCookie(CONSENT_COOKIE, 'denied');
		setVisible(false);
	};

	if (!visible || policyOpen) return null;

	return (
		<div className="cookie-consent" role="dialog" aria-live="polite" aria-label="Cookies">
			<div className="cookie-consent-inner">
				<p className="cookie-consent-text font-hk">
					{isPt
						? 'Usamos cookies para melhorar a experiência. Ao continuar, você concorda com nossa '
						: 'We use cookies to improve your experience. By continuing, you agree to our '}
					<button
						type="button"
						className="cookie-consent-link"
						onClick={() => openPolicy('privacy')}
					>
						{legal.privacy}
					</button>
					{isPt ? ' e ' : ' and '}
					<button
						type="button"
						className="cookie-consent-link"
						onClick={() => openPolicy('cookies')}
					>
						{legal.cookies}
					</button>
					{isPt ? ' (LGPD).' : ' (LGPD).'}
				</p>
				<div className="cookie-consent-actions">
					<button type="button" className="cookie-consent-btn font-hk" onClick={accept}>
						{isPt ? 'Aceitar' : 'Accept'}
					</button>
					<button
						type="button"
						className="cookie-consent-btn cookie-consent-btn--ghost font-hk"
						onClick={decline}
					>
						{isPt ? 'Negar' : 'Decline'}
					</button>
				</div>
			</div>
		</div>
	);
}
