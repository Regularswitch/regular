'use client';

import Image from 'next/image';
import { X } from 'lucide-react';
import { useCallback, useEffect, useMemo, useState } from 'react';
import { createPortal } from 'react-dom';

import { getGridSpan } from '../../lib/bentoGrid';
import { wpMediaUrl } from '../../lib/wpMediaUrl';
import { NavChevronLeft, NavChevronRight } from '../SiteIcons';

type ProjectGalleryProps = {
	images: string[];
	title: string;
	locale?: 'en' | 'pt';
};

type SlideDirection = 'open' | 'next' | 'prev';

export default function ProjectGallery({ images, title, locale = 'en' }: ProjectGalleryProps) {
	const resolvedImages = useMemo(
		() => images.map((src) => wpMediaUrl(src) ?? src).filter(Boolean),
		[images],
	);
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
			if (event.animationName !== 'project-gallery-lightbox-out' && !event.animationName.endsWith('project-gallery-lightbox-out')) return;
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
			if (current === null || resolvedImages.length <= 1) return current;
			return (current - 1 + resolvedImages.length) % resolvedImages.length;
		});
	}, [isClosing, resolvedImages.length]);

	const showNext = useCallback(() => {
		if (isClosing) return;
		setSlideDirection('next');
		setLightboxIndex((current) => {
			if (current === null || resolvedImages.length <= 1) return current;
			return (current + 1) % resolvedImages.length;
		});
	}, [isClosing, resolvedImages.length]);

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

	if (!resolvedImages.length) return null;

	const openLabel = locale === 'pt' ? 'Ver imagem ampliada' : 'View full image';
	const closeLabel = locale === 'pt' ? 'Fechar' : 'Close';
	const prevLabel = locale === 'pt' ? 'Imagem anterior' : 'Previous image';
	const nextLabel = locale === 'pt' ? 'Próxima imagem' : 'Next image';
	const lightboxSrc = lightboxIndex !== null ? resolvedImages[lightboxIndex] : null;

	const lightbox = lightboxSrc ? (
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

			{resolvedImages.length > 1 ? (
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
				{/* eslint-disable-next-line @next/next/no-img-element -- lightbox em tamanho real */}
				<img
					key={lightboxIndex}
					src={lightboxSrc}
					alt={`${title} — imagem ${(lightboxIndex ?? 0) + 1}`}
					className={`project-gallery-lightbox-image${
						isClosing
							? ' project-gallery-lightbox-image--close'
							: ` project-gallery-lightbox-image--${slideDirection}`
					}`}
				/>
			</div>
		</div>
	) : null;

	return (
		<>
			<section className="project-gallery" aria-label="Galeria do projeto">
				<div className="selected-projects-grid project-gallery-grid">
					{resolvedImages.map((src, index) => {
						const isFull = getGridSpan(index) === 'full';

						return (
							<div
								key={`${src}-${index}`}
								className={`project-gallery-item${isFull ? ' selected-projects-item--full' : ''}`}
							>
								<button
									type="button"
									className="project-gallery-trigger custom-cursor-target"
									onClick={() => openLightbox(index)}
									aria-label={`${openLabel} ${index + 1}`}
								>
									<div
										className={`project-gallery-image relative overflow-hidden rounded-md bg-(--surface)${isFull ? ' project-gallery-image--full' : ''}`}
									>
										<Image
											src={src}
											alt={`${title} — imagem ${index + 1}`}
											fill
											sizes={
												isFull
													? '(max-width: 768px) 100vw, 100vw'
													: '(max-width: 768px) 100vw, 50vw'
											}
											className="object-cover object-center"
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
