import { cookies } from 'next/headers';

import { GetApi, GetCategoriesApi, GetIntroApi } from '../../components/ApiWp';
import ProjectsListing from '../../components/ProjectsListing/ProjectsListing';
import SlugPageClient from '../../components/SlugPageClient';
import { getBaseUrl } from '../../lib/getBaseUrl';
import type { Category, Intro, Projects } from '../../types';

export const revalidate = 10;
export const dynamicParams = true;

type PageProps = {
	params: Promise<{ slug: string }>;
};

async function fetchWorkPage(locale: 'en' | 'pt') {
	if (locale === 'en') {
		const [projects, categories, intro] = await Promise.all([
			GetApi('/project/', { _embed: '', per_page: 100 }),
			GetCategoriesApi('/project-category', { per_page: 22 }),
			GetIntroApi(),
		]).catch((error) => {
			console.error('Error fetching work page', error);
			return [[], [], null] as [Projects, Category[], Intro | null];
		});

		return { projects, categories, intro };
	}

	const base = getBaseUrl();
	const headers = { Cookie: 'language=PT' };

	const [projects, categories, intro] = await Promise.all([
		fetch(`${base}/api/project`, { headers }).then((r) => r.json() as Promise<Projects>),
		fetch(`${base}/api/project/all-category`, { headers }).then((r) => r.json() as Promise<Category[]>),
		GetIntroApi({ translate: 'PT' }),
	]).catch((error) => {
		console.error('Error fetching PT work page', error);
		return [[], [], null] as [Projects, Category[], Intro | null];
	});

	return { projects, categories, intro };
}

export default async function SlugPage({ params }: PageProps) {
	const { slug } = await params;

	if (slug === 'work') {
		const lang = (await cookies()).get('language')?.value ?? '';
		const locale = lang === 'PT' ? 'pt' : 'en';
		const { projects, categories, intro } = await fetchWorkPage(locale);

		return <ProjectsListing projects={projects} categories={categories} intro={intro} locale={locale} />;
	}

	const base = getBaseUrl();
	const lang = (await cookies()).get('language')?.value ?? '';
	const cookieHeader = lang ? { Cookie: `language=${lang}` } : undefined;

	const [allPosts, allCat, allPostCat] = await Promise.all([
		fetch(`${base}/api/${slug}`, { headers: cookieHeader }).then((r) => r.json() as Promise<Projects>),
		fetch(`${base}/api/project/all-category`, { headers: cookieHeader }).then((r) => r.json() as Promise<Category[]>),
		fetch(`${base}/api/project-category/${slug}`, { headers: cookieHeader }).then((r) => r.json() as Promise<Projects>),
	]).catch((error) => {
		console.error('Error fetching slug page', error);
		return [[], [], []] as [Projects, Category[], Projects];
	});

	const api = process.env.API ?? 'https://wp.regularswitch.com';
	const pageId = allPosts?.[0]?.id;
	const metasUrl = pageId ? `${api}/wp-json/wp/v2/pages/${pageId}` : null;
	const allMetas = metasUrl ? await fetch(metasUrl).then((r) => r.json()) : null;

	return <SlugPageClient allPosts={allPosts} allPostCat={allPostCat} allCat={allCat} slug={slug} allMetas={allMetas} />;
}
