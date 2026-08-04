import { GetApi, GetCapabilitiesByLocale } from '../../components/ApiWp';
import { buildCapabilitiesContent } from '../content/capabilities/build';
import { getDefaultCapabilitiesContent } from '../content/capabilities/defaults';
import { sortProjectsByDate } from '../projects/sort';
import type { CapabilitiesContent, Projects } from '../../types';

export type CapabilitiesPageData = {
	content: CapabilitiesContent;
	latestProjects: Projects;
};

async function fetchCapabilitiesFromWp(locale: 'en' | 'pt') {
	const query: Record<string, string | number> = { _embed: '', per_page: 100 };

	const [capabilities, projects] = await Promise.all([
		GetCapabilitiesByLocale(locale),
		GetApi('/project/', query),
	]);

	return {
		content: buildCapabilitiesContent(capabilities, locale, projects),
		latestProjects: sortProjectsByDate(projects),
	};
}

export async function fetchCapabilitiesPage(locale: 'en' | 'pt'): Promise<CapabilitiesPageData> {
	return fetchCapabilitiesFromWp(locale).catch((error) => {
		console.error('Error fetching capabilities page', error);
		return {
			content: getDefaultCapabilitiesContent(locale),
			latestProjects: [],
		};
	});
}
