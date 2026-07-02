import { cookies } from 'next/headers';
import { getBaseUrl } from '../../../lib/getBaseUrl';
import ProjectSlugClient from '../../../components/ProjectSlugClient';

export const revalidate = 10;
export const dynamicParams = true;

type PageProps = {
	params: Promise<{ slug: string }>;
};

export default async function ProjectSlugPage({ params }: PageProps) {
	const { slug } = await params;
	const base = getBaseUrl();
	const lang = (await cookies()).get('language')?.value ?? 'PT';

	const [allPosts, allMetas] = await Promise.all([
		fetch(`${base}/api/project/${slug}`, { headers: { Cookie: `language=${lang}` } }).then((r) => r.json()),
		fetch(`${base}/api/project/all-metas`, { headers: { Cookie: `language=${lang}` } })
			.then((r) => r.json())
			.then((metas: any[]) => metas.find((m: any) => m.slug === slug) ?? null),
	]).catch((error) => {
		console.error('Error fetching project', error);
		return [[], null] as [any[], any];
	});

	return <ProjectSlugClient allPosts={allPosts} allMetas={allMetas} lang={lang} slug={slug} />;
}

