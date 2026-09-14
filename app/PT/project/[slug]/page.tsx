import type { Metadata } from 'next';

import { GetApi, GetCategoriesApi, GetMeta } from '../../../../components/ApiWp';
import ProjectPage from '../../../../components/Project/ProjectPage';
import JsonLd from '../../../../components/Seo/JsonLd';
import { excludeProjectTranslationTwins } from '../../../../lib/projects/sort';
import { fetchProjectSeo } from '../../../../lib/seo/fetch';
import { buildPageMetadata, DEFAULT_SITE_NAME } from '../../../../lib/seo/metadata';
import { buildCreativeWorkJsonLd } from '../../../../lib/seo/schema';
import type { Category, ProjectMeta, Projects } from '../../../../types';

export const revalidate = 10;
export const dynamicParams = true;

type PageProps = {
	params: Promise<{ slug: string }>;
};

export async function generateMetadata({ params }: PageProps): Promise<Metadata> {
	const { slug } = await params;
	const canonicalSlug = slug.replace(/-pt$/i, '');
	const seo = await fetchProjectSeo(canonicalSlug, 'pt');
	const fallbackTitle = seo.projectTitle
		? `${seo.projectTitle} | ${DEFAULT_SITE_NAME}`
		: DEFAULT_SITE_NAME;

	return buildPageMetadata(seo, {
		fallbackTitle,
		locale: 'pt',
		path: `/PT/project/${canonicalSlug}`,
	});
}

export default async function PtProjectSlugPage({ params }: PageProps) {
	const { slug } = await params;
	const canonicalSlug = slug.replace(/-pt$/i, '');

	const [allPosts, allMetas, latestProjects, categories, seo] = await Promise.all([
		GetApi('/project/', { slug: canonicalSlug, _embed: '', translate: 'PT', meta: '1' }),
		GetMeta(),
		GetApi('/project/', { _embed: '', per_page: 100, translate: 'PT' }),
		GetCategoriesApi('/project-category', { per_page: 100, translate: 'PT' }),
		fetchProjectSeo(canonicalSlug, 'pt'),
	]).catch((error) => {
		console.error('Error fetching PT project', error);
		return [[], [], [], [], {}] as [
			Projects,
			ProjectMeta[],
			Projects,
			Category[],
			Awaited<ReturnType<typeof fetchProjectSeo>>,
		];
	});

	const project = allPosts[0];
	if (!project) return null;

	const meta = allMetas.find((item) => item.slug === canonicalSlug) ?? null;
	const creativeWork = buildCreativeWorkJsonLd({
		name: project.title ?? canonicalSlug,
		description: project.more?.replace(/<[^>]+>/g, ' ').trim(),
		url: `/PT/project/${canonicalSlug}`,
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
				categories={categories}
				locale="pt"
			/>
		</>
	);
}
