export type GridSpan = 'half' | 'third' | 'quarter' | 'featured' | 'full';

/**
 * Listagem /projects: todas as linhas com 4 cards (1/4).
 * Desktop usa grid de 12 colunas (quarter = span 3).
 */
export function getGridSpan(_index: number): GridSpan {
	return 'quarter';
}

/**
 * Home: cards nas colunas do CMS; o índice do destaque (CMS) ocupa largura total.
 * `featuredIndex` negativo desativa o span de destaque.
 * `columns` vem do CMS (site-ui layout).
 */
export function getHomeGridSpan(
	index: number,
	featuredIndex: number,
	columns: 1 | 2 | 3 = 2,
): GridSpan {
	if (columns === 1) return 'full';
	if (featuredIndex >= 0 && index === featuredIndex) return 'featured';
	if (columns === 3) return 'third';
	return 'half';
}

/** Listagem /projects: 8 linhas × 4 cards ao abrir. */
export const INITIAL_BENTO_COUNT = 32;

/** Lotes da listagem /projects: +1 linha de 4 projetos. */
export const BENTO_BATCH_SIZE = 4;
