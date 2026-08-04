import type { Project, Projects } from '../../types';

/** Projeto marcado como destaque da home no CMS (`project_data.featuredOnHome`). */
export function isFeaturedOnHome(project: Project): boolean {
	return Boolean(project.project_data?.featuredOnHome);
}

/**
 * Índice do único destaque na lista da home.
 * Prioriza o flag do CMS; se ninguém estiver marcado, usa o 3º item (meio do bento).
 */
export function resolveFeaturedIndex(projects: Projects): number {
	if (!projects.length) return -1;

	const flagged = projects.findIndex(isFeaturedOnHome);
	if (flagged >= 0) return flagged;

	// Padrão visual: half | half | featured | half | half
	return Math.min(2, projects.length - 1);
}

/**
 * Posiciona o destaque no meio do bento (índice 2):
 * half | half | featured | half | half
 */
export function orderHomeProjects(projects: Projects): Projects {
	if (projects.length <= 1) return projects;

	const from = resolveFeaturedIndex(projects);
	const next = [...projects];
	const [featured] = next.splice(from, 1);
	const insertAt = Math.min(2, next.length);
	next.splice(insertAt, 0, featured);
	return next;
}

/** Índice do destaque após `orderHomeProjects`. */
export function homeFeaturedSlotIndex(count: number): number {
	if (count <= 0) return -1;
	if (count <= 2) return 0;
	return 2;
}
