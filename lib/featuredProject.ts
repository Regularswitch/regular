import type { Project, Projects } from '../types';

/** Projeto marcado como destaque da home no CMS (`project_data.featuredOnHome`). */
export function isFeaturedOnHome(project: Project): boolean {
	return Boolean(project.project_data?.featuredOnHome);
}

/**
 * Índice do único destaque na lista da home.
 * Prioriza o flag do CMS; se ninguém estiver marcado, usa o primeiro item.
 */
export function resolveFeaturedIndex(projects: Projects): number {
	if (!projects.length) return -1;

	const flagged = projects.findIndex(isFeaturedOnHome);
	return flagged >= 0 ? flagged : 0;
}

/** Garante no máximo 1 destaque: o primeiro flagado (ou o primeiro da lista). */
export function orderHomeProjects(projects: Projects): Projects {
	if (projects.length <= 1) return projects;

	const featuredIndex = resolveFeaturedIndex(projects);
	if (featuredIndex <= 0) return projects;

	const next = [...projects];
	const [featured] = next.splice(featuredIndex, 1);
	return [featured, ...next];
}
