import type { Category, Project } from '../types';

/**
 * Slug da taxonomia `project-category` para projetos da home.
 * Crie o termo com slug `home` no WordPress.
 */
export const HOME_PROJECTS_CATEGORY_SLUG = 'home';

export function resolveHomeProjectsCategoryId(categories: Category[]): number | null {
	const bySlug = categories.find((category) => category.slug === HOME_PROJECTS_CATEGORY_SLUG);
	if (bySlug) return bySlug.id;

	const byTitle = categories.find((category) => category.title.trim().toLowerCase() === 'home');
	return byTitle?.id ?? null;
}

export function isHomeProject(project: Project, categories: Category[]): boolean {
	const homeId = resolveHomeProjectsCategoryId(categories);
	if (homeId === null) return false;
	return (project.category ?? []).includes(homeId);
}

export function isHiddenProjectCategory(categoryId: number, categories: Category[]): boolean {
	const homeId = resolveHomeProjectsCategoryId(categories);
	if (homeId !== null && categoryId === homeId) return true;

	const category = categories.find((item) => item.id === categoryId);
	if (category?.slug === HOME_PROJECTS_CATEGORY_SLUG) return true;

	const title = category?.title ?? '';
	return title.trim().toLowerCase() === 'home';
}

export function getVisibleCategoryTags(categoryIds: number[], categories: Category[]): string[] {
	return categoryIds
		.filter((id) => !isHiddenProjectCategory(id, categories))
		.map((id) => categories.find((category) => category.id === id)?.title ?? '')
		.filter(Boolean);
}

export function getVisibleCategoryIds(categoryIds: number[], categories: Category[]): number[] {
	return categoryIds.filter((id) => !isHiddenProjectCategory(id, categories));
}

/** @deprecated Use resolveHomeProjectsCategoryId / HOME_PROJECTS_CATEGORY_SLUG */
export const HOME_PROJECTS_CATEGORY_ID = 17;

/** @deprecated Use resolveHomeProjectsCategoryId */
export const SELECTED_PROJECTS_CATEGORY_ID = HOME_PROJECTS_CATEGORY_ID;
