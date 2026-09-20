import type { Project, Projects } from '../../types';

/** Projeto marcado como destaque da home no CMS (`project_data.featuredOnHome`). */
export function isFeaturedOnHome(project: Project): boolean {
	return Boolean(project.project_data?.featuredOnHome);
}

/**
 * Índice do destaque na lista da home.
 * Só retorna índice se houver flag no CMS; senão -1 (sem card full-width).
 */
export function resolveFeaturedIndex(projects: Projects): number {
	if (!projects.length) return -1;
	return projects.findIndex(isFeaturedOnHome);
}

/**
 * Quantos projetos cabem em exatamente 2 linhas na home.
 * - Sem destaque: 2 × colunas (ex.: 4 em grid 2 col).
 * - Com destaque: 1 full-width + 1 linha de colunas (ex.: 3 em grid 2 col).
 */
export function homeProjectLimit(columns: 1 | 2 | 3, hasFeatured: boolean): number {
	if (columns === 1) return 2;
	return hasFeatured ? 1 + columns : 2 * columns;
}

/**
 * Garante o destaque do CMS na lista (primeiro) e corta em `max`.
 * Se ninguém estiver marcado, mantém a ordem recebida.
 */
export function pickHomeProjects(projects: Projects, max: number): Projects {
	if (max <= 0 || !projects.length) return [];

	const featured = projects.find(isFeaturedOnHome);
	if (!featured) return projects.slice(0, max);

	const rest = projects.filter((p) => p.id !== featured.id);
	return [featured, ...rest].slice(0, max);
}

/** @deprecated Use pickHomeProjects. */
export function orderHomeProjects(projects: Projects): Projects {
	return pickHomeProjects(projects, projects.length);
}

/** @deprecated Prefer resolveFeaturedIndex após pickHomeProjects (destaque fica em 0). */
export function homeFeaturedSlotIndex(count: number): number {
	if (count <= 0) return -1;
	return 0;
}
