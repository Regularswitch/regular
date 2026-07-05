'use client';

import { useEffect, useMemo, useRef, useState } from 'react';

type FontVarianteProps = {
	text: string;
	className?: string;
	charClassName?: string;
	align?: 'left' | 'center' | 'justify';
};

const DEFAULT_VARIATION = "'wght' 100, 'wdth' 5, 'ital' 0";

function getDist(mouse: { x: number; y: number }, pos: { x: number; y: number }) {
	const dx = pos.x - mouse.x;
	const dy = pos.y - mouse.y;
	return Math.sqrt(dx * dx + dy * dy);
}

function getAttr(dist: number, min: number, max: number) {
	const maxDist = 500;
	const value = max - Math.abs((max * dist) / maxDist);
	return Math.max(min, value + min);
}

export default function FontVariante({
	text,
	className = '',
	charClassName = 'text-[clamp(3rem,18vw,22rem)] leading-[0.82]',
	align = 'justify',
}: FontVarianteProps) {
	const [cursor, setCursor] = useState({ x: 0, y: 0 });
	const containerRef = useRef<HTMLDivElement>(null);
	const spanRefs = useRef<(HTMLSpanElement | null)[]>([]);

	const chars = useMemo(() => text.split(''), [text]);

	useEffect(() => {
		const container = containerRef.current;
		if (!container) return;

		function handlePointerMove(e: MouseEvent | TouchEvent) {
			const point = 'touches' in e ? e.touches[0] : e;
			setCursor({ x: point.clientX, y: point.clientY });
		}

		container.addEventListener('mousemove', handlePointerMove);
		container.addEventListener('touchmove', handlePointerMove, { passive: true });

		return () => {
			container.removeEventListener('mousemove', handlePointerMove);
			container.removeEventListener('touchmove', handlePointerMove);
		};
	}, []);

	useEffect(() => {
		spanRefs.current.forEach((span) => {
			if (!span) return;

			const pos = span.getBoundingClientRect();
			const dist = getDist(cursor, {
				x: pos.x + pos.width / 1.75,
				y: pos.y,
			});
			const wdth = ~~getAttr(dist, 5, 200);
			const wght = ~~getAttr(dist, 100, 800);
			const ital = getAttr(dist, 0, 1).toFixed(2);

			span.style.fontVariationSettings = `'wght' ${wght}, 'wdth' ${wdth}, 'ital' ${ital}`;
		});
	}, [cursor, chars.length]);

	const alignClass =
		align === 'center'
			? 'justify-center text-center'
			: align === 'justify'
				? 'w-full justify-between'
				: 'justify-start text-left';

	return (
		<div ref={containerRef} className={`w-full ${className}`}>
			<div className={`font-home flex w-full flex-nowrap uppercase text-(--fg) ${alignClass}`}>
				{chars.map((char, index) => (
					<span
						key={`${char}-${index}`}
						ref={(el) => {
							spanRefs.current[index] = el;
						}}
						data-char={char}
						style={{ textRendering: 'optimizeSpeed', fontVariationSettings: DEFAULT_VARIATION }}
						className={`pointer-events-none shrink-0 ${charClassName}${char === ' ' ? ' min-w-[0.2em]' : ''}`}
					>
						{char === ' ' ? '\u00A0' : char}
					</span>
				))}
			</div>
		</div>
	);
}
