import { GetApi, GetAboutByLocale } from '../../components/ApiWp';
import { buildAboutContent } from '../content/about/build';
import { getDefaultAboutContent, type AboutContent } from '../content/about/defaults';
import { excludeProjectTranslationTwins, sortProjectsByDate } from '../projects/sort';
import type { Projects } from '../../types';

export type AboutPageData = {
	content: AboutContent;
	latestProjects: Projects;
};

async function fetchAboutFromWp(locale: 'en' | 'pt') {
	const query: Record<string, string | number> = { _embed: '', per_page: 100 };
	if (locale === 'pt') query.translate = 'PT';

	const [about, projects] = await Promise.all([
		GetAboutByLocale(locale),
		GetApi('/project/', query),
	]);

	const latestProjects = excludeProjectTranslationTwins(sortProjectsByDate(projects));

	return {
		content: buildAboutContent(about, locale, latestProjects),
		latestProjects,
	};
}

export async function fetchAboutPage(locale: 'en' | 'pt'): Promise<AboutPageData> {
	return fetchAboutFromWp(locale).catch((error) => {
		console.error('Error fetching about page', error);
		return {
			content: {
				...getDefaultAboutContent(locale),
				accordionSections: getDefaultAboutContent(locale).accordionSections,
			},
			latestProjects: [],
		};
	});
}
