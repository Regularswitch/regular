import type { ReactNode, SVGProps } from 'react';

type IconProps = SVGProps<SVGSVGElement>;

function SocialSvg({ children, ...props }: IconProps & { children: ReactNode }) {
	return (
		<svg viewBox="0 0 24 24" width="20" height="20" fill="none" aria-hidden {...props}>
			{children}
		</svg>
	);
}

function InstagramIcon(props: IconProps) {
	return (
		<SocialSvg {...props}>
			<rect x="3" y="3" width="18" height="18" rx="5" stroke="currentColor" strokeWidth="1.6" />
			<circle cx="12" cy="12" r="4" stroke="currentColor" strokeWidth="1.6" />
			<circle cx="17.4" cy="6.6" r="1" fill="currentColor" />
		</SocialSvg>
	);
}

function LinkedinIcon(props: IconProps) {
	return (
		<SocialSvg {...props}>
			<rect x="3" y="3" width="18" height="18" rx="3" stroke="currentColor" strokeWidth="1.6" />
			<path
				d="M8 10.2V16.5M8 7.5v.03M12.2 16.5v-3.6c0-.9.7-1.6 1.6-1.6s1.6.7 1.6 1.6v3.6"
				stroke="currentColor"
				strokeWidth="1.6"
				strokeLinecap="round"
			/>
		</SocialSvg>
	);
}

function YoutubeIcon(props: IconProps) {
	return (
		<SocialSvg {...props}>
			<rect x="2.5" y="6" width="19" height="12" rx="3.2" stroke="currentColor" strokeWidth="1.6" />
			<path d="M10.2 9.4v5.2L15.4 12 10.2 9.4Z" fill="currentColor" />
		</SocialSvg>
	);
}

function TiktokIcon(props: IconProps) {
	return (
		<SocialSvg {...props}>
			<path
				d="M14.2 4v10.1a3.7 3.7 0 1 1-3.2-3.66V13a1.3 1.3 0 1 0 1.3 1.3V4h1.9c.4 2.3 1.9 3.7 4 4.1V9.8c-1.5-.1-2.8-.7-4-1.7"
				stroke="currentColor"
				strokeWidth="1.6"
				strokeLinejoin="round"
			/>
		</SocialSvg>
	);
}

function XIcon(props: IconProps) {
	return (
		<SocialSvg {...props}>
			<path
				d="M5 5 11.2 12.4 5.4 19h2.4l4.6-5.3L16.4 19H19l-6.5-7.6L18.4 5h-2.4l-4.3 5L7.6 5H5Z"
				fill="currentColor"
			/>
		</SocialSvg>
	);
}

function FacebookIcon(props: IconProps) {
	return (
		<SocialSvg {...props}>
			<path
				d="M14 8.5h2.2V5.8H14c-2.4 0-4 1.5-4 4.1v1.6H7.8v2.7H10V19h3v-4.8h2.2l.5-2.7H13V9.9c0-.8.3-1.4 1-1.4Z"
				fill="currentColor"
			/>
		</SocialSvg>
	);
}

function BehanceIcon(props: IconProps) {
	return (
		<SocialSvg {...props}>
			<path
				d="M6.2 8.2h3.1c1.3 0 2.2.6 2.2 1.8 0 .8-.4 1.4-1.1 1.6 1 .2 1.6 1 1.6 1.9 0 1.4-1.1 2.1-2.7 2.1H6.2V8.2Zm1.8 2.8h1.2c.5 0 .9-.3.9-.7s-.3-.7-.9-.7H8v1.4Zm0 2.9h1.4c.6 0 1-.3 1-.8s-.4-.8-1.1-.8H8v1.6ZM14.2 9.1h4.2M14.4 14.8c.4.7 1.2 1.1 2.2 1.1 1.8 0 3-1.2 3-3.1 0-1.9-1.2-3.1-3-3.1-1.8 0-3 1.3-3 3.1 0 .4.1.8.2 1.1h4.4c-.2 1-.9 1.5-1.8 1.5-.7 0-1.2-.2-1.6-.6"
				stroke="currentColor"
				strokeWidth="1.5"
				strokeLinejoin="round"
			/>
		</SocialSvg>
	);
}

const ICONS: Record<string, (props: IconProps) => ReactNode> = {
	instagram: InstagramIcon,
	linkedin: LinkedinIcon,
	youtube: YoutubeIcon,
	tiktok: TiktokIcon,
	x: XIcon,
	twitter: XIcon,
	facebook: FacebookIcon,
	behance: BehanceIcon,
};

export type FooterSocialItem = {
	network: string;
	href: string;
	label?: string;
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

export default function FooterSocialIcons({ links }: { links: FooterSocialItem[] }) {
	const visible = links.filter((item) => item.href?.trim());
	if (!visible.length) return null;

	return (
		<nav className="site-footer-social" aria-label="Social">
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
						<Icon />
					</a>
				);
			})}
		</nav>
	);
}
