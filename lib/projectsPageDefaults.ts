export type ProjectsPageContent = {
	title: string;
	headline: string;
	emptyMessage: string;
};

export const DEFAULT_PROJECTS_PAGE_EN: ProjectsPageContent = {
	title: 'Selected projects',
	headline:
		'Creating <strong>visual identities</strong>, <strong>digital experiences</strong> and <strong>cultural narratives</strong> for brands, institutions and projects that move between <strong>strategy</strong>, <strong>creativity</strong> and <strong>contemporary impact</strong>.',
	emptyMessage: 'No projects found.',
};

export const DEFAULT_PROJECTS_PAGE_PT: ProjectsPageContent = {
	title: 'Projetos selecionados',
	headline:
		'Criando <strong>identidades visuais</strong>, <strong>experiências digitais</strong> e <strong>narrativas culturais</strong> para marcas, instituições e projetos que transitam entre <strong>estratégia</strong>, <strong>criatividade</strong> e <strong>impacto contemporâneo</strong>.',
	emptyMessage: 'Nenhum projeto encontrado.',
};

export function getDefaultProjectsPageContent(locale: 'en' | 'pt'): ProjectsPageContent {
	return locale === 'pt' ? { ...DEFAULT_PROJECTS_PAGE_PT } : { ...DEFAULT_PROJECTS_PAGE_EN };
}
