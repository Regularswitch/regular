'use client';

import { useEffect, useMemo, useRef, useState } from 'react';

export type NormalizedPointer = {
	x: number; // -1..1
	y: number; // -1..1 (positive up, matches original)
	active: boolean;
};

type Options = {
	containerRef: React.RefObject<HTMLElement | null>;
};

function randomBetween(min: number, max: number) {
	return min + Math.random() * (max - min);
}

export default function usePointerNormalized({ containerRef }: Options) {
	const [pointer, setPointer] = useState<NormalizedPointer>({ x: 0, y: 0, active: false });
	const timeoutRef = useRef<number | null>(null);

	const noHover = useMemo(() => {
		if (typeof window === 'undefined') return false;
		return window.matchMedia('(hover: none)').matches;
	}, []);

	useEffect(() => {
		const el = containerRef.current;
		if (!el) return;

		if (noHover) {
			const tick = () => {
				setPointer({
					x: randomBetween(-0.8, 0.8),
					y: randomBetween(-0.8, 0.8),
					active: true,
				});
				timeoutRef.current = window.setTimeout(tick, Math.round(randomBetween(3000, 5000)));
			};

			tick();
			return () => {
				if (timeoutRef.current) window.clearTimeout(timeoutRef.current);
				timeoutRef.current = null;
			};
		}

		const onMove = (e: PointerEvent) => {
			const rect = el.getBoundingClientRect();
			const x = (e.clientX - rect.left) / rect.width;
			const y = (e.clientY - rect.top) / rect.height;
			const inside = x >= 0 && x <= 1 && y >= 0 && y <= 1;
			setPointer({
				x: (x - 0.5) * 2,
				y: (y - 0.5) * -2,
				active: inside,
			});
		};

		const onLeave = () => setPointer((p) => ({ ...p, active: false }));

		el.addEventListener('pointermove', onMove, { passive: true });
		el.addEventListener('pointerleave', onLeave, { passive: true });
		return () => {
			el.removeEventListener('pointermove', onMove);
			el.removeEventListener('pointerleave', onLeave);
		};
	}, [containerRef, noHover]);

	return pointer;
}

