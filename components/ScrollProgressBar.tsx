'use client';

import { useEffect, useRef } from 'react';

/**
 * Barra fixa no rodapé: progresso de scroll da página (0 → 1).
 */
export default function ScrollProgressBar() {
	const fillRef = useRef<HTMLDivElement>(null);

	useEffect(() => {
		const fill = fillRef.current;
		if (!fill) return;

		const update = () => {
			const doc = document.documentElement;
			const max = doc.scrollHeight - doc.clientHeight;
			const progress = max > 0 ? Math.min(1, Math.max(0, doc.scrollTop / max)) : 0;
			fill.style.transform = `scaleX(${progress})`;
		};

		update();
		window.addEventListener('scroll', update, { passive: true });
		window.addEventListener('resize', update);
		return () => {
			window.removeEventListener('scroll', update);
			window.removeEventListener('resize', update);
		};
	}, []);

	return (
		<div className="scroll-progress" aria-hidden="true">
			<div ref={fillRef} className="scroll-progress-fill" />
		</div>
	);
}
