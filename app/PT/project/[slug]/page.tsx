import { GetApi, GetMeta } from '../../../../components/ApiWp';
import ProjectPage from '../../../../components/Project/ProjectPage';
import { excludeProjectTranslationTwins } from '../../../../lib/projects/sort';
import type { ProjectMeta, Projects } from '../../../../types';

export const revalidate = 10;
export const dynamicParams = true;

type PageProps = {
	params: Promise<{ slug: string }>;
};

export default async function PtProjectSlugPage({ params }: PageProps) {
	const { slug } = await params;
	const canonicalSlug = slug.replace(/-pt$/i, '');

	const [allPosts, allMetas, latestProjects] = await Promise.all([
		GetApi('/project/', { slug: canonicalSlug, _embed: '', translate: 'PT', meta: '1' }),
		GetMeta(),
		GetApi('/project/', { _embed: '', per_page: 100, translate: 'PT' }),
	]).catch((error) => {
		console.error('Error fetching PT project', error);
		return [[], [], []] as [Projects, ProjectMeta[], Projects];
	});

	const project = allPosts[0];
	if (!project) return null;

	const meta = allMetas.find((item) => item.slug === canonicalSlug) ?? null;

	return (
		<ProjectPage
			project={project}
			meta={meta}
			latestProjects={excludeProjectTranslationTwins(latestProjects)}
			locale="pt"
		/>
	);
}
