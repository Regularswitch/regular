export type GridSpan = 'half' | 'third' | 'featured' | 'full';

/**
 * Listagem /projects: ciclo de 5 — 3 cards (1/3) + 2 cards (1/2).
 * Desktop usa grid de 6 colunas (third=span 2, half=span 3).
 */
export function getGridSpan(index: number): GridSpan {
	return index % 5 < 3 ? 'third' : 'half';
}

/**
 * Home: no máximo 1 card em destaque (largura total → altura ~2×).
 * `columns` vem do CMS (site-ui layout).
 */
export function getHomeGridSpan(
	index: number,
	featuredIndex: number,
	columns: 1 | 2 | 3 = 2,
): GridSpan {
	if (columns === 1) return 'full';
	if (index === featuredIndex) return 'featured';
	if (columns === 3) return 'third';
	return 'half';
}

/** Primeira dobra: 1 ciclo 3+2 (listagem) ou 1 destaque + 4 (home). */
export const INITIAL_BENTO_COUNT = 5;

/** Lotes da listagem /projects (múltiplos do ciclo 3+2). */
export const BENTO_BATCH_SIZE = 5;
