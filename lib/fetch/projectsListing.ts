import { GetApi, GetCategoriesApi, GetProjectsPageByLocale } from '../../components/ApiWp';
import { buildProjectsPageContent } from '../content/projects-page/build';
import { getBaseUrl } from '../config/getBaseUrl';
import type { Category, Projects } from '../../types';
import type { ProjectsPageContent } from '../content/projects-page/defaults';

export type ProjectsListingPageData = {
	content: ProjectsPageContent;
	projects: Projects;
	categories: Category[];
};

export async function fetchProjectsListingPage(locale: 'en' | 'pt'): Promise<ProjectsListingPageData> {
	if (locale === 'en') {
		const [projects, categories, pageRaw] = await Promise.all([
			GetApi('/project/', { _embed: '', per_page: 100 }),
			GetCategoriesApi('/project-category', { per_page: 22 }),
			GetProjectsPageByLocale('en'),
		]).catch((error) => {
			console.error('Error fetching EN projects listing page', error);
			return [[], [], null] as [Projects, Category[], null];
		});

		return {
			content: buildProjectsPageContent(pageRaw, 'en'),
			projects,
			categories,
		};
	}

	const base = getBaseUrl();
	const headers = { Cookie: 'language=PT' };

	const [projects, categories, pageRaw] = await Promise.all([
		fetch(`${base}/api/project`, { headers }).then((r) => r.json() as Promise<Projects>),
		fetch(`${base}/api/project/all-category`, { headers }).then((r) => r.json() as Promise<Category[]>),
		GetProjectsPageByLocale('pt'),
	]).catch((error) => {
		console.error('Error fetching PT projects listing page', error);
		return [[], [], null] as [Projects, Category[], null];
	});

	return {
		content: buildProjectsPageContent(pageRaw, 'pt'),
		projects,
		categories,
	};
}
