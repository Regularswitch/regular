'use client';

import { useEffect, useRef, useState } from 'react';

import { getCursorColor, isBlobCursor } from '../../lib/cursorConfig';

const DEFAULT_SIZE = 16;
const HOVER_SIZE = 44;
const SMOOTHING = 0.22;
const SIZE_SMOOTHING = 0.24;

const HOVER_SELECTOR =
	'a[href], button:not(:disabled), .accordion-trigger, .custom-cursor-target, [data-custom-cursor]';

const IGNORE_SELECTOR = 'input, textarea, select, option, [contenteditable="true"], .custom-cursor-ignore';

function getHoverTarget(element: EventTarget | null): HTMLElement | null {
	if (!(element instanceof HTMLElement)) return null;
	if (element.closest(IGNORE_SELECTOR)) return null;
	const target = element.closest(HOVER_SELECTOR);
	return target instanceof HTMLElement ? target : null;
}

export default function CustomCursor() {
	const cursorRef = useRef<HTMLDivElement>(null);
	const [enabled, setEnabled] = useState(false);
	const [visible, setVisible] = useState(false);

	const pointer = useRef({ x: -100, y: -100 });
	const smooth = useRef({ x: -100, y: -100, size: DEFAULT_SIZE });
	const isHovering = useRef(false);
	const hasMoved = useRef(false);
	const reduceMotion = useRef(false);

	useEffect(() => {
		const color = getCursorColor();
		if (color) {
			document.documentElement.style.setProperty('--cursor-color', color);
		}
	}, []);

	useEffect(() => {
		const finePointer = window.matchMedia('(hover: hover) and (pointer: fine)');
		const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)');

		const syncEnabled = () => {
			const active = finePointer.matches;
			reduceMotion.current = reducedMotion.matches;
			setEnabled(active);

			if (!active) {
				hasMoved.current = false;
				setVisible(false);
				document.documentElement.classList.remove('has-custom-cursor');
			}
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

		const followSpeed = () => (reduceMotion.current ? 1 : SMOOTHING);
		const sizeSpeed = () => (reduceMotion.current ? 1 : SIZE_SMOOTHING);

		const applyPosition = (x: number, y: number, size: number) => {
			const el = cursorRef.current;
			if (!el) return;

			el.style.left = `${x}px`;
			el.style.top = `${y}px`;
			el.style.width = `${size}px`;
			el.style.height = `${size}px`;
		};

		const onMove = (event: MouseEvent) => {
			pointer.current.x = event.clientX;
			pointer.current.y = event.clientY;

			if (!hasMoved.current) {
				hasMoved.current = true;
				setVisible(true);
				document.documentElement.classList.add('has-custom-cursor');

				const half = smooth.current.size / 2;
				smooth.current.x = event.clientX - half;
				smooth.current.y = event.clientY - half;
				applyPosition(smooth.current.x, smooth.current.y, smooth.current.size);
			}
		};

		const onOver = (event: MouseEvent) => {
			if (getHoverTarget(event.target)) {
				isHovering.current = true;
			}
		};

		const onOut = (event: MouseEvent) => {
			const from = getHoverTarget(event.target);
			if (!from) return;

			const to = getHoverTarget(event.relatedTarget);
			if (!to) {
				isHovering.current = false;
			}
		};

		const onLeave = () => {
			setVisible(false);
		};

		const onEnter = () => {
			if (hasMoved.current) {
				setVisible(true);
			}
		};

		window.addEventListener('mousemove', onMove);
		document.addEventListener('mouseover', onOver);
		document.addEventListener('mouseout', onOut);
		document.documentElement.addEventListener('mouseleave', onLeave);
		document.documentElement.addEventListener('mouseenter', onEnter);

		let frame = 0;
		const tick = () => {
			if (hasMoved.current) {
				const targetSize = isHovering.current ? HOVER_SIZE : DEFAULT_SIZE;
				const follow = followSpeed();
				const sizeFollow = sizeSpeed();

				smooth.current.size += (targetSize - smooth.current.size) * sizeFollow;

				const half = smooth.current.size / 2;
				const targetX = pointer.current.x - half;
				const targetY = pointer.current.y - half;

				smooth.current.x += (targetX - smooth.current.x) * follow;
				smooth.current.y += (targetY - smooth.current.y) * follow;

				applyPosition(smooth.current.x, smooth.current.y, smooth.current.size);
			}

			frame = window.requestAnimationFrame(tick);
		};

		frame = window.requestAnimationFrame(tick);

		return () => {
			window.removeEventListener('mousemove', onMove);
			document.removeEventListener('mouseover', onOver);
			document.removeEventListener('mouseout', onOut);
			document.documentElement.removeEventListener('mouseleave', onLeave);
			document.documentElement.removeEventListener('mouseenter', onEnter);
			window.cancelAnimationFrame(frame);
		};
	}, [enabled]);

	if (!enabled) return null;

	return (
		<div
			ref={cursorRef}
			className={`custom-cursor${isBlobCursor() ? ' custom-cursor--blob' : ''}${visible ? ' is-visible' : ''}`}
			aria-hidden
		/>
	);
}
