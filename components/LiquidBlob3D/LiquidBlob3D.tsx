'use client';

import { Canvas } from '@react-three/fiber';
import { useEffect, useMemo, useRef, useState } from 'react';
import BlobMesh from './BlobMesh';
import usePointerNormalized from './usePointerNormalized';
import useScrollProgress from './useScrollProgress';

export interface LiquidBlob3DProps {
	color1?: string;
	color2?: string;
	palette?: string[];
	intensity?: number;
	blobRadius?: number;
	blobDetail?: number;
	className?: string;
	children?: React.ReactNode;
}

function useReducedMotion() {
	const [reduced, setReduced] = useState(false);
	useEffect(() => {
		const m = window.matchMedia('(prefers-reduced-motion: reduce)');
		const sync = () => setReduced(m.matches);
		sync();
		m.addEventListener('change', sync);
		return () => m.removeEventListener('change', sync);
	}, []);
	return reduced;
}

export default function LiquidBlob3D({
	color1 = '#6ae4ff',
	color2 = '#7e79ff',
	palette = ['#7B00FF', '#D400FF', '#FF5FAF', '#304FFE', '#FFD500'],
	intensity = 0.85,
	blobRadius = 1.55,
	blobDetail = 80,
	className,
	children,
}: LiquidBlob3DProps) {
	const sectionRef = useRef<HTMLElement | null>(null);
	const stageRef = useRef<HTMLDivElement | null>(null);

	const reducedMotion = useReducedMotion();
	const pointer = usePointerNormalized({ containerRef: stageRef as React.RefObject<HTMLElement | null> });
	const scrollProgress = useScrollProgress({ sectionRef });

	const [inView, setInView] = useState(true);

	useEffect(() => {
		const el = sectionRef.current;
		if (!el) return;
		const io = new IntersectionObserver(
			(entries) => setInView(entries[0]?.isIntersecting ?? true),
			{ threshold: 0.01 },
		);
		io.observe(el);
		return () => io.disconnect();
	}, []);

	const dpr = useMemo(() => {
		if (typeof window === 'undefined') return 1;
		return [1, Math.min(window.devicePixelRatio || 1, 1.6)] as [number, number];
	}, []);

	const [isMobile, setIsMobile] = useState(false);
	useEffect(() => {
		const mq = window.matchMedia('(max-width: 640px)');
		const sync = () => setIsMobile(mq.matches);
		sync();
		mq.addEventListener('change', sync);
		return () => mq.removeEventListener('change', sync);
	}, []);

	return (
		<section
			ref={sectionRef}
			className={className}
			style={{
				position: 'relative',
				isolation: 'isolate',
				overflow: 'hidden',
			}}
		>
			<div ref={stageRef} aria-hidden="true" style={{ position: 'absolute', inset: 0 }}>
				{!reducedMotion && (
					<Canvas
						dpr={dpr}
						camera={{ fov: isMobile ? 40 : 36, position: [0, 0, isMobile ? 7 : 6.4] }}
						gl={{ antialias: true, alpha: true, powerPreference: 'high-performance' }}
						style={{ width: '100%', height: '100%', filter: 'drop-shadow(0 40px 80px rgba(0,0,0,.35))' }}
					>
						<BlobMesh
							color1={color1}
							color2={color2}
							palette={palette}
							intensity={intensity}
							radius={isMobile ? blobRadius * 0.9 : blobRadius}
							detail={isMobile ? Math.max(40, Math.floor(blobDetail * 0.75)) : blobDetail}
							scrollProgress={scrollProgress}
							pointer={{ x: pointer.x, y: pointer.y }}
							paused={!inView}
						/>
					</Canvas>
				)}
			</div>

			{children ? (
				<div style={{ position: 'relative', zIndex: 2, pointerEvents: 'auto' }}>{children}</div>
			) : null}
		</section>
	);
}

