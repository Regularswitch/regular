import type { FooterContent } from '../../types';
import {
	ABOUT_PAGE_SLUG,
	COOKIES_POLICY_PAGE_SLUG,
	pagePath,
	PRIVACY_POLICY_PAGE_SLUG,
} from '../../lib/site/pageSlugs';
import { getContactMailto, getNewsletterHref } from '../../lib/site/siteLinks';

const YEAR = new Date().getFullYear();

const DEFAULT_PRIVACY_BODY_EN = `<p>We collect and process personal data to operate this website and respond to inquiries. For questions about your data, contact us at <a href="mailto:contact@regularswitch.com">contact@regularswitch.com</a>.</p>`;
const DEFAULT_PRIVACY_BODY_PT = `<p>Coletamos e processamos dados pessoais para operar este site e responder a solicitações. Para dúvidas sobre seus dados, fale conosco em <a href="mailto:contact@regularswitch.com">contact@regularswitch.com</a>.</p>`;

const DEFAULT_COOKIES_BODY_EN = `<p>We use essential cookies to remember preferences (language, theme) and improve your experience. By continuing to browse, you agree to our use of cookies.</p>`;
const DEFAULT_COOKIES_BODY_PT = `<p>Usamos cookies essenciais para lembrar preferências (idioma, tema) e melhorar sua experiência. Ao continuar navegando, você concorda com o uso de cookies.</p>`;

export const DEFAULT_FOOTER_EN: FooterContent = {
	brandMark: 'REGULARSWITCH',
	links: [
		{ title: 'Contact', subtitle: 'Get in touch.', href: getContactMailto() },
		{ title: 'Newsletter', subtitle: 'Subscribe.', href: getNewsletterHref() },
		{ title: 'Join Us', subtitle: 'Careers.', href: pagePath(ABOUT_PAGE_SLUG) },
	],
	legal: {
		brand: `© ${YEAR} Regularswitch`,
		privacy: 'Privacy Policy',
		privacyHref: pagePath(PRIVACY_POLICY_PAGE_SLUG),
		privacyBody: DEFAULT_PRIVACY_BODY_EN,
		cookies: 'Cookies Policy',
		cookiesHref: pagePath(COOKIES_POLICY_PAGE_SLUG),
		cookiesBody: DEFAULT_COOKIES_BODY_EN,
	},
};

export const DEFAULT_FOOTER_PT: FooterContent = {
	brandMark: 'REGULARSWITCH',
	links: [
		{ title: 'Contato', subtitle: 'Fale conosco.', href: getContactMailto() },
		{ title: 'Newsletter', subtitle: 'Inscreva-se.', href: getNewsletterHref() },
		{ title: 'Junte-se', subtitle: 'Carreiras.', href: pagePath(ABOUT_PAGE_SLUG) },
	],
	legal: {
		brand: `© ${YEAR} Regularswitch`,
		privacy: 'Política de Privacidade',
		privacyHref: pagePath(PRIVACY_POLICY_PAGE_SLUG),
		privacyBody: DEFAULT_PRIVACY_BODY_PT,
		cookies: 'Política de Cookies',
		cookiesHref: pagePath(COOKIES_POLICY_PAGE_SLUG),
		cookiesBody: DEFAULT_COOKIES_BODY_PT,
	},
};
