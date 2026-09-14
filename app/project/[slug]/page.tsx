import { cookies } from 'next/headers';
import type { Metadata } from 'next';

import { GetApi, GetMeta } from '../../../components/ApiWp';
import ProjectPage from '../../../components/Project/ProjectPage';
import JsonLd from '../../../components/Seo/JsonLd';
import { excludeProjectTranslationTwins } from '../../../lib/projects/sort';
import { fetchProjectSeo } from '../../../lib/seo/fetch';
import { buildPageMetadata, DEFAULT_SITE_NAME } from '../../../lib/seo/metadata';
import { buildCreativeWorkJsonLd } from '../../../lib/seo/schema';
import type { ProjectMeta, Projects } from '../../../types';

export const revalidate = 10;
export const dynamicParams = true;

type PageProps = {
	params: Promise<{ slug: string }>;
};

export async function generateMetadata({ params }: PageProps): Promise<Metadata> {
	const { slug } = await params;
	const lang = (await cookies()).get('language')?.value ?? '';
	const locale = lang === 'PT' ? 'pt' : 'en';
	const seo = await fetchProjectSeo(slug, locale);
	const fallbackTitle = seo.projectTitle
		? `${seo.projectTitle} | ${DEFAULT_SITE_NAME}`
		: DEFAULT_SITE_NAME;

	return buildPageMetadata(seo, {
		fallbackTitle,
		locale,
		path: `/project/${slug}`,
	});
}

export default async function ProjectSlugPage({ params }: PageProps) {
	const { slug } = await params;
	const lang = (await cookies()).get('language')?.value ?? '';
	const locale = lang === 'PT' ? 'pt' : 'en';

	const [allPosts, allMetas, latestProjects, seo] = await Promise.all([
		GetApi('/project/', { slug, _embed: '', translate: lang, meta: '1' }),
		GetMeta(),
		GetApi('/project/', { _embed: '', per_page: 100, translate: lang }),
		fetchProjectSeo(slug, locale),
	]).catch((error) => {
		console.error('Error fetching project', error);
		return [[], [], [], {}] as [
			Projects,
			ProjectMeta[],
			Projects,
			Awaited<ReturnType<typeof fetchProjectSeo>>,
		];
	});

	const project = allPosts[0];
	if (!project) return null;

	const meta = allMetas.find((item) => item.slug === slug) ?? null;
	const creativeWork = buildCreativeWorkJsonLd({
		name: project.title ?? slug,
		description: project.more?.replace(/<[^>]+>/g, ' ').trim(),
		url: `/project/${slug}`,
		dateCreated: project.created_at ? String(project.created_at).slice(0, 10) : undefined,
		seo,
	});

	return (
		<>
			<JsonLd id="project-creativework-jsonld" data={creativeWork} />
			<ProjectPage
				project={project}
				meta={meta}
				latestProjects={excludeProjectTranslationTwins(latestProjects)}
				locale={locale}
			/>
		</>
	);
}
