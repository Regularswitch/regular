/**
 * Cores do cursor customizado.
 * Prefere a paleta do blob (WP); a cada visita sorteia uma cor diferente da anterior.
 */
export const CURSOR_COLORS = [
	'#7B00FF',
	'#D400FF',
	'#FF5FAF',
	'#304FFE',
	'#FFD500',
	'#4af117',
	'#fe4857',
] as const;

const LAST_COLOR_KEY = 'rs-cursor-color';

function isHexColor(value: string): boolean {
	return /^#[0-9a-fA-F]{3,6}$/.test(value);
}

function normalizeHex(value: string): string {
	const trimmed = value.trim();
	if (/^#[0-9a-fA-F]{6}$/.test(trimmed)) return trimmed.toLowerCase();
	if (/^#[0-9a-fA-F]{3}$/.test(trimmed)) {
		return `#${trimmed[1]}${trimmed[1]}${trimmed[2]}${trimmed[2]}${trimmed[3]}${trimmed[3]}`.toLowerCase();
	}
	return trimmed.toLowerCase();
}

export function pickRandomCursorColor(palette?: string[] | null): string {
	const fromPalette = Array.isArray(palette)
		? palette.filter((color): color is string => typeof color === 'string' && isHexColor(color)).map(normalizeHex)
		: [];
	const colors = fromPalette.length >= 2 ? fromPalette : [...CURSOR_COLORS].map(normalizeHex);

	if (typeof window === 'undefined') {
		return colors[0] ?? CURSOR_COLORS[0];
	}

	const last = window.sessionStorage.getItem(LAST_COLOR_KEY);
	const pool = colors.filter((color) => color !== last);
	const choices = pool.length > 0 ? pool : colors;
	const color = choices[Math.floor(Math.random() * choices.length)] ?? colors[0];

	window.sessionStorage.setItem(LAST_COLOR_KEY, color);
	return color;
}
