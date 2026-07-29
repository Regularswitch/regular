export type GridSpan = 'half' | 'featured';

/** Listagem uniforme: todos os cards no mesmo tamanho. */
export function getGridSpan(_index?: number): GridSpan {
	return 'half';
}

/**
 * Home: no máximo 1 card em destaque (altura ~2×).
 * O restante fica no formato padrão (`half`).
 */
export function getHomeGridSpan(index: number, featuredIndex: number): GridSpan {
	return index === featuredIndex ? 'featured' : 'half';
}

/** Galeria do projeto: ciclo 2 colunas → largura total. */
export type GallerySpan = 'half' | 'full';

const GALLERY_CYCLE: GallerySpan[] = ['half', 'half', 'full'];

export function getGallerySpan(index: number): GallerySpan {
	return GALLERY_CYCLE[index % GALLERY_CYCLE.length] ?? 'half';
}

/** Primeira dobra da home (1 destaque + 4 padrão). */
export const INITIAL_BENTO_COUNT = 5;

/** Lotes da listagem /projects (sempre grid uniforme). */
export const BENTO_BATCH_SIZE = 6;
