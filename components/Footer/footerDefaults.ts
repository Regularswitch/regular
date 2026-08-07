import type { FooterContent } from '../../types';
import { ABOUT_PAGE_SLUG, pagePath } from '../../lib/site/pageSlugs';
import { getContactMailto, getNewsletterHref } from '../../lib/site/siteLinks';

const YEAR = new Date().getFullYear();

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
		privacyHref: '/privacy-policy',
		cookies: 'Cookies Policy',
		cookiesHref: '/cookies-policy',
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
		privacyHref: '/privacy-policy',
		cookies: 'Política de Cookies',
		cookiesHref: '/cookies-policy',
	},
};
