export type GridSpan = 'half' | 'third' | 'quarter' | 'featured' | 'full';

/**
 * Listagem /projects: 1ª linha 3 cards (1/3) · demais 4 cards (1/4).
 * Desktop usa grid de 12 colunas (third=span 4, quarter=span 3).
 */
export function getGridSpan(index: number): GridSpan {
	return index < 3 ? 'third' : 'quarter';
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

/** Listagem /projects: 1ª linha (3) + 2ª linha (4). */
export const INITIAL_BENTO_COUNT = 7;

/** Lotes da listagem /projects: +1 linha de 4 projetos. */
export const BENTO_BATCH_SIZE = 4;
