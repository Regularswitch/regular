/**
 * Alt text de mídia de projeto.
 * Prefere o alt nativo do WordPress; senão usa o padrão SEO do PDF.
 */
export function projectImageAltFallback(
	projectTitle: string,
	locale: 'en' | 'pt' = 'en',
): string {
	const title = projectTitle.trim() || 'RegularSwitch';
	return locale === 'pt'
		? `Identidade visual ${title}, RegularSwitch`
		: `Visual identity ${title}, RegularSwitch`;
}

export function resolveMediaAlt(
	mediaAlt: string | null | undefined,
	projectTitle: string,
	locale: 'en' | 'pt' = 'en',
): string {
	const fromWp = mediaAlt?.trim();
	if (fromWp) return fromWp;
	return projectImageAltFallback(projectTitle, locale);
}

type ProjectLike = {
	title?: string;
	slug?: string;
	project_data?: {
		featuredImage?: { alt?: string } | null;
		heroImage?: { alt?: string } | null;
	} | null;
};

/** Alt para cards / thumbs a partir do projeto (featured → hero → padrão). */
export function resolveProjectCardAlt(
	project: ProjectLike,
	locale: 'en' | 'pt' = 'en',
): string {
	const title = project.title?.trim() || project.slug?.trim() || 'RegularSwitch';
	const fromFeatured = project.project_data?.featuredImage?.alt?.trim();
	if (fromFeatured) return fromFeatured;
	const fromHero = project.project_data?.heroImage?.alt?.trim();
	if (fromHero) return fromHero;
	return projectImageAltFallback(title, locale);
}
