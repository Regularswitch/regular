export type ContactBlock = {
	title: string;
	body: string;
};

export type ContactContent = {
	heroImage?: string;
	heroVideo?: string;
	headline: string;
	blocks: ContactBlock[];
};

const CONTACT_BLOCKS_PT: ContactBlock[] = [
	{
		title: 'CONTATO',
		body: '<p>São Paulo – Brasil<br><a href="tel:+5511945408448">+55 11 (9) 4540-8448</a><br><a href="mailto:contact@regularswitch.com">contact@regularswitch.com</a></p>',
	},
	{
		title: 'ENDEREÇO',
		body: '<p>São Paulo – Brasil<br>Rua da Consolação, 65</p>',
	},
	{
		title: 'VAGAS',
		body: '<p>No momento não estamos contratando.</p>',
	},
	{
		title: 'ESTÁGIO',
		body: '<p>Envie um e-mail para se candidatar.<br><a href="mailto:contact@regularswitch.com">contact@regularswitch.com</a></p>',
	},
];

const CONTACT_BLOCKS_EN: ContactBlock[] = [
	{
		title: 'CONTACT',
		body: '<p>São Paulo – Brazil<br><a href="tel:+5511945408448">+55 11 (9) 4540-8448</a><br><a href="mailto:contact@regularswitch.com">contact@regularswitch.com</a></p>',
	},
	{
		title: 'ADDRESS',
		body: '<p>São Paulo – Brazil<br>Rua da Consolação, 65</p>',
	},
	{
		title: 'JOBS',
		body: '<p>We are not hiring at the moment.</p>',
	},
	{
		title: 'INTERNSHIP',
		body: '<p>Send us an e-mail to apply.<br><a href="mailto:contact@regularswitch.com">contact@regularswitch.com</a></p>',
	},
];

export const DEFAULT_CONTACT_PT: ContactContent = {
	headline: 'Vamos <strong>conversar</strong> sobre o seu <strong>próximo projeto</strong>.',
	blocks: CONTACT_BLOCKS_PT,
};

export const DEFAULT_CONTACT_EN: ContactContent = {
	headline: 'Let\'s <strong>talk</strong> about your <strong>next project</strong>.',
	blocks: CONTACT_BLOCKS_EN,
};

export function getDefaultContactContent(locale: 'en' | 'pt'): ContactContent {
	return locale === 'pt' ? { ...DEFAULT_CONTACT_PT } : { ...DEFAULT_CONTACT_EN };
}

export const DEFAULT_CONTACT_HERO_IMAGE =
	'https://wp.regularswitch.com/wp-content/uploads/2024/11/LA-martiniere-Regularswitch-1024x582.jpg';
