import { cookies } from 'next/headers';
import type { Metadata } from 'next';

import { GetApi, GetMeta } from '../../../components/ApiWp';
import ProjectPage from '../../../components/Project/ProjectPage';
import { excludeProjectTranslationTwins } from '../../../lib/projects/sort';
import { fetchProjectSeo } from '../../../lib/seo/fetch';
import { buildPageMetadata, DEFAULT_SITE_NAME } from '../../../lib/seo/metadata';
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

	const [allPosts, allMetas, latestProjects] = await Promise.all([
		GetApi('/project/', { slug, _embed: '', translate: lang, meta: '1' }),
		GetMeta(),
		GetApi('/project/', { _embed: '', per_page: 100, translate: lang }),
	]).catch((error) => {
		console.error('Error fetching project', error);
		return [[], [], []] as [Projects, ProjectMeta[], Projects];
	});

	const project = allPosts[0];
	if (!project) return null;

	const meta = allMetas.find((item) => item.slug === slug) ?? null;

	return (
		<ProjectPage
			project={project}
			meta={meta}
			latestProjects={excludeProjectTranslationTwins(latestProjects)}
			locale={locale}
		/>
	);
}
