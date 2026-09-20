import {
	GetCategoriesApi,
	GetCategoryBySlug,
	GetProjectsByCategorySlug,
	GetSiteUiApi,
} from '../../components/ApiWp';
import { HOME_PROJECTS_CATEGORY_SLUG } from '../projects/categories';
import { DEFAULT_SITE_UI_LAYOUT } from '../site/uiDefaults';
import { buildSiteUiContent } from '../site/resolveSiteUi';
import type { Category, Projects } from '../../types';

export type CategoryArchiveData = {
	category: Category;
	projects: Projects;
	categories: Category[];
	initialCount: number;
};

export async function fetchCategoryArchivePage(
	slug: string,
	locale: 'en' | 'pt',
): Promise<CategoryArchiveData | null> {
	if (!slug || slug === HOME_PROJECTS_CATEGORY_SLUG) return null;

	const query: Record<string, string | number> =
		locale === 'pt' ? { translate: 'PT' } : {};

	const [category, allCategories, siteUiRaw] = await Promise.all([
		GetCategoryBySlug(slug, query),
		GetCategoriesApi('/project-category', { per_page: 100, ...query }),
		GetSiteUiApi(),
	]);

	if (!category) return null;

	const projects = await GetProjectsByCategorySlug(slug, {
		_embed: '',
		per_page: 100,
		...query,
	});

	const { layout } = buildSiteUiContent(siteUiRaw);

	return {
		category,
		projects,
		categories: allCategories,
		initialCount: layout?.projectsInitialCount ?? DEFAULT_SITE_UI_LAYOUT.projectsInitialCount,
	};
}
