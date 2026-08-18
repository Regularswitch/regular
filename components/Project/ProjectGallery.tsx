'use client';

import Image from 'next/image';
import { X } from 'lucide-react';
import { useCallback, useEffect, useMemo, useState } from 'react';
import { createPortal } from 'react-dom';

import { isGalleryWide, normalizeGalleryItems } from '../../lib/projects/gallery';
import type { ProjectGalleryImage } from '../../types';
import { NavChevronLeft, NavChevronRight } from '../SiteIcons';

type ProjectGalleryProps = {
	images: Array<string | ProjectGalleryImage>;
	title: string;
	locale?: 'en' | 'pt';
};

type SlideDirection = 'open' | 'next' | 'prev';

function GalleryMedia({
	item,
	alt,
	wide,
}: {
	item: ProjectGalleryImage;
	alt: string;
	wide: boolean;
}) {
	const width = item.width && item.width > 0 ? item.width : 1600;
	const height = item.height && item.height > 0 ? item.height : 900;
	const sizes = wide
		? '(max-width: 768px) 100vw, 100vw'
		: '(max-width: 768px) 100vw, 50vw';

	if (item.type === 'video') {
		return (
			<video
				className="project-gallery-img h-auto w-full"
				src={item.url}
				width={width}
				height={height}
				autoPlay
				muted
				loop
				playsInline
				preload="metadata"
			/>
		);
	}

	if (item.type === 'gif') {
		return (
			<Image
				src={item.url}
				alt={alt}
				width={width}
				height={height}
				sizes={sizes}
				unoptimized
				className="project-gallery-img h-auto w-full"
			/>
		);
	}

	return (
		<Image
			src={item.url}
			alt={alt}
			width={width}
			height={height}
			sizes={sizes}
			className="project-gallery-img h-auto w-full"
		/>
	);
}

