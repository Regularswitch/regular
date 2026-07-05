'use client';

import Image from 'next/image';

import { wpMediaUrl } from '../../lib/wpMediaUrl';

type ProjectHeroProps = {
	image?: string;
	logo?: string;
	title: string;
};

export default function ProjectHero({ image, logo, title }: ProjectHeroProps) {
	if (!image) return null;

	const heroSrc = wpMediaUrl(image) ?? image;
	const logoSrc = logo ? (wpMediaUrl(logo) ?? logo) : undefined;

	return (
		<section className="project-hero relative -mx-7 min-h-[70svh] overflow-hidden md:min-h-[85svh]">
			<Image
				src={heroSrc}
				alt={title}
				fill
				priority
				sizes="100vw"
				className="object-cover object-center"
			/>
			<div className="absolute inset-0 bg-black/20" />

			{logoSrc ? (
				<div className="absolute bottom-8 left-7 z-10 md:bottom-12 md:left-10">
					<Image
						src={logoSrc}
						alt={`${title} logo`}
						width={280}
						height={120}
						className="h-auto w-[min(55vw,280px)] object-contain"
						unoptimized={logoSrc.includes('.svg')}
					/>
				</div>
			) : (
				<div className="absolute bottom-8 left-7 z-10 max-w-xl md:bottom-12 md:left-10">
					<p className="font-hk text-3xl font-extrabold uppercase tracking-tight text-white md:text-5xl">{title}</p>
				</div>
			)}
		</section>
	);
}
