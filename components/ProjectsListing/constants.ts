export type GridSpan = 'half' | 'full';

/** Ciclo contínuo: 2 colunas → full width → 2 colunas → full width… */
const BENTO_CYCLE: GridSpan[] = ['half', 'half', 'full'];

export function getGridSpan(index: number): GridSpan {
	return BENTO_CYCLE[index % BENTO_CYCLE.length] ?? 'half';
}

/** Primeira dobra — igual ao mockup (2 + full + 2). */
export const INITIAL_PROJECTS_COUNT = 5;

/** Cada clique em "Veja mais" adiciona um ciclo completo do bento. */
export const PROJECTS_BATCH_SIZE = BENTO_CYCLE.length;
