'use client';

import Image from 'next/image';
import type { ReactNode } from 'react';

import { wpMediaUrl } from '../../lib/wpMediaUrl';

type PageHeroMediaProps = {
	image?: string;
	video?: string;
	label?: string;
	className?: string;
	/** Mantém um slot vazio quando não há mídia (ex.: Education). */
	showEmptySlot?: boolean;
	/** Quando false, renderiza só o frame (sem <section>). */
	asSection?: boolean;
	/** Edge-to-edge dentro do main (sem radius / aspect card). */
	fullBleed?: boolean;
};

export default function PageHeroMedia({
	image,
	video,
	label = 'Hero',
	className = '',
	showEmptySlot = false,
	asSection = true,
	fullBleed = false,
}: PageHeroMediaProps) {
	const imageSrc = image ? (wpMediaUrl(image) ?? image) : undefined;
	const videoSrc = video ? (wpMediaUrl(video) ?? video) : undefined;

	const frame = (children: ReactNode) => (
		<div
			className={
				fullBleed
					? 'page-hero-media page-hero-media--bleed relative overflow-hidden bg-(--surface)'
					: 'page-hero-media relative aspect-square overflow-hidden rounded-[5px] bg-(--surface) md:aspect-6/3'
			}
		>
			{children}
		</div>
	);

	const empty = frame(null);

	if (!videoSrc && !imageSrc) {
		if (!showEmptySlot) return null;
		return asSection ? (
			<section className={className} aria-label={label} aria-hidden>
				{empty}
			</section>
		) : (
			<div className={className} aria-hidden>
				{empty}
			</div>
		);
	}

	const media = videoSrc ? (
		<video
			className="absolute inset-0 h-full w-full object-cover object-center"
			src={videoSrc}
			autoPlay
			muted
			loop
			playsInline
			poster={imageSrc}
		/>
	) : (
		<Image
			src={imageSrc!}
			alt=""
			fill
			priority
			sizes={fullBleed ? '100vw' : '(max-width: 768px) 100vw, 90vw'}
			className="object-cover object-center"
		/>
	);

	const content = frame(media);

	return asSection ? (
		<section className={className} aria-label={label}>
			{content}
		</section>
	) : (
		<div className={className}>{content}</div>
	);
}
