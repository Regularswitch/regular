import type { ProjectAccordionSection } from './parseProjectContent';

export type EducationContent = {
	heroImage?: string;
	headline: string;
	accordionSections: ProjectAccordionSection[];
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

export const DEFAULT_EDUCATION_PT: EducationContent = {
	headline:
		'Acreditamos na educação como espaço de troca e experimentação criativa. <strong>Entre</strong> França e Brasil, <strong>desenvolvemos</strong> workshops, talks e projetos colaborativos <strong>que conectam</strong> culturas e novas formas de pensar <strong>o design contemporâneo</strong>.',
	accordionSections: EDUCATION_ACCORDION_PT,
};

export const DEFAULT_EDUCATION_EN: EducationContent = {
	headline:
		'We believe education is a space for exchange and creative experimentation. <strong>Between</strong> France and Brazil, <strong>we develop</strong> workshops, talks and collaborative projects <strong>that connect</strong> cultures and new ways of thinking about <strong>contemporary design</strong>.',
	accordionSections: EDUCATION_ACCORDION_EN,
};
