'use client';

import { useCallback, useLayoutEffect, useRef } from 'react';

const CANVAS_HEIGHT = 60;
const LINE_Y = 30;
const AMPLITUDE_FACTOR = 0.22;
const MAX_PROGRESS = 14;

function lerp(from: number, to: number, amount: number) {
	return from * (1 - amount) + to * amount;
}

export default function BezierDivider() {
	const containerRef = useRef<HTMLDivElement>(null);
	const svgRef = useRef<SVGSVGElement>(null);
	const pathRef = useRef<SVGPathElement>(null);
	const progressRef = useRef(0);
	const xRef = useRef(0.5);
	const timeRef = useRef(Math.PI / 2);
	const reqIdRef = useRef<number | null>(null);
	const reducedMotionRef = useRef(false);

	const setPath = useCallback((progress: number) => {
		const container = containerRef.current;
		const path = pathRef.current;
		const svg = svgRef.current;
		if (!container || !path || !svg) return;

		const width = container.getBoundingClientRect().width;
		if (width <= 0) return;

		svg.setAttribute('viewBox', `0 0 ${width} ${CANVAS_HEIGHT}`);
		path.setAttribute(
			'd',
			`M 0 ${LINE_Y} Q ${width * xRef.current} ${LINE_Y + progress}, ${width} ${LINE_Y}`,
		);
	}, []);

	const resetAnimation = useCallback(() => {
		timeRef.current = Math.PI / 2;
		progressRef.current = 0;
		reqIdRef.current = null;
		setPath(0);
	}, [setPath]);

	const animateOutRef = useRef<() => void>(() => {});

	animateOutRef.current = () => {
		const progress = progressRef.current;
		const newProgress = progress * Math.sin(timeRef.current);

		progressRef.current = lerp(progress, 0, 0.025);
		timeRef.current += 0.2;
		setPath(newProgress);

		if (Math.abs(progressRef.current) > 0.75) {
			reqIdRef.current = requestAnimationFrame(() => animateOutRef.current());
		} else {
			resetAnimation();
		}
	};

	useLayoutEffect(() => {
		reducedMotionRef.current = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
		setPath(0);

		const container = containerRef.current;
		if (!container) return undefined;

		const resizeObserver = new ResizeObserver(() => {
			setPath(progressRef.current);
		});
		resizeObserver.observe(container);

		return () => {
			resizeObserver.disconnect();
			if (reqIdRef.current) cancelAnimationFrame(reqIdRef.current);
		};
	}, [setPath]);

	const handleMouseEnter = () => {
		if (reducedMotionRef.current) return;

		if (reqIdRef.current) {
			cancelAnimationFrame(reqIdRef.current);
			resetAnimation();
		}
	};

	const handleMouseMove = (event: React.MouseEvent<HTMLDivElement>) => {
		if (reducedMotionRef.current) return;

		const bounds = containerRef.current?.getBoundingClientRect();
		if (!bounds?.width) return;

		xRef.current = (event.clientX - bounds.left) / bounds.width;
		progressRef.current = Math.max(
			-MAX_PROGRESS,
			Math.min(MAX_PROGRESS, progressRef.current + event.movementY * AMPLITUDE_FACTOR),
		);
		setPath(progressRef.current);
	};

	const handleMouseLeave = () => {
		if (reducedMotionRef.current) return;
		animateOutRef.current();
	};

	return (
		<div ref={containerRef} className="bezier-divider" aria-hidden>
			<div
				className="bezier-divider-hit"
				onMouseEnter={handleMouseEnter}
				onMouseMove={handleMouseMove}
				onMouseLeave={handleMouseLeave}
			/>
			<svg
				ref={svgRef}
				className="bezier-divider-svg"
				preserveAspectRatio="none"
				aria-hidden
			>
				<path
					ref={pathRef}
					className="bezier-divider-path"
					d={`M 0 ${LINE_Y} L 100 ${LINE_Y}`}
				/>
			</svg>
		</div>
	);
}
