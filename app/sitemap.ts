import type { MetadataRoute } from 'next';

import { GetApi, GetCategoriesApi } from '../components/ApiWp';
import { getBaseUrl } from '../lib/config/getBaseUrl';
import { HOME_PROJECTS_CATEGORY_SLUG } from '../lib/projects/categories';
import { absoluteUrl, localePathPair } from '../lib/seo/localePaths';
import {
	ABOUT_PAGE_SLUG,
	CAPABILITIES_PAGE_SLUG,
	CONTACT_PAGE_SLUG,
	COOKIES_POLICY_PAGE_SLUG,
	EDUCATION_PAGE_SLUG,
	PRIVACY_POLICY_PAGE_SLUG,
	PROJECTS_PAGE_SLUG,
} from '../lib/site/pageSlugs';

export const revalidate = 3600;

const STATIC_EN_PATHS = [
	'/',
	`/${PROJECTS_PAGE_SLUG}`,
	`/${CAPABILITIES_PAGE_SLUG}`,
	`/${EDUCATION_PAGE_SLUG}`,
	`/${ABOUT_PAGE_SLUG}`,
	`/${CONTACT_PAGE_SLUG}`,
	`/${PRIVACY_POLICY_PAGE_SLUG}`,
	`/${COOKIES_POLICY_PAGE_SLUG}`,
] as const;

async function fetchAllProjectSlugs(): Promise<string[]> {
	const slugs: string[] = [];

	for (let page = 1; page <= 20; page += 1) {
		const projects = await GetApi('/project/', {
			per_page: 100,
			page,
			orderby: 'modified',
			order: 'desc',
		}).catch(() => []);

		if (!projects.length) break;

		for (const project of projects) {
			const slug = project.slug?.trim();
			if (slug) slugs.push(slug);
		}

		if (projects.length < 100) break;
	}

	return [...new Set(slugs)];
}

function sitemapEntry(
	base: string,
	enPath: string,
	priority: number,
): MetadataRoute.Sitemap[number][] {
	const { en, pt } = localePathPair(enPath);
	const languages = {
		en: absoluteUrl(base, en),
		'pt-BR': absoluteUrl(base, pt),
	};

	return [
		{
			url: absoluteUrl(base, en),
			lastModified: new Date(),
			changeFrequency: 'weekly',
			priority,
			alternates: { languages },
		},
		{
			url: absoluteUrl(base, pt),
			lastModified: new Date(),
			changeFrequency: 'weekly',
			priority,
			alternates: { languages },
		},
	];
}

export default async function sitemap(): Promise<MetadataRoute.Sitemap> {
	const base = getBaseUrl();
	const entries: MetadataRoute.Sitemap = [];

	for (const path of STATIC_EN_PATHS) {
		entries.push(...sitemapEntry(base, path, path === '/' ? 1 : 0.8));
	}

	const [projectSlugs, categories] = await Promise.all([
		fetchAllProjectSlugs(),
		GetCategoriesApi('/project-category', { per_page: 100 }).catch(() => []),
	]);

	for (const slug of projectSlugs) {
		entries.push(...sitemapEntry(base, `/project/${slug}`, 0.7));
	}

	for (const category of categories) {
		const slug = category.slug?.trim();
		if (!slug || slug === HOME_PROJECTS_CATEGORY_SLUG) continue;
		entries.push(...sitemapEntry(base, `/category/${slug}`, 0.6));
	}

	return entries;
}
