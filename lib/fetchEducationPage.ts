import { GetApi, GetEducationByLocale } from '../components/ApiWp';
import { buildEducationContent } from './buildEducationContent';
import { getDefaultEducationContent, type EducationContent } from './educationDefaults';
import { sortProjectsByDate } from './sortProjects';
import type { Category, Projects } from '../types';

export type EducationPageData = {
	content: EducationContent;
	projects: Projects;
	categories: Category[];
};

async function fetchEducationFromWp(locale: 'en' | 'pt') {
	const query: Record<string, string | number> = { _embed: '', per_page: 100 };

	const [education, projects] = await Promise.all([
		GetEducationByLocale(locale),
		GetApi('/project/', query),
	]);

	return {
		content: buildEducationContent(education, locale),
		projects: sortProjectsByDate(projects),
		categories: [] as Category[],
	};
}

export async function fetchEducationPage(locale: 'en' | 'pt'): Promise<EducationPageData> {
	return fetchEducationFromWp(locale).catch((error) => {
		console.error('Error fetching education page', error);
		return {
			content: getDefaultEducationContent(locale),
			projects: [],
			categories: [],
		};
	});
}
