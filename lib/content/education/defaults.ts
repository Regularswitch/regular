import type { ProjectAccordionSection } from '../../projects/parseContent';

export type EducationGalleryLayout = 'pair' | 'triple' | 'grid-2x2';

export type EducationGallery = {
	layout: EducationGalleryLayout;
	images: string[];
	caption?: string;
};

export type EducationInstitution = {
	name: string;
	logo?: string;
	description?: string;
	/** Galeria acima do logo/nome (ex.: Nantes). */
	topGallery?: EducationGallery;
	/** Galeria logo após o header (ex.: Mackenzie triple). */
	midGallery?: EducationGallery;
	/** Galeria após a descrição (ex.: grid 2×2). */
	bottomGallery?: EducationGallery;
};

export type EducationContent = {
	heroImage?: string;
	heroVideo?: string;
	headline: string;
	accordionSections: ProjectAccordionSection[];
	institutions?: EducationInstitution[];
	/** @deprecated Mantido por compatibilidade com CMS antigo. */
	studioImages?: string[];
};

const EDUCATION_ACCORDION_PT: ProjectAccordionSection[] = [
	{
		title: 'WORKSHOPS',
		body: '<p>Experiências práticas de design, tipografia e direção criativa em formato intensivo e colaborativo.</p>',
	},
	{
		title: 'COLABORAÇÕES COM ESCOLAS',
		body: '<p>Parcerias com instituições de ensino na França e no Brasil para projetos, residências e intercâmbios culturais.</p>',
	},
	{
		title: 'PALESTRAS & TALKS',
		body: '<p>Conversas e apresentações sobre design contemporâneo, branding e cultura visual para públicos acadêmicos e profissionais.</p>',
	},
	{
		title: 'MENTORIAS & INTERVENTIONS',
		body: '<p>Acompanhamento criativo e intervenções em projetos estudantis e iniciativas emergentes.</p>',
	},
];

const EDUCATION_ACCORDION_EN: ProjectAccordionSection[] = [
	{
		title: 'WORKSHOPS',
		body: '<p>Hands-on experiences in design, typography and creative direction through intensive, collaborative formats.</p>',
	},
	{
		title: 'SCHOOL COLLABORATIONS',
		body: '<p>Partnerships with schools and universities in France and Brazil for projects, residencies and cultural exchange.</p>',
	},
	{
		title: 'LECTURES & TALKS',
		body: '<p>Talks and presentations on contemporary design, branding and visual culture for academic and professional audiences.</p>',
	},
	{
		title: 'MENTORING & INTERVENTIONS',
		body: '<p>Creative mentoring and interventions in student projects and emerging initiatives.</p>',
	},
];

const EDUCATION_INSTITUTIONS_PT: EducationInstitution[] = [
	{
		name: 'École de Design de Nantes Atlantique (France)',
		topGallery: {
			layout: 'pair',
			images: [],
			caption: '',
		},
		midGallery: {
			layout: 'pair',
			images: [],
		},
	},
	{
		name: 'Mackenzie University (Brazil)',
		midGallery: {
			layout: 'triple',
			images: [],
		},
		description:
			'<p>Parceria com a universidade para workshops, mentorias e projetos colaborativos entre estudantes e o estúdio.</p>',
		bottomGallery: {
			layout: 'grid-2x2',
			images: [],
		},
	},
];

const EDUCATION_INSTITUTIONS_EN: EducationInstitution[] = [
	{
		name: 'École de Design de Nantes Atlantique (France)',
		topGallery: {
			layout: 'pair',
			images: [],
			caption: '',
		},
		midGallery: {
			layout: 'pair',
			images: [],
		},
	},
	{
		name: 'Mackenzie University (Brazil)',
		midGallery: {
			layout: 'triple',
			images: [],
		},
		description:
			'<p>Partnership with the university for workshops, mentoring and collaborative projects between students and the studio.</p>',
		bottomGallery: {
			layout: 'grid-2x2',
			images: [],
		},
	},
];

export const DEFAULT_EDUCATION_PT: EducationContent = {
	headline:
		'Acreditamos na educação como espaço de troca e experimentação criativa. <strong>Entre França e Brasil</strong>, desenvolvemos workshops, talks e projetos colaborativos que conectam culturas e novas formas de pensar o <strong>design contemporâneo</strong>.',
	accordionSections: EDUCATION_ACCORDION_PT,
	institutions: EDUCATION_INSTITUTIONS_PT,
	studioImages: [],
};

export const DEFAULT_EDUCATION_EN: EducationContent = {
	headline:
		'We believe education is a space for exchange and creative experimentation. <strong>Between France and Brazil</strong>, we develop workshops, talks and collaborative projects that connect cultures and new ways of thinking about <strong>contemporary design</strong>.',
	accordionSections: EDUCATION_ACCORDION_EN,
	institutions: EDUCATION_INSTITUTIONS_EN,
	studioImages: [],
};

export function getDefaultEducationContent(locale: 'en' | 'pt'): EducationContent {
	return locale === 'pt'
		? {
				...DEFAULT_EDUCATION_PT,
				accordionSections: [...DEFAULT_EDUCATION_PT.accordionSections],
				institutions: DEFAULT_EDUCATION_PT.institutions?.map((item) => ({ ...item })),
			}
		: {
				...DEFAULT_EDUCATION_EN,
				accordionSections: [...DEFAULT_EDUCATION_EN.accordionSections],
				institutions: DEFAULT_EDUCATION_EN.institutions?.map((item) => ({ ...item })),
			};
}
