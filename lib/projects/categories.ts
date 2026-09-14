import type { Category, CategoryTag, Project } from '../../types';

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

export function getVisibleCategoryTags(categoryIds: number[], categories: Category[]): CategoryTag[] {
	return categoryIds
		.filter((id) => !isHiddenProjectCategory(id, categories))
		.map((id) => {
			const category = categories.find((item) => item.id === id);
			if (!category?.title) return null;
			const slug = category.slug?.trim() || '';
			if (!slug) return null;
			return {
				id: category.id,
				title: category.title,
				slug,
			};
		})
		.filter((tag): tag is CategoryTag => Boolean(tag));
}

export function getVisibleCategoryIds(categoryIds: number[], categories: Category[]): number[] {
	return categoryIds.filter((id) => !isHiddenProjectCategory(id, categories));
}

export function categoryArchivePath(slug: string, locale: 'en' | 'pt' = 'en'): string {
	const base = `/category/${slug}`;
	return locale === 'pt' ? `/PT${base}` : base;
}

/** @deprecated Use resolveHomeProjectsCategoryId / HOME_PROJECTS_CATEGORY_SLUG */
export const HOME_PROJECTS_CATEGORY_ID = 17;

/** @deprecated Use resolveHomeProjectsCategoryId */
export const SELECTED_PROJECTS_CATEGORY_ID = HOME_PROJECTS_CATEGORY_ID;
