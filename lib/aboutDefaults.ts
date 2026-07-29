import type { ProjectAccordionSection } from './parseProjectContent';

export type AboutAccordionSection = ProjectAccordionSection & {
	image?: string;
	/** Slug de projeto no WP para imagem do painel. */
	imageProjectSlug?: string;
};

export type AboutContent = {
	heroImage?: string;
	heroVideo?: string;
	headline: string;
	body: string;
	accordionSections: AboutAccordionSection[];
};

const ABOUT_ACCORDION_PT: AboutAccordionSection[] = [
	{
		title: 'NOSSA ABORDAGEM',
		body: '<p>Combinamos pensamento estratégico, sensibilidade cultural e experimentação criativa para desenvolver projetos que conectam marcas, pessoas e territórios de forma relevante e contemporânea.</p>',
		imageProjectSlug: 'central-1926',
	},
	{
		title: 'NOSSO TIME',
		body: '<p>Somos um time multicultural com raízes no Brasil e na França — designers, diretores de arte e estrategistas que atuam de forma colaborativa em projetos de branding, digital e cultura visual.</p>',
		imageProjectSlug: 'workshop-explore-france-2026',
	},
	{
		title: 'NOSSA METODOLOGIA DE TRABALHO',
		body: '<p>Trabalhamos em ciclos de pesquisa, concepção e execução, integrando narrativa, sistema visual e aplicação em diferentes contextos — do impresso ao digital, do institucional ao cultural.</p>',
		imageProjectSlug: 'piktiz',
	},
	{
		title: 'NOSSOS VALORES',
		body: '<p>Acreditamos em colaboração, rigor gráfico, abertura cultural e impacto contemporâneo. Buscamos projetos com propósito, estética e consistência ao longo do tempo.</p>',
		imageProjectSlug: 'frentistas-do-brasil',
	},
	{
		title: 'NÃO NEGOCIÁVEIS',
		body: '<p>Qualidade de execução, honestidade criativa, respeito às pessoas e compromisso com a relevância cultural dos projetos que desenvolvemos.</p>',
		imageProjectSlug: 'exposicao-sonhos',
	},
];

const ABOUT_ACCORDION_EN: AboutAccordionSection[] = [
	{
		title: 'OUR APPROACH',
		body: '<p>We combine strategic thinking, cultural sensitivity and creative experimentation to develop projects that connect brands, people and territories in a relevant, contemporary way.</p>',
		imageProjectSlug: 'central-1926',
	},
	{
		title: 'OUR TEAM',
		body: '<p>We are a multicultural team rooted in Brazil and France — designers, art directors and strategists working collaboratively on branding, digital and visual culture projects.</p>',
		imageProjectSlug: 'workshop-explore-france-2026',
	},
	{
		title: 'OUR WORKING METHODOLOGY',
		body: '<p>We work in cycles of research, conception and execution, integrating narrative, visual systems and application across contexts — from print to digital, institutional to cultural.</p>',
		imageProjectSlug: 'piktiz',
	},
	{
		title: 'OUR VALUES',
		body: '<p>We believe in collaboration, graphic rigor, cultural openness and contemporary impact. We pursue projects with purpose, aesthetics and consistency over time.</p>',
		imageProjectSlug: 'frentistas-do-brasil',
	},
	{
		title: 'NON-NEGOTIABLES',
		body: '<p>Quality of execution, creative honesty, respect for people and commitment to the cultural relevance of the projects we develop.</p>',
		imageProjectSlug: 'exposicao-sonhos',
	},
];

export const DEFAULT_ABOUT_PT: AboutContent = {
	headline:
		'Criando <strong>conexões culturais</strong> entre <strong>estratégia, criatividade</strong> e <strong>experiências contemporâneas</strong>.',
	body: `<p>A RegularSwitch nasceu da conexão entre culturas distintas — com raízes no Brasil e na França. Atuamos na interseção entre branding, conteúdo e experiências digitais, desenvolvendo sistemas visuais, narrativas e ecossistemas criativos para marcas e instituições.</p>
<p>Nossa abordagem combina pensamento estratégico, sensibilidade cultural e experimentação criativa. Do conceito à execução, construímos projetos que aproximam marcas, pessoas e territórios de forma relevante e contemporânea.</p>
<p>Acreditamos no design como ferramenta de conexão — capaz de traduzir propósito em linguagem visual e ampliar o impacto de marcas e projetos culturais em diferentes contextos.</p>`,
	accordionSections: ABOUT_ACCORDION_PT,
};

export const DEFAULT_ABOUT_EN: AboutContent = {
	headline:
		'Creating <strong>cultural connections</strong> between <strong>strategy, creativity</strong> and <strong>contemporary experiences</strong>.',
	body: `<p>RegularSwitch was born from the connection between distinct cultures — with roots in Brazil and France. We operate at the intersection of branding, content and digital experiences, developing visual systems, narratives and creative ecosystems for brands and institutions.</p>
<p>Our approach combines strategic thinking, cultural sensitivity and creative experimentation. From concept to execution, we build projects that bring brands, people and territories closer in a relevant, contemporary way.</p>
<p>We believe in design as a tool for connection — able to translate purpose into visual language and expand the impact of brands and cultural projects across different contexts.</p>`,
	accordionSections: ABOUT_ACCORDION_EN,
};

export function getDefaultAboutContent(locale: 'en' | 'pt'): AboutContent {
	return locale === 'pt' ? { ...DEFAULT_ABOUT_PT } : { ...DEFAULT_ABOUT_EN };
}

/** Imagem padrão do hero até haver conteúdo no WordPress. */
export const DEFAULT_ABOUT_HERO_IMAGE =
	'https://wp.regularswitch.com/wp-content/uploads/2024/11/LA-martiniere-Regularswitch-1024x582.jpg';
