import { GetApi, GetAboutByLocale } from '../components/ApiWp';
import { buildAboutContent } from './buildAboutContent';
import { getDefaultAboutContent, type AboutContent } from './aboutDefaults';
import { sortProjectsByDate } from './sortProjects';
import type { Projects } from '../types';

export type AboutPageData = {
	content: AboutContent;
	latestProjects: Projects;
};

async function fetchAboutFromWp(locale: 'en' | 'pt') {
	const query: Record<string, string | number> = { _embed: '', per_page: 100 };

	const [about, projects] = await Promise.all([
		GetAboutByLocale(locale),
		GetApi('/project/', query),
	]);

	return {
		content: buildAboutContent(about, locale, projects),
		latestProjects: sortProjectsByDate(projects),
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
