'use client';

import { useEffect, useRef, useState } from 'react';

type Options = {
	sectionRef: React.RefObject<HTMLElement | null>;
};

// Computes a 0..1 progress based on how centered the section is in the viewport,
// matching the original logic:
// scrollProgress = clamp(0.5 + center * 0.5, 0, 1)
export default function useScrollProgress({ sectionRef }: Options) {
	const [progress, setProgress] = useState(0);
	const inViewRef = useRef(false);
	const rafRef = useRef<number | null>(null);

	useEffect(() => {
		const el = sectionRef.current;
		if (!el) return;

		const compute = () => {
			const rect = el.getBoundingClientRect();
			const vh = window.innerHeight || document.documentElement.clientHeight;
			const fullyAbove = rect.bottom <= 0;
			const fullyBelow = rect.top >= vh;
			if (fullyAbove || fullyBelow) {
				setProgress(0);
				return;
			}
			const center = (vh / 2 - (rect.top + rect.height / 2)) / (vh / 2);
			const p = Math.max(0, Math.min(1, 0.5 + center * 0.5));
			setProgress(p);
		};

		const loop = () => {
			if (inViewRef.current) compute();
			rafRef.current = requestAnimationFrame(loop);
		};

		const io = new IntersectionObserver(
			(entries) => {
				inViewRef.current = entries[0]?.isIntersecting ?? false;
				if (inViewRef.current) compute();
			},
			{ threshold: [0, 0.01, 0.1, 0.25, 0.5, 0.75, 1] },
		);
		io.observe(el);

		loop();

		return () => {
			io.disconnect();
			if (rafRef.current) cancelAnimationFrame(rafRef.current);
			rafRef.current = null;
		};
	}, [sectionRef]);

	return progress;
}

