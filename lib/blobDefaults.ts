import type { BlobVisual } from '../types';

export const DEFAULT_BLOB_VISUAL: BlobVisual = {
	color1: '#fe4857',
	color2: '#4af117',
	palette: ['#7B00FF', '#D400FF', '#FF5FAF', '#304FFE', '#FFD500', '#4af117', '#fe4857'],
};

export function resolveBlobVisual(fromWp: BlobVisual | null | undefined): BlobVisual {
	if (!fromWp) {
		return DEFAULT_BLOB_VISUAL;
	}

	const palette = Array.isArray(fromWp.palette)
		? fromWp.palette.filter((color): color is string => typeof color === 'string' && /^#[0-9a-fA-F]{3,6}$/.test(color))
		: [];

	return {
		color1: /^#[0-9a-fA-F]{3,6}$/.test(fromWp.color1) ? fromWp.color1 : DEFAULT_BLOB_VISUAL.color1,
		color2: /^#[0-9a-fA-F]{3,6}$/.test(fromWp.color2) ? fromWp.color2 : DEFAULT_BLOB_VISUAL.color2,
		palette: palette.length >= 2 ? palette : DEFAULT_BLOB_VISUAL.palette,
	};
}

/** 4 cores do indicador ativo do menu (violeta, rosa, azul). */
export const NAV_ACTIVE_LINE_COLORS = ['#7B00FF', '#D400FF', '#FF5FAF', '#304FFE'] as const;

/** Gradiente horizontal do menu (loop contínuo para animação). */
export function buildNavActiveGradient(): string {
	const loop = [...NAV_ACTIVE_LINE_COLORS, NAV_ACTIVE_LINE_COLORS[0]];
	const stops = loop
		.map((color, index) => {
			const pct = (index / (loop.length - 1)) * 100;
			return `${color} ${pct.toFixed(2)}%`;
		})
		.join(', ');

	return `linear-gradient(90deg, ${stops})`;
}

/** @deprecated Use buildNavActiveGradient */
export function buildBlobNavGradient(_palette?: string[]): string {
	return buildNavActiveGradient();
}
