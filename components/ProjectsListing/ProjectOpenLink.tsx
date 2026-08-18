'use client';

import Link from 'next/link';
import { useRouter } from 'next/navigation';
import { type ComponentProps, type MouseEvent, useState } from 'react';

const PULSE_MS = 340;

type ProjectOpenLinkProps = Omit<ComponentProps<typeof Link>, 'href' | 'onClick'> & {
	href: string;
};

export default function ProjectOpenLink({ href, className, children, ...rest }: ProjectOpenLinkProps) {
	const router = useRouter();
	const [opening, setOpening] = useState(false);

	function handleClick(event: MouseEvent<HTMLAnchorElement>) {
		if (
			event.defaultPrevented ||
			event.button !== 0 ||
			event.metaKey ||
			event.ctrlKey ||
			event.shiftKey ||
			event.altKey
		) {
			return;
		}

		if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
			return;
		}

		event.preventDefault();
		if (opening) return;

		setOpening(true);
		window.setTimeout(() => {
			router.push(href);
		}, PULSE_MS);
	}

	return (
		<Link
			href={href}
			onClick={handleClick}
			className={[className, opening ? 'is-opening' : ''].filter(Boolean).join(' ')}
			{...rest}
		>
			{children}
		</Link>
	);
}
