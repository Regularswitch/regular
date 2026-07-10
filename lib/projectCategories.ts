import type { Category } from '../types';

/**
 * Categoria interna no WordPress — indica que o projeto aparece na home.
 * Não deve ser exibida como tag pública nos cards.
 */
export const HOME_PROJECTS_CATEGORY_ID = 17;

/**
 * Slug da taxonomia `project-category` no WordPress.
 * Crie o termo "Education" / "Educação" com slug `education` e atribua aos projetos da página.
 */
export const EDUCATION_PROJECTS_CATEGORY_SLUG = 'education';

/** @deprecated Use HOME_PROJECTS_CATEGORY_ID */
export const SELECTED_PROJECTS_CATEGORY_ID = HOME_PROJECTS_CATEGORY_ID;

export function isHiddenProjectCategory(categoryId: number, categories: Category[]): boolean {
	if (categoryId === HOME_PROJECTS_CATEGORY_ID) return true;

	const title = categories.find((category) => category.id === categoryId)?.title ?? '';
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
