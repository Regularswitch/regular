import { getDefaultProjectsPageContent, type ProjectsPageContent } from './defaults';

export function buildProjectsPageContent(
	fromWp: ProjectsPageContent | null | undefined,
	locale: 'en' | 'pt',
): ProjectsPageContent {
	const defaults = getDefaultProjectsPageContent(locale);

	if (!fromWp) return defaults;

	return {
		title: fromWp.title?.trim() || defaults.title,
		headline: fromWp.headline?.trim() || defaults.headline,
		emptyMessage: fromWp.emptyMessage?.trim() || defaults.emptyMessage,
	};
}
