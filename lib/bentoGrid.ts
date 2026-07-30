export type GridSpan = 'half' | 'third' | 'featured';

/**
 * Listagem /projects: ciclo de 5 — 3 cards (1/3) + 2 cards (1/2).
 * Desktop usa grid de 6 colunas (third=span 2, half=span 3).
 */
export function getGridSpan(index: number): GridSpan {
	return index % 5 < 3 ? 'third' : 'half';
}

/**
 * Home: no máximo 1 card em destaque (altura ~2×).
 * O restante fica no formato padrão (`half`).
 */
export function getHomeGridSpan(index: number, featuredIndex: number): GridSpan {
	return index === featuredIndex ? 'featured' : 'half';
}

/** Primeira dobra: 1 ciclo 3+2 (listagem) ou 1 destaque + 4 (home). */
export const INITIAL_BENTO_COUNT = 5;

/** Lotes da listagem /projects (múltiplos do ciclo 3+2). */
export const BENTO_BATCH_SIZE = 5;
