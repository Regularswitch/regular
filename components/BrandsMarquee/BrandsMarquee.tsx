'use client';

import { useCallback, useEffect, useRef, useState } from 'react';

import type { Brand } from '../../types';

type BrandsMarqueeProps = {
	title?: string;
	brands: Brand[];
};

const AUTO_PX_PER_SEC = 48;
const HOVER_PX_PER_SEC = 140;

/** Repete marcas até cada metade do track preencher a tela (loop -50% sem vazio). */
function buildMarqueeTrack(brands: Brand[]) {
	const minItemsPerHalf = 24;
	const repeats = Math.max(2, Math.ceil(minItemsPerHalf / brands.length));
	const half = Array.from({ length: repeats }, () => brands).flat();
	return { track: [...half, ...half], halfLength: half.length };
}

function BrandMark({ name, logo }: { name: string; logo?: string }) {
	const [failed, setFailed] = useState(false);

	if (!logo || failed) {
		return (
			<span className="whitespace-nowrap text-lg font-semibold tracking-tight text-(--fg) opacity-90 md:text-2xl">
				{name}
			</span>
		);
	}

	return (
		// eslint-disable-next-line @next/next/no-img-element -- URLs do WP local; evita falha do otimizador
		<img
			src={logo}
			alt={name}
			width={180}
			height={48}
			loading="lazy"
			decoding="async"
			onError={() => setFailed(true)}
			className="brand-mark h-8 w-auto max-w-[140px] object-contain opacity-90 md:h-10 md:max-w-[180px]"
		/>
	);
}

export default function BrandsMarquee({ title = 'Marcas que confiam na gente', brands }: BrandsMarqueeProps) {
	const containerRef = useRef<HTMLDivElement>(null);
	const trackRef = useRef<HTMLDivElement>(null);
	const offsetRef = useRef(0);
	const halfWidthRef = useRef(0);
	const speedRef = useRef(AUTO_PX_PER_SEC);
	const hoverSideRef = useRef<'none' | 'left' | 'right'>('none');
	const rafRef = useRef<number>(0);

	const { track, halfLength } = buildMarqueeTrack(brands);

	const syncSpeed = useCallback(() => {
		if (hoverSideRef.current === 'left') {
			speedRef.current = -HOVER_PX_PER_SEC;
		} else if (hoverSideRef.current === 'right') {
			speedRef.current = HOVER_PX_PER_SEC;
		} else {
			speedRef.current = AUTO_PX_PER_SEC;
		}
	}, []);

	const handlePointerMove = useCallback(
		(event: React.PointerEvent<HTMLDivElement>) => {
			const el = containerRef.current;
			if (!el) return;

			const rect = el.getBoundingClientRect();
			const x = event.clientX - rect.left;
			hoverSideRef.current = x < rect.width / 2 ? 'left' : 'right';
			syncSpeed();
		},
		[syncSpeed],
	);

	const handlePointerEnter = useCallback(
		(event: React.PointerEvent<HTMLDivElement>) => {
			handlePointerMove(event);
		},
		[handlePointerMove],
	);

	const handlePointerLeave = useCallback(() => {
		hoverSideRef.current = 'none';
		syncSpeed();
	}, [syncSpeed]);

	useEffect(() => {
		const trackEl = trackRef.current;
		if (!trackEl) return;

		const measure = () => {
			halfWidthRef.current = trackEl.scrollWidth / 2;
		};

		measure();
		const ro = new ResizeObserver(measure);
		ro.observe(trackEl);

		const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)');

		let last = performance.now();

		const tick = (now: number) => {
			const dt = Math.min(now - last, 32) / 1000;
			last = now;

			if (!reducedMotion.matches) {
				let offset = offsetRef.current + speedRef.current * dt;
				const half = halfWidthRef.current;

				if (half > 0) {
					while (offset >= half) offset -= half;
					while (offset < 0) offset += half;
				}

				offsetRef.current = offset;
				trackEl.style.transform = `translate3d(${-offset}px, 0, 0)`;
			}

			rafRef.current = requestAnimationFrame(tick);
		};

		rafRef.current = requestAnimationFrame(tick);

		return () => {
			cancelAnimationFrame(rafRef.current);
			ro.disconnect();
		};
	}, [track.length]);

	if (!brands.length) return null;

	return (
		<section className="py-12 md:py-20" aria-label={title}>
			<div className="mb-8 flex items-end justify-between px-7 md:mb-12">
				<h2 className="text-base font-medium text-(--fg) md:text-lg">
					{title} <span aria-hidden>↘</span>
				</h2>
			</div>

			<div
				ref={containerRef}
				className="brands-marquee overflow-hidden cursor-ew-resize"
				onPointerEnter={handlePointerEnter}
				onPointerMove={handlePointerMove}
				onPointerLeave={handlePointerLeave}
			>
				<div
					ref={trackRef}
					className="brands-marquee-track flex w-max items-center gap-12 will-change-transform md:gap-20"
				>
					{track.map((brand, index) => (
						<div
							key={`${brand.id}-${index}`}
							className="flex shrink-0 items-center justify-center px-2 md:px-4"
							aria-hidden={index >= halfLength}
						>
							<BrandMark name={brand.name} logo={brand.logo} />
						</div>
					))}
				</div>
			</div>
		</section>
	);
}
