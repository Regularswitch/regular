/**
 * Links de Contato / Newsletter — troque via env até o cliente confirmar.
 * NEXT_PUBLIC_* para uso no client (footer).
 */
export function getContactMailto(): string {
	const email =
		process.env.NEXT_PUBLIC_CONTACT_EMAIL?.trim() ||
		process.env.CONTACT_EMAIL?.trim() ||
		'contact@regularswitch.com';

	return `mailto:${email}`;
}

export function getNewsletterHref(): string {
	const href =
		process.env.NEXT_PUBLIC_NEWSLETTER_URL?.trim() ||
		process.env.NEWSLETTER_URL?.trim();

	if (href) return href;

	return `${getContactMailto()}?subject=Newsletter`;
}
