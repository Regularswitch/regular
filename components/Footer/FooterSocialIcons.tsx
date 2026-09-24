import type { IconType } from 'react-icons';
import {
	FaBehance,
	FaFacebookF,
	FaInstagram,
	FaLinkedinIn,
	FaLocationDot,
	FaTiktok,
	FaXTwitter,
	FaYoutube,
} from 'react-icons/fa6';

export type FooterSocialItem = {
	network: string;
	href: string;
	label?: string;
};

export const SOCIAL_ICONS: Record<string, IconType> = {
	instagram: FaInstagram,
	linkedin: FaLinkedinIn,
	youtube: FaYoutube,
	tiktok: FaTiktok,
	x: FaXTwitter,
	twitter: FaXTwitter,
	facebook: FaFacebookF,
	behance: FaBehance,
	location: FaLocationDot,
	local: FaLocationDot,
	map: FaLocationDot,
};

export const SOCIAL_LABELS: Record<string, string> = {
	instagram: 'Instagram',
	linkedin: 'LinkedIn',
	youtube: 'YouTube',
	tiktok: 'TikTok',
	x: 'X',
	twitter: 'X',
	facebook: 'Facebook',
	behance: 'Behance',
	location: 'Local',
	local: 'Local',
	map: 'Local',
};

/** Redes do painel gradiente do menu mobile (editáveis no Footer do WP). */
export const MOBILE_MENU_SOCIAL_NETWORKS = ['instagram', 'linkedin', 'location'] as const;

export function normalizeSocialNetwork(network: string): string {
	return network.trim().toLowerCase();
}

export default function FooterSocialIcons({
	links,
	title = 'Social',
}: {
	links: FooterSocialItem[];
	title?: string;
}) {
	const visible = links.filter((item) => item.href?.trim());
	if (!visible.length) return null;

	return (
		<div className="site-footer-social">
			<p className="site-footer-social-title font-hk text-xl font-medium text-(--fg)">{title}</p>
			<nav className="site-footer-social-links" aria-label={title}>
				{visible.map((item) => {
					const network = normalizeSocialNetwork(item.network);
					const Icon = SOCIAL_ICONS[network];
					if (!Icon) return null;
					const label = item.label?.trim() || SOCIAL_LABELS[network] || network;
					const href = item.href.trim();

					return (
						<a
							key={`${network}-${href}`}
							href={href}
							className="site-footer-social-link custom-cursor-target"
							target="_blank"
							rel="noopener noreferrer"
							aria-label={label}
							title={label}
						>
							<Icon size={20} aria-hidden />
						</a>
					);
				})}
			</nav>
		</div>
	);
}
