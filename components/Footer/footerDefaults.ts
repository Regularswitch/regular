import type { FooterContent } from '../../types';
import {
	ABOUT_PAGE_SLUG,
	CONTACT_PAGE_SLUG,
	COOKIES_POLICY_PAGE_SLUG,
	pagePath,
	PRIVACY_POLICY_PAGE_SLUG,
} from '../../lib/pageSlugs';

export const DEFAULT_FOOTER_EN: FooterContent = {
	brandMark: 'REGULARSWITCH',
	links: [
		{ title: 'Contact', subtitle: 'Get in touch.', href: pagePath(CONTACT_PAGE_SLUG) },
		{ title: 'Newsletter', subtitle: 'Subscribe.', href: 'mailto:contact@regularswitch.com?subject=Newsletter' },
		{ title: 'Join Us', subtitle: 'Careers.', href: pagePath(ABOUT_PAGE_SLUG) },
	],
	legal: {
		brand: '@ RegularSwitch',
		privacy: 'Privacy Policy',
		privacyHref: pagePath(PRIVACY_POLICY_PAGE_SLUG),
		cookies: 'Cookies Policy',
		cookiesHref: pagePath(COOKIES_POLICY_PAGE_SLUG),
	},
};

export const DEFAULT_FOOTER_PT: FooterContent = {
	brandMark: 'REGULARSWITCH',
	links: [
		{ title: 'Contato', subtitle: 'Fale conosco.', href: pagePath(CONTACT_PAGE_SLUG) },
		{ title: 'Newsletter', subtitle: 'Inscreva-se.', href: 'mailto:contact@regularswitch.com?subject=Newsletter' },
		{ title: 'Junte-se', subtitle: 'Carreiras.', href: pagePath(ABOUT_PAGE_SLUG) },
	],
	legal: {
		brand: '@ RegularSwitch',
		privacy: 'Política de Privacidade',
		privacyHref: pagePath(PRIVACY_POLICY_PAGE_SLUG),
		cookies: 'Política de Cookies',
		cookiesHref: pagePath(COOKIES_POLICY_PAGE_SLUG),
	},
};
