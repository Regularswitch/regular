/**
 * Remove markup de layout colado acidentalmente do front no editor WP.
 */

function stripTags(html: string): string {
	return html.replace(/<[^>]+>/g, '').replace(/&nbsp;/gi, ' ').trim();
}

function firstInnerHtml(html: string, classFragment: string): string | null {
	const pattern = new RegExp(
		`<div[^>]*class="[^"]*${classFragment}[^"]*"[^>]*>([\\s\\S]*?)<\\/div>`,
		'i',
	);
	const match = html.match(pattern);

	if (!match?.[1]) {
		return null;
	}

	const inner = match[1].trim();
	return stripTags(inner) ? inner : null;
}

export function sanitizeAboutHeadline(html: string): string {
	const trimmed = html.trim();
	if (!trimmed) return '';

	const fromWrapper = firstInnerHtml(trimmed, 'intro-headline');
	const content = fromWrapper
		?? trimmed.replace(/<div[^>]*about-body[^>]*>[\s\S]*?<\/div>/gi, '').trim();

	return content.replace(/\u00A0|&nbsp;/gi, ' ');
}

export function sanitizeAboutBody(html: string): string {
	const trimmed = html.trim();
	if (!trimmed) return '';

	const fromWrapper = firstInnerHtml(trimmed, 'about-body');
	if (fromWrapper) {
		return fromWrapper;
	}

	return trimmed
		.replace(/<section[^>]*>[\s\S]*?<\/section>/gi, '')
		.replace(/<div[^>]*class="[^"]*px-7[^"]*"[^>]*>[\s\S]*$/gi, '')
		.trim();
}
