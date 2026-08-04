/**
 * Estilo do cursor — troque `CURSOR_STYLE` para testar.
 * - `blob`: gradiente animado igual ao blob / nav
 * - cores sólidas: yellow | magenta | green | blue
 */
export const CURSOR_VARIANTS = {
	yellow: 'rgb(255, 255, 0)',
	magenta: 'rgb(255, 0, 255)',
	green: 'rgb(0, 255, 0)',
	blue: 'rgb(0, 0, 255)',
} as const;

export type CursorVariant = keyof typeof CURSOR_VARIANTS;
export type CursorStyle = 'blob' | CursorVariant;

/** Altere aqui para comparar. */
export const CURSOR_STYLE: CursorStyle = 'green';

export function isBlobCursor(style: CursorStyle = CURSOR_STYLE): boolean {
	return style === 'blob';
}

export function getCursorColor(style: CursorStyle = CURSOR_STYLE): string | null {
	if (style === 'blob') return null;
	return CURSOR_VARIANTS[style];
}
