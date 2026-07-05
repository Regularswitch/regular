import type { FooterContent } from '../../types';

export const DEFAULT_FOOTER_EN: FooterContent = {
	brandMark: 'REGULARSWITCH',
	links: [
		{ title: 'Contact', subtitle: 'Get in touch.', href: '/contact-3' },
		{ title: 'Newsletter', subtitle: 'Subscribe.', href: 'mailto:contact@regularswitch.com?subject=Newsletter' },
		{ title: 'Join Us', subtitle: 'Careers.', href: '/about' },
	],
	legal: {
		brand: '@ RegularSwitch',
		privacy: 'Privacy Policy',
		privacyHref: '/privacy-policy',
		cookies: 'Cookies Policy',
		cookiesHref: '/cookies-policy',
	},
};

export const DEFAULT_FOOTER_PT: FooterContent = {
	brandMark: 'REGULARSWITCH',
	links: [
		{ title: 'Contato', subtitle: 'Fale conosco.', href: '/contact-3' },
		{ title: 'Newsletter', subtitle: 'Inscreva-se.', href: 'mailto:contact@regularswitch.com?subject=Newsletter' },
		{ title: 'Junte-se', subtitle: 'Carreiras.', href: '/about' },
	],
	legal: {
		brand: '@ RegularSwitch',
		privacy: 'Política de Privacidade',
		privacyHref: '/privacy-policy',
		cookies: 'Política de Cookies',
		cookiesHref: '/cookies-policy',
	},
};
