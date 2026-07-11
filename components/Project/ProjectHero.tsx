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
		<section className="project-hero" aria-label={title}>
			<div className="project-hero-image relative aspect-square overflow-hidden rounded-xl bg-(--surface) md:aspect-video">
				<Image
					src={heroSrc}
					alt={title}
					fill
					priority
					sizes="(max-width: 768px) 100vw, 90vw"
					className="object-cover object-center"
				/>

				<div className="absolute inset-0 bg-black/20" />

				{logoSrc ? (
					<div className="absolute bottom-6 left-6 z-10 hidden md:block md:bottom-8 md:left-8">
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
					<div className="absolute bottom-6 left-6 z-10 max-w-xl md:bottom-8 md:left-8">
						<p className="font-hk text-2xl font-medium uppercase tracking-tight text-white md:text-4xl">
							{title}
						</p>
					</div>
				)}
			</div>
		</section>
	);
}
