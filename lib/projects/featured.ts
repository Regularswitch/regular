import type { Project, Projects } from '../../types';

/** Projeto marcado como destaque da home no CMS (`project_data.featuredOnHome`). */
export function isFeaturedOnHome(project: Project): boolean {
	return Boolean(project.project_data?.featuredOnHome);
}

/**
 * Índice do único destaque na lista da home.
 * Prioriza o flag do CMS; se ninguém estiver marcado, 0 (mantém ordem por data).
 */
export function resolveFeaturedIndex(projects: Projects): number {
	if (!projects.length) return -1;

	const flagged = projects.findIndex(isFeaturedOnHome);
	if (flagged >= 0) return flagged;

	return 0;
}

/**
 * Coloca o destaque do CMS no início da lista da home
 * (cards iguais em 2 linhas — sem slot full-width no meio).
 */
export function orderHomeProjects(projects: Projects): Projects {
	if (projects.length <= 1) return projects;

	const from = resolveFeaturedIndex(projects);
	if (from <= 0) return projects;

	const next = [...projects];
	const [featured] = next.splice(from, 1);
	return [featured, ...next];
}

/** @deprecated Home não usa mais slot full-width; mantido por compat. */
export function homeFeaturedSlotIndex(count: number): number {
	if (count <= 0) return -1;
	return 0;
}
