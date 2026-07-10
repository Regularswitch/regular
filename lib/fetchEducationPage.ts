import { GetApi, GetCategoriesApi, GetProjectsByCategorySlug } from '../components/ApiWp';
import { buildEducationContent } from './parsePageContent';
import type { EducationContent } from './educationDefaults';
import { EDUCATION_PROJECTS_CATEGORY_SLUG } from './projectCategories';
import { getBaseUrl } from './getBaseUrl';
import { sortProjectsByDate } from './sortProjects';
import type { Category, Projects } from '../types';

export type EducationPageData = {
	content: EducationContent;
	projects: Projects;
	categories: Category[];
};

async function fetchEducationFromWp(translate?: string) {
	const query: Record<string, string | number> = { _embed: '' };
	if (translate) query.translate = translate;

	const [pages, educationProjects, categories] = await Promise.all([
		GetApi('/pages', { slug: 'education', ...query }),
		GetProjectsByCategorySlug(EDUCATION_PROJECTS_CATEGORY_SLUG, query),
		GetCategoriesApi('/project-category', { per_page: 22, ...query }),
	]);

	const page = pages[0];

	return {
		content: buildEducationContent(page?.content, page?.image_full, translate === 'PT' ? 'pt' : 'en'),
		projects: sortProjectsByDate(educationProjects),
		categories,
	};
}

export async function fetchEducationPage(locale: 'en' | 'pt'): Promise<EducationPageData> {
	if (locale === 'en') {
		return fetchEducationFromWp().catch((error) => {
			console.error('Error fetching education page', error);
			return {
				content: buildEducationContent(undefined, undefined, 'en'),
				projects: [],
				categories: [],
			};
		});
	}

	const base = getBaseUrl();
	const headers = { Cookie: 'language=PT' };

	try {
		const [pages, educationProjects, categories] = await Promise.all([
			fetch(`${base}/api/education`, { headers }).then((r) => r.json() as Promise<Projects>),
			fetch(`${base}/api/project-category/${EDUCATION_PROJECTS_CATEGORY_SLUG}`, { headers }).then(
				(r) => r.json() as Promise<Projects>,
			),
			fetch(`${base}/api/project/all-category`, { headers }).then((r) => r.json() as Promise<Category[]>),
		]);

		const page = pages[0];

		return {
			content: buildEducationContent(page?.content, page?.image_full, 'pt'),
			projects: sortProjectsByDate(educationProjects),
			categories,
		};
	} catch (error) {
		console.error('Error fetching PT education page', error);
		return {
			content: buildEducationContent(undefined, undefined, 'pt'),
			projects: [],
			categories: [],
		};
	}
}
