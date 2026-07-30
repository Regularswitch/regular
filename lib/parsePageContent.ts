import type { AboutAccordionSection } from './aboutDefaults';
import type { ContactBlock } from './contactDefaults';
import type { ProjectAccordionSection } from './parseProjectContent';
import { removeImagesFromHtml } from './parseProjectContent';

function stripHtml(html: string) {
	return html.replace(/<[^>]+>/g, ' ').replace(/\s+/g, ' ').trim();
}

function normalizeImageUrl(url: string) {
	return url.replace(/^rhttps/i, 'https').replace(/&amp;/g, '&');
}

export function extractHeroImageFromHtml(html: string): string | undefined {
	const images: Array<{ url: string; score: number }> = [];
	const figureRegex = /<figure[^>]*>([\s\S]*?)<\/figure>/gi;
	let figureMatch: RegExpExecArray | null;

	while ((figureMatch = figureRegex.exec(html)) !== null) {
		const block = figureMatch[1];
		const imgMatch = block.match(/<img[^>]+src=["']([^"']+)["'][^>]*>/i);
		if (!imgMatch) continue;

		const widthMatch = block.match(/width=["'](\d+)["']/i);
		const width = widthMatch ? Number(widthMatch[1]) : 0;
		const isWide = /alignwide|size-large/i.test(figureMatch[0]);
		const score = width + (isWide ? 500 : 0);

		images.push({ url: normalizeImageUrl(imgMatch[1]), score });
	}

	if (!images.length) {
		const imgMatch = html.match(/<img[^>]+src=["']([^"']+)["']/i);
		return imgMatch ? normalizeImageUrl(imgMatch[1]) : undefined;
	}

	images.sort((a, b) => b.score - a.score);
	return images[0]?.url;
}

/** Extrai o conteúdo interno do primeiro `<h2>`, preservando HTML (ex.: `<strong>`). */
export function parsePageHeadline(html: string): string | null {
	const h2Match = html.match(/<h2[^>]*>([\s\S]*?)<\/h2>/i);
	if (!h2Match) return null;

	const inner = h2Match[1].trim();
	if (!inner || !stripHtml(inner)) return null;

	return inner;
}

/** Conteúdo entre o primeiro `<h2>` e o primeiro `<h3>`. */
export function parsePageBodyAfterHeadline(html: string): string | null {
	const cleaned = removeImagesFromHtml(html);
	const h2Match = cleaned.match(/<h2[^>]*>[\s\S]*?<\/h2>/i);
	if (!h2Match || h2Match.index === undefined) return null;

	const start = h2Match.index + h2Match[0].length;
	const h3Match = cleaned.slice(start).match(/<h3[^>]*>/i);
	const end = h3Match?.index !== undefined ? start + h3Match.index : cleaned.length;
	const body = cleaned.slice(start, end).trim();

	return body || null;
}

export function parsePageAccordionFromHeadings(
	html: string,
	headingTags: Array<'h2' | 'h3' | 'h4'> = ['h3'],
): ProjectAccordionSection[] {
	const cleaned = removeImagesFromHtml(html);
	const tagPattern = headingTags.join('|');
	const headingRegex = new RegExp(`<(${tagPattern})[^>]*>([\\s\\S]*?)<\\/\\1>`, 'gi');
	const matches = [...cleaned.matchAll(headingRegex)];

	const sections: ProjectAccordionSection[] = [];

	for (let i = 0; i < matches.length; i += 1) {
		const title = stripHtml(matches[i][2]);
		if (!title) continue;

		const start = (matches[i].index ?? 0) + matches[i][0].length;
		const end = matches[i + 1]?.index ?? cleaned.length;
		const body = cleaned.slice(start, end).trim();

		sections.push({ title, body });
	}

	return sections.filter((section) => section.title);
}

export function parsePageBlocksFromHeadings(html: string): ContactBlock[] {
	return parsePageAccordionFromHeadings(html, ['h3']).map((section) => ({
		title: section.title,
		body: section.body,
	}));
}

export function mergeAboutAccordionSections(
	parsed: ProjectAccordionSection[],
	defaults: AboutAccordionSection[],
): AboutAccordionSection[] {
	if (!parsed.length) return defaults;

	return parsed.map((section, index) => {
		const fallback = defaults.find((item) => item.title === section.title) ?? defaults[index];

		return {
			...section,
			imageProjectSlug: fallback?.imageProjectSlug,
		};
	});
}
