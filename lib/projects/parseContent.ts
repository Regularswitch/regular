export type ProjectAccordionSection = {
	title: string;
	body: string;
};

const ACCORDION_PATTERN = /^(contexto|context|dire[cç][aã]o\s*creativa|creative\s*direction|solu[cç][aã]o|solution|impacto|impact)/i;

function normalizeImageUrl(url: string) {
	return url.replace(/^rhttps/i, 'https').replace(/&amp;/g, '&');
}

function stripHtml(html: string) {
	return html.replace(/<[^>]+>/g, ' ').replace(/\s+/g, ' ').trim();
}

/** Remove cores inline do rich text do WP (evita preto no tema dark). */
export function stripInlineColors(html: string): string {
	return html.replace(/\sstyle=(["'])([\s\S]*?)\1/gi, (_match, quote: string, style: string) => {
		const cleaned = style
			.split(';')
			.map((part) => part.trim())
			.filter((part) => {
				if (!part) return false;
				const prop = part.split(':')[0]?.trim().toLowerCase() ?? '';
				return prop !== 'color' && prop !== 'background' && prop !== 'background-color';
			})
			.join('; ');
		return cleaned ? ` style=${quote}${cleaned}${quote}` : '';
	});
}

export function extractImagesFromHtml(html: string): string[] {
	const images: string[] = [];
	const regex = /<img[^>]+src=["']([^"']+)["']/gi;
	let match: RegExpExecArray | null;

	while ((match = regex.exec(html)) !== null) {
		const src = normalizeImageUrl(match[1]);
		if (src.startsWith('http') && !images.includes(src)) images.push(src);
	}

	return images;
}

export function removeImagesFromHtml(html: string) {
	return html
		.replace(/<figure[^>]*>[\s\S]*?<\/figure>/gi, '')
		.replace(/<img[^>]*>/gi, '')
		.trim();
}

export function parseAccordionSections(
	html: string,
	locale: 'en' | 'pt',
): ProjectAccordionSection[] {
	const defaults =
		locale === 'pt'
			? ['Contexto', 'Direção criativa', 'Solução', 'Impacto']
			: ['Context', 'Creative direction', 'Solution', 'Impact'];

	const cleaned = removeImagesFromHtml(html);
	const headingRegex = /<(h[2-6])[^>]*>([\s\S]*?)<\/\1>/gi;
	const matches = [...cleaned.matchAll(headingRegex)];

	const sections: ProjectAccordionSection[] = [];

	for (let i = 0; i < matches.length; i += 1) {
		const title = stripHtml(matches[i][2]);
		if (!ACCORDION_PATTERN.test(title)) continue;

		const start = (matches[i].index ?? 0) + matches[i][0].length;
		const end = matches[i + 1]?.index ?? cleaned.length;
		const body = stripInlineColors(cleaned.slice(start, end).trim());

		if (body) sections.push({ title, body });
	}

	if (sections.length) return sections;

	const fallbackBody = stripInlineColors(cleaned.trim());
	if (!fallbackBody) {
		return defaults.map((title) => ({ title, body: '' }));
	}

	return [{ title: defaults[0], body: fallbackBody }];
}