export default function ProjectGallery({ images, title, locale = 'en' }: ProjectGalleryProps) {
	const items = useMemo(() => normalizeGalleryItems(images), [images]);
	const [lightboxIndex, setLightboxIndex] = useState<number | null>(null);
	const [slideDirection, setSlideDirection] = useState<SlideDirection>('open');
	const [isClosing, setIsClosing] = useState(false);
	const [isMounted, setIsMounted] = useState(false);

	useEffect(() => {
		setIsMounted(true);
	}, []);

	const finishClose = useCallback(() => {
		setLightboxIndex(null);
		setIsClosing(false);
		setSlideDirection('open');
	}, []);

	const closeLightbox = useCallback(() => {
		if (lightboxIndex === null || isClosing) return;

		if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
			finishClose();
			return;
		}

		setIsClosing(true);
	}, [finishClose, isClosing, lightboxIndex]);

	const handleBackdropAnimationEnd = useCallback(
		(event: React.AnimationEvent<HTMLDivElement>) => {
			if (!isClosing || event.target !== event.currentTarget) return;
			if (
				event.animationName !== 'project-gallery-lightbox-out' &&
				!event.animationName.endsWith('project-gallery-lightbox-out')
			) {
				return;
			}
			finishClose();
		},
		[finishClose, isClosing],
	);

	const openLightbox = useCallback((index: number) => {
		setSlideDirection('open');
		setIsClosing(false);
		setLightboxIndex(index);
	}, []);

	const showPrevious = useCallback(() => {
		if (isClosing) return;
		setSlideDirection('prev');
		setLightboxIndex((current) => {
			if (current === null || items.length <= 1) return current;
			return (current - 1 + items.length) % items.length;
		});
	}, [isClosing, items.length]);

	const showNext = useCallback(() => {
		if (isClosing) return;
		setSlideDirection('next');
		setLightboxIndex((current) => {
			if (current === null || items.length <= 1) return current;
			return (current + 1) % items.length;
		});
	}, [isClosing, items.length]);

	useEffect(() => {
		if (lightboxIndex === null) return;

		const onKeyDown = (event: KeyboardEvent) => {
			if (isClosing) return;
			if (event.key === 'Escape') closeLightbox();
			if (event.key === 'ArrowLeft') showPrevious();
			if (event.key === 'ArrowRight') showNext();
		};

		const previousOverflow = document.body.style.overflow;
		document.body.style.overflow = 'hidden';

		window.addEventListener('keydown', onKeyDown);

		return () => {
			document.body.style.overflow = previousOverflow;
			window.removeEventListener('keydown', onKeyDown);
		};
	}, [closeLightbox, isClosing, lightboxIndex, showNext, showPrevious]);

	if (!items.length) return null;

	const openLabel = locale === 'pt' ? 'Ver mídia ampliada' : 'View full media';
	const closeLabel = locale === 'pt' ? 'Fechar' : 'Close';
	const prevLabel = locale === 'pt' ? 'Mídia anterior' : 'Previous media';
	const nextLabel = locale === 'pt' ? 'Próxima mídia' : 'Next media';
	const lightboxItem = lightboxIndex !== null ? items[lightboxIndex] : null;

	const lightbox = lightboxItem ? (
		<div
			className={`project-gallery-lightbox${isClosing ? ' is-closing' : ' is-open'}`}
			role="dialog"
			aria-modal="true"
			aria-label={openLabel}
			onClick={closeLightbox}
		>
			<div
				className="project-gallery-lightbox-backdrop absolute inset-0 h-full w-full bg-black/50 backdrop-blur-2xl"
				aria-hidden
				onAnimationEnd={handleBackdropAnimationEnd}
			/>
			<button
				type="button"
				className="project-gallery-lightbox-close project-gallery-lightbox-control custom-cursor-target"
				onClick={closeLightbox}
				aria-label={closeLabel}
			>
				<X size={22} strokeWidth={1.75} aria-hidden />
			</button>

			{items.length > 1 ? (
				<>
					<button
						type="button"
						className="project-gallery-lightbox-nav project-gallery-lightbox-nav--prev project-gallery-lightbox-control custom-cursor-target"
						onClick={(event) => {
							event.stopPropagation();
							showPrevious();
						}}
						aria-label={prevLabel}
					>
						<NavChevronLeft />
					</button>
					<button
						type="button"
						className="project-gallery-lightbox-nav project-gallery-lightbox-nav--next project-gallery-lightbox-control custom-cursor-target"
						onClick={(event) => {
							event.stopPropagation();
							showNext();
						}}
						aria-label={nextLabel}
					>
						<NavChevronRight />
					</button>
				</>
			) : null}

			<div
				className="project-gallery-lightbox-stage"
				onClick={(event) => event.stopPropagation()}
			>
				{lightboxItem.type === 'video' ? (
					<video
						key={lightboxIndex}
						src={lightboxItem.url}
						className={`project-gallery-lightbox-image${
							isClosing
								? ' project-gallery-lightbox-image--close'
								: ` project-gallery-lightbox-image--${slideDirection}`
						}`}
						controls
						autoPlay
						muted
						loop
						playsInline
					/>
				) : (
					/* eslint-disable-next-line @next/next/no-img-element -- lightbox em tamanho real / GIF animado */
					<img
						key={lightboxIndex}
						src={lightboxItem.url}
						alt={`${title} — mídia ${(lightboxIndex ?? 0) + 1}`}
						className={`project-gallery-lightbox-image${
							isClosing
								? ' project-gallery-lightbox-image--close'
								: ` project-gallery-lightbox-image--${slideDirection}`
						}`}
					/>
				)}
			</div>
		</div>
	) : null;

	return (
		<>
			<section className="project-gallery" aria-label="Galeria do projeto">
				<div className="project-gallery-grid">
					{items.map((item, index) => {
						const wide = isGalleryWide(item);

						return (
							<div
								key={`${item.url}-${index}`}
								className={`project-gallery-item${wide ? ' project-gallery-item--wide' : ''}`}
							>
								<button
									type="button"
									className="project-gallery-trigger custom-cursor-target"
									onClick={() => openLightbox(index)}
									aria-label={`${openLabel} ${index + 1}`}
								>
									<div className="project-gallery-image overflow-hidden rounded-[5px] bg-(--surface)">
										<GalleryMedia
											item={item}
											alt={`${title} — mídia ${index + 1}`}
											wide={wide}
										/>
									</div>
								</button>
							</div>
						);
					})}
				</div>
			</section>

			{isMounted && lightbox ? createPortal(lightbox, document.body) : null}
		</>
	);
}
