export type CookieCategoryId = 'necessary' | 'performance' | 'functional' | 'marketing';

export type CookieCategory = {
	id: CookieCategoryId;
	title: string;
	description: string;
	locked: boolean;
	defaultOn: boolean;
};

export type LegalContent = {
	privacyTitle: string;
	privacyBody: string;
	cookiesModalTitle: string;
	cookiesIntro?: string;
	rejectAllLabel: string;
	submitLabel: string;
	categories: CookieCategory[];
};

const CATEGORIES_EN: CookieCategory[] = [
	{
		id: 'necessary',
		title: 'Strictly necessary cookies',
		description:
			'<p>These cookies are essential for the website to function (language, theme and basic preferences). They cannot be switched off.</p>',
		locked: true,
		defaultOn: true,
	},
	{
		id: 'performance',
		title: 'Performance cookies',
		description:
			'<p>These cookies let us and our analytics partners collect information about how you and other visitors use our services. We use these insights to improve our products and services so they work better for you and everyone else.</p>',
		locked: false,
		defaultOn: true,
	},
	{
		id: 'functional',
		title: 'Functional cookies',
		description:
			'<p>These cookies enable enhanced functionality and personalisation. If you disable them, some features may not work as expected.</p>',
		locked: false,
		defaultOn: false,
	},
	{
		id: 'marketing',
		title: 'Marketing cookies',
		description:
			'<p>These cookies may be set to deliver relevant ads and measure campaigns. They can be set by us or by our partners.</p>',
		locked: false,
		defaultOn: true,
	},
];

const CATEGORIES_PT: CookieCategory[] = [
	{
		id: 'necessary',
		title: 'Cookies estritamente necessários',
		description:
			'<p>Esses cookies são essenciais para o funcionamento do site (idioma, tema e preferências básicas). Não podem ser desativados.</p>',
		locked: true,
		defaultOn: true,
	},
	{
		id: 'performance',
		title: 'Cookies de desempenho',
		description:
			'<p>Esses cookies permitem que nós e nossos parceiros de análise coletem informações sobre como você e outros visitantes usam nossos serviços. Usamos esses insights para melhorar produtos e serviços.</p>',
		locked: false,
		defaultOn: true,
	},
	{
		id: 'functional',
		title: 'Cookies funcionais',
		description:
			'<p>Esses cookies permitem recursos aprimorados e personalização. Se desativados, alguns recursos podem não funcionar corretamente.</p>',
		locked: false,
		defaultOn: false,
	},
	{
		id: 'marketing',
		title: 'Cookies de marketing',
		description:
			'<p>Esses cookies podem ser usados para exibir anúncios relevantes e medir campanhas. Podem ser definidos por nós ou por parceiros.</p>',
		locked: false,
		defaultOn: true,
	},
];

export const DEFAULT_LEGAL_EN: LegalContent = {
	privacyTitle: 'Privacy Policy',
	privacyBody:
		'<p>We collect and process personal data to operate this website and respond to inquiries. For questions, contact us at <a href="mailto:contact@regularswitch.com">contact@regularswitch.com</a>.</p>',
	cookiesModalTitle: 'Manage cookie preferences',
	cookiesIntro: '',
	rejectAllLabel: 'Reject all',
	submitLabel: 'Submit my choices',
	categories: CATEGORIES_EN,
};

export const DEFAULT_LEGAL_PT: LegalContent = {
	privacyTitle: 'Política de Privacidade',
	privacyBody:
		'<p>Coletamos e processamos dados pessoais para operar este site e responder a solicitações. Para dúvidas, fale conosco em <a href="mailto:contact@regularswitch.com">contact@regularswitch.com</a>.</p>',
	cookiesModalTitle: 'Gerenciar preferências de cookies',
	cookiesIntro: '',
	rejectAllLabel: 'Rejeitar todos',
	submitLabel: 'Enviar minhas escolhas',
	categories: CATEGORIES_PT,
};

export function getDefaultLegalContent(locale: 'en' | 'pt'): LegalContent {
	return locale === 'pt' ? { ...DEFAULT_LEGAL_PT, categories: [...DEFAULT_LEGAL_PT.categories] } : { ...DEFAULT_LEGAL_EN, categories: [...DEFAULT_LEGAL_EN.categories] };
}
