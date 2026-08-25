import type { IconType } from 'react-icons';
import {
	FaBehance,
	FaFacebookF,
	FaInstagram,
	FaLinkedinIn,
	FaTiktok,
	FaXTwitter,
	FaYoutube,
} from 'react-icons/fa6';

export type FooterSocialItem = {
	network: string;
	href: string;
	label?: string;
};

const ICONS: Record<string, IconType> = {
	instagram: FaInstagram,
	linkedin: FaLinkedinIn,
	youtube: FaYoutube,
	tiktok: FaTiktok,
	x: FaXTwitter,
	twitter: FaXTwitter,
	facebook: FaFacebookF,
	behance: FaBehance,
};

const LABELS: Record<string, string> = {
	instagram: 'Instagram',
	linkedin: 'LinkedIn',
	youtube: 'YouTube',
	tiktok: 'TikTok',
	x: 'X',
	twitter: 'X',
	facebook: 'Facebook',
	behance: 'Behance',
};

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
			<p className="site-footer-social-title font-hk text-base font-bold text-(--fg) md:text-lg">{title}</p>
			<nav className="site-footer-social-links" aria-label={title}>
				{visible.map((item) => {
					const network = item.network.trim().toLowerCase();
					const Icon = ICONS[network];
					if (!Icon) return null;
					const label = item.label?.trim() || LABELS[network] || network;
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
