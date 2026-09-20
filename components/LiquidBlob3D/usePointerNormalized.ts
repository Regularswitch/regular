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
	const rafRef = useRef<number | null>(null);
	const currentRef = useRef({ x: 0, y: 0 });
	const targetRef = useRef({ x: 0, y: 0 });

	const noHover = useMemo(() => {
		if (typeof window === 'undefined') return false;
		return window.matchMedia('(hover: none)').matches;
	}, []);

	useEffect(() => {
		const el = containerRef.current;
		if (!el) return;

		if (noHover) {
			const lerp = (a: number, b: number, t: number) => a + (b - a) * t;

			const tick = () => {
				targetRef.current.x = randomBetween(-0.8, 0.8);
				targetRef.current.y = randomBetween(-0.8, 0.8);
				timeoutRef.current = window.setTimeout(tick, Math.round(randomBetween(3000, 5000)));
			};

			const loop = () => {
				// Continuous motion towards the target (organic, non-linear enough with periodic retargeting)
				currentRef.current.x = lerp(currentRef.current.x, targetRef.current.x, 0.02);
				currentRef.current.y = lerp(currentRef.current.y, targetRef.current.y, 0.02);
				setPointer({ x: currentRef.current.x, y: currentRef.current.y, active: true });
				rafRef.current = requestAnimationFrame(loop);
			};

			tick();
			loop();
			return () => {
				if (timeoutRef.current) window.clearTimeout(timeoutRef.current);
				timeoutRef.current = null;
				if (rafRef.current) cancelAnimationFrame(rafRef.current);
				rafRef.current = null;
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

