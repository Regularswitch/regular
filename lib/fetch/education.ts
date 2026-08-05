import { GetApi, GetEducationByLocale } from '../../components/ApiWp';
import { buildEducationContent } from '../content/education/build';
import { getDefaultEducationContent, type EducationContent } from '../content/education/defaults';
import { excludeProjectTranslationTwins, sortProjectsByDate } from '../projects/sort';
import type { Category, Projects } from '../../types';

export type EducationPageData = {
	content: EducationContent;
	projects: Projects;
	categories: Category[];
};

async function fetchEducationFromWp(locale: 'en' | 'pt') {
	const query: Record<string, string | number> = { _embed: '', per_page: 100 };
	if (locale === 'pt') query.translate = 'PT';

	const [education, projects] = await Promise.all([
		GetEducationByLocale(locale),
		GetApi('/project/', query),
	]);

	return {
		content: buildEducationContent(education, locale),
		projects: excludeProjectTranslationTwins(sortProjectsByDate(projects)),
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
