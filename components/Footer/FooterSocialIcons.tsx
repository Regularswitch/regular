import type { ReactNode, SVGProps } from 'react';

type IconProps = SVGProps<SVGSVGElement>;

function SocialSvg({ children, ...props }: IconProps & { children: ReactNode }) {
	return (
		<svg viewBox="0 0 24 24" width="20" height="20" fill="currentColor" aria-hidden {...props}>
			{children}
		</svg>
	);
}

function InstagramIcon(props: IconProps) {
	return (
		<SocialSvg {...props}>
			<path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069ZM12 0C8.741 0 8.333.014 7.053.072 2.695.272.273 2.69.073 7.052.014 8.333 0 8.741 0 12c0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98C8.333 23.986 8.741 24 12 24c3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98C15.668.014 15.259 0 12 0Zm0 5.838a6.162 6.162 0 1 0 0 12.324 6.162 6.162 0 0 0 0-12.324ZM12 16a4 4 0 1 1 0-8 4 4 0 0 1 0 8Zm6.406-11.845a1.44 1.44 0 1 0 0 2.881 1.44 1.44 0 0 0 0-2.881Z" />
		</SocialSvg>
	);
}

function LinkedinIcon(props: IconProps) {
	return (
		<SocialSvg {...props}>
			<path d="M22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003ZM7.12 20.452H3.558V9h3.562v11.452ZM5.337 7.433a2.064 2.064 0 1 1 0-4.128 2.064 2.064 0 0 1 0 4.128ZM20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286Z" />
		</SocialSvg>
	);
}

function YoutubeIcon(props: IconProps) {
	return (
		<SocialSvg {...props}>
			<path
				fillRule="evenodd"
				d="M23.5 6.2a3.02 3.02 0 0 0-2.12-2.14C19.5 3.6 12 3.6 12 3.6s-7.5 0-9.38.46A3.02 3.02 0 0 0 .5 6.2 31.6 31.6 0 0 0 0 12a31.6 31.6 0 0 0 .5 5.8 3.02 3.02 0 0 0 2.12 2.14C4.5 20.4 12 20.4 12 20.4s7.5 0 9.38-.46a3.02 3.02 0 0 0 2.12-2.14A31.6 31.6 0 0 0 24 12a31.6 31.6 0 0 0-.5-5.8ZM9.6 15.6V8.4L15.8 12 9.6 15.6Z"
			/>
		</SocialSvg>
	);
}

function TiktokIcon(props: IconProps) {
	return (
		<SocialSvg {...props}>
			<path d="M21 8.1a7.4 7.4 0 0 1-4.3-1.4v8.2A6.9 6.9 0 1 1 8.4 8.1v3.1a3.8 3.8 0 1 0 2.7 3.6V2.2h3a7.4 7.4 0 0 0 6.9 5.9V8.1Z" />
		</SocialSvg>
	);
}

function XIcon(props: IconProps) {
	return (
		<SocialSvg {...props}>
			<path
				fillRule="evenodd"
				d="M18.9 2H22l-6.8 7.8L23 22h-6.4l-5-6.6L6.2 22H3.1l7.3-8.3L1.3 2h6.6l4.5 6L18.9 2Zm-1.1 18h1.8L6.4 3.9H4.5L17.8 20Z"
			/>
		</SocialSvg>
	);
}

function FacebookIcon(props: IconProps) {
	return (
		<SocialSvg {...props}>
			<path d="M24 12.07C24 5.41 18.63 0 12 0S0 5.41 0 12.07C0 18.1 4.39 23.09 10.13 24v-8.44H7.08v-3.49h3.05V9.41c0-3.02 1.79-4.7 4.53-4.7 1.31 0 2.69.24 2.69.24v2.97h-1.51c-1.5 0-1.96.93-1.96 1.89v2.27h3.34l-.53 3.49h-2.81V24C19.61 23.09 24 18.1 24 12.07Z" />
		</SocialSvg>
	);
}

function BehanceIcon(props: IconProps) {
	return (
		<SocialSvg {...props}>
			<path d="M8.4 10.4c.9 0 1.6-.2 2.1-.7.5-.5.7-1.1.7-1.9 0-.9-.3-1.6-.8-2.1-.6-.5-1.4-.8-2.4-.8H3.2v11.3h4.8c1.1 0 2-.3 2.7-.9.7-.6 1.1-1.4 1.1-2.5 0-1.5-.8-2.4-2.4-2.4Zm-2.7-3h1.8c1.1 0 1.7.4 1.7 1.3 0 .9-.6 1.4-1.7 1.4H5.7V7.4Zm2 8.2H5.7v-3h2.1c1.2 0 1.8.5 1.8 1.5 0 1-.6 1.5-1.9 1.5ZM21.2 7.2h-5.6V5.8h5.6v1.4Zm-2.7 1.6c-2.5 0-4.2 1.7-4.2 4.2 0 2.5 1.7 4.3 4.3 4.3 1.8 0 3.2-.8 3.8-2.2l-1.8-.7c-.4.8-1.1 1.2-2 1.2-1.2 0-2.1-.7-2.3-1.9h6.4c0-.2.1-.5.1-.8 0-2.5-1.5-4.1-4.3-4.1Zm-2.2 3.3c.3-1.1 1.1-1.7 2.2-1.7 1.1 0 1.9.6 2.1 1.7h-4.3Z" />
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
							<Icon />
						</a>
					);
				})}
			</nav>
		</div>
	);
}
