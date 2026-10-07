import type { BlobVisual } from '../../types';

export const DEFAULT_BLOB_VISUAL: BlobVisual = {
	enabled: false,
	color1: '#fe4857',
	color2: '#4af117',
	menuBg: '#e8ebf1',
	menuFg: '#000000',
	palette: ['#7B00FF', '#D400FF', '#FF5FAF', '#304FFE', '#FFD500', '#4af117', '#fe4857'],
	video: '',
	poster: '',
};

function isHexColor(value: string): boolean {
	return /^#[0-9a-fA-F]{3,6}$/.test(value);
}

export function resolveBlobVisual(fromWp: BlobVisual | null | undefined): BlobVisual {
	if (!fromWp) {
		return DEFAULT_BLOB_VISUAL;
	}

	const palette = Array.isArray(fromWp.palette)
		? fromWp.palette.filter((color): color is string => typeof color === 'string' && isHexColor(color))
		: [];

	const video = typeof fromWp.video === 'string' ? fromWp.video.trim() : '';
	const poster = typeof fromWp.poster === 'string' ? fromWp.poster.trim() : '';

	return {
		enabled: Boolean(fromWp.enabled),
		color1: isHexColor(fromWp.color1) ? fromWp.color1 : DEFAULT_BLOB_VISUAL.color1,
		color2: isHexColor(fromWp.color2) ? fromWp.color2 : DEFAULT_BLOB_VISUAL.color2,
		menuBg:
			typeof fromWp.menuBg === 'string' && isHexColor(fromWp.menuBg)
				? fromWp.menuBg
				: DEFAULT_BLOB_VISUAL.menuBg,
		menuFg:
			typeof fromWp.menuFg === 'string' && isHexColor(fromWp.menuFg)
				? fromWp.menuFg
				: DEFAULT_BLOB_VISUAL.menuFg,
		palette: palette.length >= 2 ? palette : DEFAULT_BLOB_VISUAL.palette,
		video,
		poster,
	};
}

/** 4 cores do indicador ativo do menu (fallback se a paleta do WP não vier). */
export const NAV_ACTIVE_LINE_COLORS = ['#7B00FF', '#D400FF', '#FF5FAF', '#304FFE'] as const;

/** Gradiente horizontal a partir da paleta do blob (loop contínuo para animação). */
export function buildNavActiveGradient(palette?: string[] | null): string {
	const fromPalette = Array.isArray(palette)
		? palette.filter((color): color is string => typeof color === 'string' && isHexColor(color))
		: [];
	const colors =
		fromPalette.length >= 2 ? fromPalette : [...NAV_ACTIVE_LINE_COLORS];
	const loop = [...colors, colors[0]];
	const stops = loop
		.map((color, index) => {
			const pct = (index / (loop.length - 1)) * 100;
			return `${color} ${pct.toFixed(2)}%`;
		})
		.join(', ');

	return `linear-gradient(90deg, ${stops})`;
}

/** @deprecated Use buildNavActiveGradient */
export function buildBlobNavGradient(palette?: string[]): string {
	return buildNavActiveGradient(palette);
}
