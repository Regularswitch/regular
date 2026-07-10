import { GetApi, GetCapabilitiesApi } from '../components/ApiWp';
import { buildCapabilitiesContent } from './buildCapabilitiesContent';
import { getDefaultCapabilitiesContent } from './capabilitiesDefaults';
import { getBaseUrl } from './getBaseUrl';
import { sortProjectsByDate } from './sortProjects';
import type { CapabilitiesContent, Projects } from '../types';

export type CapabilitiesPageData = {
	content: CapabilitiesContent;
	latestProjects: Projects;
};

async function fetchCapabilitiesFromWp(translate?: string) {
	const query: Record<string, string | number> = { _embed: '', per_page: 100 };
	if (translate) query.translate = translate;

	const capabilitiesQuery: Record<string, string> = {};
	if (translate) capabilitiesQuery.translate = translate;

	const [capabilities, projects] = await Promise.all([
		GetCapabilitiesApi(capabilitiesQuery),
		GetApi('/project/', query),
	]);

	const locale = translate === 'PT' ? 'pt' : 'en';

	return {
		content: buildCapabilitiesContent(capabilities, locale, projects),
		latestProjects: sortProjectsByDate(projects),
	};
}

export async function fetchCapabilitiesPage(locale: 'en' | 'pt'): Promise<CapabilitiesPageData> {
	if (locale === 'en') {
		return fetchCapabilitiesFromWp().catch((error) => {
			console.error('Error fetching capabilities page', error);
			return {
				content: getDefaultCapabilitiesContent('en'),
				latestProjects: [],
			};
		});
	}

	const base = getBaseUrl();
	const headers = { Cookie: 'language=PT' };

	try {
		const [capabilities, projects] = await Promise.all([
			fetch(`${base}/api/capabilities`, { headers }).then(
				(r) => r.json() as Promise<CapabilitiesContent | null>,
			),
			fetch(`${base}/api/project`, { headers }).then((r) => r.json() as Promise<Projects>),
		]);

		return {
			content: buildCapabilitiesContent(capabilities, 'pt', projects),
			latestProjects: sortProjectsByDate(projects),
		};
	} catch (error) {
		console.error('Error fetching PT capabilities page', error);
		return {
			content: getDefaultCapabilitiesContent('pt'),
			latestProjects: [],
		};
	}
}
