'use client';

import { useEffect, useMemo, useRef, useState, type MutableRefObject } from 'react';

type FontVarianteProps = {
	text: string;
	className?: string;
	charClassName?: string;
	align?: 'left' | 'center' | 'justify';
	/** Mobile: duas linhas compactas (ex. REGULAR / SWITCH). Desktop: uma linha com justify. */
	splitOnMobile?: boolean;
};

const DEFAULT_VARIATION = "'wght' 100, 'wdth' 5, 'ital' 0";
const DEFAULT_CHAR_CLASS = 'text-[clamp(3rem,18vw,22rem)] leading-[0.82]';
const MOBILE_LINE_CLASS = 'text-[clamp(3.75rem,19vw,7.5rem)] leading-[0.78]';

function splitLines(text: string): string[] {
	const trimmed = text.trim();
	if (!trimmed) return [];

	const spaceIdx = trimmed.indexOf(' ');
	if (spaceIdx !== -1) {
		return [trimmed.slice(0, spaceIdx), trimmed.slice(spaceIdx + 1).trim()].filter(Boolean);
	}

	const switchIdx = trimmed.toUpperCase().indexOf('SWITCH');
	if (switchIdx > 0) {
		return [trimmed.slice(0, switchIdx), trimmed.slice(switchIdx)];
	}

	if (trimmed.length < 2) return [trimmed];

	const splitAt = Math.ceil(trimmed.length / 2);
	return [trimmed.slice(0, splitAt), trimmed.slice(splitAt)];
}

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

function CharSpan({
	char,
	index,
	className,
	spanRefs,
}: {
	char: string;
	index: number;
	className: string;
	spanRefs: MutableRefObject<(HTMLSpanElement | null)[]>;
}) {
	return (
		<span
			ref={(el) => {
				spanRefs.current[index] = el;
			}}
			data-char={char}
			style={{ textRendering: 'optimizeSpeed', fontVariationSettings: DEFAULT_VARIATION }}
			className={`shrink-0 ${className}${char === ' ' ? ' min-w-[0.2em]' : ''}`}
		>
			{char === ' ' ? '\u00A0' : char}
		</span>
	);
}

export default function FontVariante({
	text,
	className = '',
	charClassName = DEFAULT_CHAR_CLASS,
	align = 'justify',
	splitOnMobile = false,
}: FontVarianteProps) {
	const [cursor, setCursor] = useState({ x: 0, y: 0 });
	const containerRef = useRef<HTMLDivElement>(null);
	const mobileSpanRefs = useRef<(HTMLSpanElement | null)[]>([]);
	const desktopSpanRefs = useRef<(HTMLSpanElement | null)[]>([]);

	const desktopChars = useMemo(() => text.split(''), [text]);
	const mobileLines = useMemo(() => (splitOnMobile ? splitLines(text) : []), [splitOnMobile, text]);
	const useMobileStack = mobileLines.length > 1;

	useEffect(() => {
		const container = containerRef.current;
		if (!container) return;

		function handlePointerMove(e: MouseEvent | TouchEvent) {
			if ('touches' in e && e.touches.length === 0) return;
			const point = 'touches' in e ? e.touches[0] : e;
			setCursor({ x: point.clientX, y: point.clientY });
		}

		container.addEventListener('mousemove', handlePointerMove);
		container.addEventListener('touchstart', handlePointerMove, { passive: true });
		container.addEventListener('touchmove', handlePointerMove, { passive: true });

		return () => {
			container.removeEventListener('mousemove', handlePointerMove);
			container.removeEventListener('touchstart', handlePointerMove);
			container.removeEventListener('touchmove', handlePointerMove);
		};
	}, []);

	useEffect(() => {
		const isMobile = typeof window !== 'undefined' && window.matchMedia('(max-width: 767px)').matches;
		const maxWdth = isMobile ? 120 : 200;
		const activeRefs = isMobile && useMobileStack ? mobileSpanRefs : desktopSpanRefs;

		activeRefs.current.forEach((span) => {
			if (!span) return;

			const pos = span.getBoundingClientRect();
			const dist = getDist(cursor, {
				x: pos.x + pos.width / 1.75,
				y: pos.y,
			});
			const wdth = ~~getAttr(dist, 5, maxWdth);
			const wght = ~~getAttr(dist, 100, 800);
			const ital = getAttr(dist, 0, 1).toFixed(2);

			span.style.fontVariationSettings = `'wght' ${wght}, 'wdth' ${wdth}, 'ital' ${ital}`;
		});
	}, [cursor, desktopChars.length, mobileLines, useMobileStack]);

	const desktopAlignClass =
		align === 'center'
			? 'justify-center text-center'
			: align === 'justify'
				? 'w-full justify-between'
				: 'justify-start text-left';

	return (
		<div ref={containerRef} className={`w-full ${className}`}>
			{useMobileStack ? (
				<div className="flex flex-col items-center md:hidden">
					{mobileLines.map((line, lineIndex) => (
						<div
							key={`${line}-${lineIndex}`}
							className={`font-home flex w-full flex-nowrap justify-center uppercase text-(--fg) ${MOBILE_LINE_CLASS}`}
						>
							{line.split('').map((char, charIndex) => {
								const index =
									mobileLines.slice(0, lineIndex).reduce((sum, prev) => sum + prev.length, 0) + charIndex;

								return (
									<CharSpan
										key={`${line}-${charIndex}`}
										char={char}
										index={index}
										className="text-[1em]"
										spanRefs={mobileSpanRefs}
									/>
								);
							})}
						</div>
					))}
				</div>
			) : null}

			<div
				className={`font-home flex w-full flex-nowrap uppercase text-(--fg) ${desktopAlignClass}${
					useMobileStack ? ' hidden md:flex' : ''
				}`}
			>
				{desktopChars.map((char, index) => (
					<CharSpan
						key={`desktop-${index}`}
						char={char}
						index={index}
						className={charClassName}
						spanRefs={desktopSpanRefs}
					/>
				))}
			</div>
		</div>
	);
}
