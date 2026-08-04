/**
 * Cores sólidas do cursor customizado.
 * A cada reload uma cor é sorteada (evita repetir a anterior).
 */
export const CURSOR_COLORS = [
	'rgb(255, 255, 0)', // yellow
	'rgb(255, 0, 255)', // magenta
	'rgb(0, 255, 0)', // green
	'rgb(0, 0, 255)', // blue
] as const;

const LAST_COLOR_KEY = 'rs-cursor-color';

export function pickRandomCursorColor(): string {
	if (typeof window === 'undefined') {
		return CURSOR_COLORS[0];
	}

	const last = window.sessionStorage.getItem(LAST_COLOR_KEY);
	const pool = CURSOR_COLORS.filter((color) => color !== last);
	const choices = pool.length > 0 ? pool : CURSOR_COLORS;
	const color = choices[Math.floor(Math.random() * choices.length)] ?? CURSOR_COLORS[0];

	window.sessionStorage.setItem(LAST_COLOR_KEY, color);
	return color;
}
