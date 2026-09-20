/**
 * Links de Contato / Newsletter / Jobs.
 * NEXT_PUBLIC_* para uso no client (footer / cookie banner).
 * Redes sociais vêm do WP (Footer → Social compartilhado) — sem defaults fixos.
 */
export function getContactEmail(): string {
	return (
		process.env.NEXT_PUBLIC_CONTACT_EMAIL?.trim() ||
		process.env.CONTACT_EMAIL?.trim() ||
		'contact@regularswitch.com'
	);
}

export function getJoinUsEmail(): string {
	return (
		process.env.NEXT_PUBLIC_JOIN_US_EMAIL?.trim() ||
		process.env.JOIN_US_EMAIL?.trim() ||
		'join-us@regularswitch.com'
	);
}

export function getContactMailto(): string {
	return `mailto:${getContactEmail()}`;
}

export function getJoinUsMailto(): string {
	return `mailto:${getJoinUsEmail()}`;
}

export function getNewsletterHref(): string {
	const href =
		process.env.NEXT_PUBLIC_NEWSLETTER_URL?.trim() ||
		process.env.NEWSLETTER_URL?.trim();

	if (href) return href;

	return `${getContactMailto()}?subject=Newsletter`;
}
