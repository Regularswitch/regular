import { GetApi, GetCategoriesApi, GetProjectsByCategorySlug } from '../components/ApiWp';
import { buildEducationContent } from './parsePageContent';
import type { EducationContent } from './educationDefaults';
import { fetchWpPageByLocale } from './fetchWpPageByLocale';
import { EDUCATION_PAGE_SLUG } from './pageSlugs';
import { EDUCATION_PROJECTS_CATEGORY_SLUG } from './projectCategories';
import { sortProjectsByDate } from './sortProjects';
import type { Category, Projects } from '../types';

export type EducationPageData = {
	content: EducationContent;
	projects: Projects;
	categories: Category[];
};

async function fetchEducationFromWp(locale: 'en' | 'pt') {
	const query: Record<string, string | number> = { _embed: '' };
	if (locale === 'pt') query.translate = 'PT';

	const [page, educationProjects, categories] = await Promise.all([
		fetchWpPageByLocale(EDUCATION_PAGE_SLUG, locale, query),
		GetProjectsByCategorySlug(EDUCATION_PROJECTS_CATEGORY_SLUG, query),
		GetCategoriesApi('/project-category', { per_page: 22, ...query }),
	]);

	return {
		content: buildEducationContent(page?.content, page?.image_full, locale),
		projects: sortProjectsByDate(educationProjects),
		categories,
	};
}

export async function fetchEducationPage(locale: 'en' | 'pt'): Promise<EducationPageData> {
	return fetchEducationFromWp(locale).catch((error) => {
		console.error('Error fetching education page', error);
		return {
			content: buildEducationContent(undefined, undefined, locale),
			projects: [],
			categories: [],
		};
	});
}
