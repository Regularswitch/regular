'use client';

import Image from 'next/image';
import { useCallback, useEffect, useState } from 'react';

import { wpMediaUrl } from '../../lib/wp/mediaUrl';
import { NavChevronLeft, NavChevronRight } from '../SiteIcons';

type MediaCarouselProps = {
	images: string[];
	locale?: 'en' | 'pt';
	autoplayMs?: number;
	className?: string;
	ariaLabel?: string;
};

export default function MediaCarousel({
	images,
	locale = 'en',
	autoplayMs = 0,
	className = '',
	ariaLabel,
}: MediaCarouselProps) {
	const slides = images.map((src) => wpMediaUrl(src) ?? src).filter(Boolean);
	const [index, setIndex] = useState(0);

	const go = useCallback(
		(direction: -1 | 1) => {
			if (slides.length < 2) return;
			setIndex((current) => (current + direction + slides.length) % slides.length);
		},
		[slides.length],
	);

	useEffect(() => {
		if (!autoplayMs || slides.length < 2) return;

		const timer = window.setInterval(() => go(1), autoplayMs);
		return () => window.clearInterval(timer);
	}, [autoplayMs, go, slides.length]);

	if (!slides.length) {
		return (
			<div
				className={`media-carousel media-carousel--empty relative aspect-[4/3] overflow-hidden rounded-[5px] bg-(--surface) ${className}`.trim()}
				aria-hidden
			/>
		);
	}

	const label = ariaLabel ?? (locale === 'pt' ? 'Galeria' : 'Gallery');
	const prevLabel = locale === 'pt' ? 'Anterior' : 'Previous';
	const nextLabel = locale === 'pt' ? 'Próxima' : 'Next';
	const current = slides[index] ?? slides[0];

	return (
		<div className={`media-carousel relative ${className}`.trim()} aria-label={label}>
			<div className="media-carousel-frame relative aspect-[4/3] overflow-hidden rounded-[5px] bg-(--surface)">
				<Image
					key={current}
					src={current}
					alt=""
					fill
					sizes="(max-width: 768px) 100vw, 40vw"
					className="object-cover object-center"
				/>
			</div>

			{slides.length > 1 ? (
				<div className="mt-3 flex items-center justify-between gap-3">
					<div className="flex items-center gap-2">
						<button
							type="button"
							onClick={() => go(-1)}
							className="latest-projects-nav-btn"
							aria-label={prevLabel}
						>
							<NavChevronLeft />
						</button>
						<button
							type="button"
							onClick={() => go(1)}
							className="latest-projects-nav-btn"
							aria-label={nextLabel}
						>
							<NavChevronRight />
						</button>
					</div>
					<p className="font-hk text-xs text-(--muted)">
						{index + 1} / {slides.length}
					</p>
				</div>
			) : null}
		</div>
	);
}
