import { GetApi } from '../components/ApiWp';
import {
	getDefaultCapabilitiesContent,
	type CapabilitiesContent,
	type CapabilitySection,
} from './capabilitiesDefaults';
import { getBaseUrl } from './getBaseUrl';
import { sortProjectsByDate } from './sortProjects';
import type { Projects } from '../types';

export type CapabilitiesPageData = {
	content: CapabilitiesContent;
	latestProjects: Projects;
};

function attachProjectImages(sections: CapabilitySection[], projects: Projects): CapabilitySection[] {
	return sections.map((section) => {
		if (section.image || !section.imageProjectSlug) return section;

		const image = projects.find((project) => project.slug === section.imageProjectSlug)?.image_full;
		return image ? { ...section, image } : section;
	});
}

function buildCapabilitiesContent(locale: 'en' | 'pt', projects: Projects): CapabilitiesContent {
	const defaults = getDefaultCapabilitiesContent(locale);

	return {
		headline: defaults.headline,
		sections: attachProjectImages(defaults.sections, projects),
	};
}

async function fetchCapabilitiesFromWp(translate?: string) {
	const query: Record<string, string | number> = { _embed: '', per_page: 100 };
	if (translate) query.translate = translate;

	const projects = await GetApi('/project/', query);
	const locale = translate === 'PT' ? 'pt' : 'en';

	return {
		content: buildCapabilitiesContent(locale, projects),
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
		const projects = await fetch(`${base}/api/project`, { headers }).then((r) => r.json() as Promise<Projects>);

		return {
			content: buildCapabilitiesContent('pt', projects),
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
