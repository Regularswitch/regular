import { cookies } from 'next/headers';
import SlugPageClient from '../../components/SlugPageClient';
import { getBaseUrl } from '../../lib/getBaseUrl';
import type { Category, Projects } from '../../types';

export const revalidate = 10;
export const dynamicParams = true;

type PageProps = {
	params: Promise<{ slug: string }>;
};

export default async function SlugPage({ params }: PageProps) {
	const { slug } = await params;
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

	// metas are fetched directly from WP in the legacy pages router; keep compatible payload.
	const api = process.env.API ?? 'https://wp.regularswitch.com';
	const pageId = allPosts?.[0]?.id;
	const metasUrl = pageId ? `${api}/wp-json/wp/v2/pages/${pageId}` : null;
	const allMetas = metasUrl ? await fetch(metasUrl).then((r) => r.json()) : null;

	return <SlugPageClient allPosts={allPosts} allPostCat={allPostCat} allCat={allCat} slug={slug} allMetas={allMetas} />;
}

