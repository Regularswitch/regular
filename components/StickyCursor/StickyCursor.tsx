'use client';

import { useEffect, useRef, useState } from 'react';

const DEFAULT_SIZE = 15;
const HOVER_SIZE = 60;
const SMOOTHING = 0.18;
const SIZE_SMOOTHING = 0.22;

const STICKY_SELECTOR =
	'a[href], button:not(:disabled), .accordion-trigger, .sticky-cursor-target, [data-sticky-cursor]';

const IGNORE_SELECTOR = 'input, textarea, select, option, [contenteditable="true"], .sticky-cursor-ignore';

function isStickyTarget(element: EventTarget | null): element is HTMLElement {
	if (!(element instanceof HTMLElement)) return false;
	if (element.closest(IGNORE_SELECTOR)) return false;
	return Boolean(element.closest(STICKY_SELECTOR));
}

export default function StickyCursor() {
	const cursorRef = useRef<HTMLDivElement>(null);
	const stickyTargetRef = useRef<HTMLElement | null>(null);
	const [enabled, setEnabled] = useState(false);

	const pointer = useRef({ x: 0, y: 0 });
	const smooth = useRef({ x: 0, y: 0, size: DEFAULT_SIZE });
	const motion = useRef({ scaleX: 1, scaleY: 1, rotate: 0 });

	useEffect(() => {
		const finePointer = window.matchMedia('(hover: hover) and (pointer: fine)');
		const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)');

		const syncEnabled = () => {
			const active = finePointer.matches && !reducedMotion.matches;
			setEnabled(active);
			document.documentElement.classList.toggle('has-custom-cursor', active);
		};

		syncEnabled();
		finePointer.addEventListener('change', syncEnabled);
		reducedMotion.addEventListener('change', syncEnabled);

		return () => {
			finePointer.removeEventListener('change', syncEnabled);
			reducedMotion.removeEventListener('change', syncEnabled);
			document.documentElement.classList.remove('has-custom-cursor');
		};
	}, []);

	useEffect(() => {
		if (!enabled) return;

		const onMove = (event: MouseEvent) => {
			pointer.current.x = event.clientX;
			pointer.current.y = event.clientY;
		};

		const onOver = (event: MouseEvent) => {
			const target =
				event.target instanceof HTMLElement ? event.target.closest(STICKY_SELECTOR) : null;

			if (target instanceof HTMLElement && !target.closest(IGNORE_SELECTOR)) {
				stickyTargetRef.current = target;
			}
		};

		const onOut = (event: MouseEvent) => {
			const from = event.target;
			const to = event.relatedTarget;
			if (!isStickyTarget(from)) return;

			const leavingTarget = from instanceof HTMLElement ? from.closest(STICKY_SELECTOR) : null;
			const enteringTarget =
				to instanceof HTMLElement ? to.closest(STICKY_SELECTOR) : null;

			if (leavingTarget && leavingTarget !== enteringTarget) {
				stickyTargetRef.current = null;
				motion.current.scaleX = 1;
				motion.current.scaleY = 1;
				motion.current.rotate = 0;
			}
		};

		window.addEventListener('mousemove', onMove);
		document.addEventListener('mouseover', onOver);
		document.addEventListener('mouseout', onOut);

		let frame = 0;
		const tick = () => {
			const sticky = stickyTargetRef.current;
			const hovered = Boolean(sticky);
			const targetSize = hovered ? HOVER_SIZE : DEFAULT_SIZE;

			let targetX = pointer.current.x - targetSize / 2;
			let targetY = pointer.current.y - targetSize / 2;
			let scaleX = 1;
			let scaleY = 1;
			let rotate = 0;

			if (sticky) {
				const rect = sticky.getBoundingClientRect();
				const centerX = rect.left + rect.width / 2;
				const centerY = rect.top + rect.height / 2;
				const distanceX = pointer.current.x - centerX;
				const distanceY = pointer.current.y - centerY;

				rotate = Math.atan2(distanceY, distanceX);

				const absDistance = Math.max(Math.abs(distanceX), Math.abs(distanceY));
				const stretchX = Math.min(absDistance / Math.max(rect.height / 2, 1), 1);
				const stretchY = Math.min(absDistance / Math.max(rect.width / 2, 1), 1);

				scaleX = 1 + stretchX * 0.3;
				scaleY = 1 - stretchY * 0.2;

				targetX = centerX - targetSize / 2 + distanceX * 0.1;
				targetY = centerY - targetSize / 2 + distanceY * 0.1;
			}

			smooth.current.x += (targetX - smooth.current.x) * SMOOTHING;
			smooth.current.y += (targetY - smooth.current.y) * SMOOTHING;
			smooth.current.size += (targetSize - smooth.current.size) * SIZE_SMOOTHING;

			motion.current.scaleX = scaleX;
			motion.current.scaleY = scaleY;
			motion.current.rotate = rotate;

			const el = cursorRef.current;
			if (el) {
				el.style.left = `${smooth.current.x}px`;
				el.style.top = `${smooth.current.y}px`;
				el.style.width = `${smooth.current.size}px`;
				el.style.height = `${smooth.current.size}px`;
				el.style.transform = `rotate(${motion.current.rotate}rad) scaleX(${motion.current.scaleX}) scaleY(${motion.current.scaleY})`;
			}

			frame = window.requestAnimationFrame(tick);
		};

		frame = window.requestAnimationFrame(tick);

		return () => {
			window.removeEventListener('mousemove', onMove);
			document.removeEventListener('mouseover', onOver);
			document.removeEventListener('mouseout', onOut);
			window.cancelAnimationFrame(frame);
		};
	}, [enabled]);

	if (!enabled) return null;

	return <div ref={cursorRef} className="sticky-cursor" aria-hidden />;
}
