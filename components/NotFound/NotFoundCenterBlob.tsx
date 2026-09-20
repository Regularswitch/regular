'use client';

import { Canvas, useFrame } from '@react-three/fiber';
import { useEffect, useRef, useState } from 'react';

import BlobMesh from '../LiquidBlob3D/BlobMesh';
import type { BlobVisual } from '../../types';

type NotFoundCenterBlobProps = {
	blob: BlobVisual;
};

function CenterBlobMesh({ blob, paused }: { blob: BlobVisual; paused: boolean }) {
	const pointer = useRef({ x: 0, y: 0 });

	useFrame((state) => {
		const t = state.clock.getElapsedTime();
		pointer.current.x = Math.sin(t * 0.42) * 0.38;
		pointer.current.y = Math.cos(t * 0.36) * 0.3;
	});

	return (
		<BlobMesh
			color1={blob.color1}
			color2={blob.color2}
			palette={blob.palette}
			intensity={0.78}
			radius={1.42}
			detail={80}
			scrollProgress={0}
			pointer={pointer.current}
			paused={paused}
			opacity={1}
		/>
	);
}

export default function NotFoundCenterBlob({ blob }: NotFoundCenterBlobProps) {
	const [reducedMotion, setReducedMotion] = useState(false);

	useEffect(() => {
		const media = window.matchMedia('(prefers-reduced-motion: reduce)');
		const sync = () => setReducedMotion(media.matches);
		sync();
		media.addEventListener('change', sync);
		return () => media.removeEventListener('change', sync);
	}, []);

	return (
		<div className="not-found-center-blob" aria-hidden="true">
			{!reducedMotion && (
				<Canvas
					camera={{ fov: 36, position: [0, 0, 6.2] }}
					gl={{ antialias: true, alpha: true, powerPreference: 'high-performance' }}
					dpr={[1, Math.min(typeof window !== 'undefined' ? window.devicePixelRatio || 1 : 1, 1.75)]}
				>
					<CenterBlobMesh blob={blob} paused={reducedMotion} />
				</Canvas>
			)}
		</div>
	);
}
