import ProjectPage from '../../../../components/Project/ProjectPage';
import { getBaseUrl } from '../../../../lib/config/getBaseUrl';
import type { ProjectMeta, Projects } from '../../../../types';

export const revalidate = 10;
export const dynamicParams = true;

type PageProps = {
	params: Promise<{ slug: string }>;
};

export default async function PtProjectSlugPage({ params }: PageProps) {
	const { slug } = await params;
	const base = getBaseUrl();
	const cookieHeader = { Cookie: 'language=PT' };

	const [allPosts, allMetas, latestProjects] = await Promise.all([
		fetch(`${base}/api/project/${slug}`, { headers: cookieHeader }).then((r) => r.json() as Promise<Projects>),
		fetch(`${base}/api/project/all-metas`, { headers: cookieHeader })
			.then((r) => r.json())
			.then((metas: ProjectMeta[]) => metas.find((m) => m.slug === slug) ?? null),
		fetch(`${base}/api/project`, { headers: cookieHeader }).then((r) => r.json() as Promise<Projects>),
	]).catch((error) => {
		console.error('Error fetching PT project', error);
		return [[], null, []] as [Projects, ProjectMeta | null, Projects];
	});

	const project = allPosts[0];
	if (!project) return null;

	return (
		<ProjectPage project={project} meta={allMetas} latestProjects={latestProjects} locale="pt" />
	);
}
