/** Converte texto (possivelmente UPPERCASE do CMS) para sentence case. */
export function toSentenceCase(value: string): string {
	const trimmed = value.trim();
	if (!trimmed) return value;

	const lower = trimmed.toLocaleLowerCase('pt-BR');
	return lower.charAt(0).toLocaleUpperCase('pt-BR') + lower.slice(1);
}

/** Aplica sentence case preservando tags HTML simples no título. */
export function toSentenceCaseHtml(html: string): string {
	const trimmed = html.trim();
	if (!trimmed) return html;

	if (!/<[^>]+>/.test(trimmed)) {
		return toSentenceCase(trimmed);
	}

	return trimmed.replace(/>([^<]*)</g, (match, text: string) => {
		if (!text.trim()) return match;
		return `>${toSentenceCase(text)}<`;
	}).replace(/^([^<]+)/, (match) => toSentenceCase(match));
}
