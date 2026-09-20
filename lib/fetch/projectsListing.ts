import { GetApi, GetCategoriesApi, GetProjectsPageByLocale } from '../../components/ApiWp';
import { buildProjectsPageContent } from '../content/projects-page/build';
import { excludeProjectTranslationTwins } from '../projects/sort';
import type { Category, Projects } from '../../types';
import type { ProjectsPageContent } from '../content/projects-page/defaults';

export type ProjectsListingPageData = {
	content: ProjectsPageContent;
	projects: Projects;
	categories: Category[];
};

export async function fetchProjectsListingPage(locale: 'en' | 'pt'): Promise<ProjectsListingPageData> {
	const translate = locale === 'pt' ? 'PT' : '';
	const projectQuery: Record<string, string | number> = {
		_embed: '',
		per_page: 100,
		...(translate ? { translate } : {}),
	};
	const categoryQuery: Record<string, string | number> = {
		per_page: 22,
		...(translate ? { translate } : {}),
	};

	const [projects, categories, pageRaw] = await Promise.all([
		GetApi('/project/', projectQuery),
		GetCategoriesApi('/project-category', categoryQuery),
		GetProjectsPageByLocale(locale),
	]).catch((error) => {
		console.error(`Error fetching ${locale.toUpperCase()} projects listing page`, error);
		return [[], [], null] as [Projects, Category[], null];
	});

	return {
		content: buildProjectsPageContent(pageRaw, locale),
		projects: excludeProjectTranslationTwins(projects),
		categories,
	};
}
