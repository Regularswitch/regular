'use client';

import Image from 'next/image';

import { isProjectMediaVideo, resolveProjectMediaType } from '../../lib/projects/gallery';
import { isGifUrl } from '../../lib/projects/images';
import { wpMediaUrl } from '../../lib/wp/mediaUrl';
import type { ProjectStructuredImage } from '../../types';

type ProjectHeroProps = {
	image?: string;
	/** Hero estruturado (com type/mime) — preferido quando disponível. */
	media?: ProjectStructuredImage | null;
	logo?: string;
	title: string;
	showVignette?: boolean;
};

export default function ProjectHero({
	image,
	media,
	logo,
	title,
	showVignette = true,
}: ProjectHeroProps) {
	const mediaUrl =
		(media?.url && typeof media.url === 'string' ? (wpMediaUrl(media.url) ?? media.url) : undefined) ||
		(image ? (wpMediaUrl(image) ?? image) : undefined);

	if (!mediaUrl) return null;

	const mediaType = media
		? resolveProjectMediaType(mediaUrl, media.mime, media.type)
		: resolveProjectMediaType(mediaUrl);
	const isVideo = mediaType === 'video' || isProjectMediaVideo({ url: mediaUrl, type: mediaType });
	const isGif = mediaType === 'gif';
	const logoSrc = logo ? (wpMediaUrl(logo) ?? logo) : undefined;

	return (
		<section className="project-hero" aria-label={title}>
			<div className="project-hero-image relative aspect-square overflow-hidden rounded-[5px] bg-(--surface) md:aspect-video">
				{isVideo ? (
					<video
						className="absolute inset-0 h-full w-full object-cover object-center"
						src={mediaUrl}
						autoPlay
						muted
						loop
						playsInline
					/>
				) : (
					<Image
						src={mediaUrl}
						alt={title}
						fill
						priority
						unoptimized={isGif}
						sizes="(max-width: 768px) 100vw, 90vw"
						className="object-cover object-center"
					/>
				)}

				<div className="absolute inset-0 bg-black/20" />

				{showVignette ? (
					logoSrc ? (
						<div className="absolute bottom-6 left-6 z-10 hidden md:block md:bottom-8 md:left-8">
							<Image
								src={logoSrc}
								alt={`${title} logo`}
								width={280}
								height={120}
								className="h-auto w-[min(55vw,280px)] object-contain"
								unoptimized={logoSrc.includes('.svg') || isGifUrl(logoSrc)}
							/>
						</div>
					) : (
						<div className="absolute bottom-6 left-6 z-10 max-w-xl md:bottom-8 md:left-8">
							<p className="font-hk text-2xl font-medium uppercase tracking-tight text-white md:text-4xl">
								{title}
							</p>
						</div>
					)
				) : null}
			</div>
		</section>
	);
}
