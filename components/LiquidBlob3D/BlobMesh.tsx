'use client';

import { useFrame } from '@react-three/fiber';
import { useMemo, useRef } from 'react';
import * as THREE from 'three';
import { fragmentShader, vertexShader } from './shaders';

type BlobMeshProps = {
	color1: string;
	color2: string;
	palette: string[];
	intensity: number;
	radius: number;
	detail: number;
	scrollProgress: number;
	pointer: { x: number; y: number };
	paused: boolean;
};

type Uniforms = {
	u_time: { value: number };
	u_intensity: { value: number };
	u_pointer: { value: THREE.Vector2 };
	u_color1: { value: THREE.Color };
	u_color2: { value: THREE.Color };
};

function pickNextIndex(length: number, current: number) {
	if (length <= 1) return current;
	let next = current;
	while (next === current) next = Math.floor(Math.random() * length);
	return next;
}

export default function BlobMesh({ color1, color2, palette, intensity, radius, detail, scrollProgress, pointer, paused }: BlobMeshProps) {
	const meshRef = useRef<THREE.Mesh>(null);
	const pointerTarget = useMemo(() => new THREE.Vector2(0, 0), []);
	const paletteColors = useMemo(() => (palette.length ? palette : [color1, color2]), [palette, color1, color2]);
	const colorFromRef = useRef({ a: new THREE.Color(color1), b: new THREE.Color(color2) });
	const colorToRef = useRef({ a: new THREE.Color(color1), b: new THREE.Color(color2) });
	const colorIdxRef = useRef({ a: 0, b: Math.min(1, paletteColors.length - 1) });
	const nextColorAtRef = useRef(0);

	const uniforms = useMemo<Uniforms>(
		() => ({
			u_time: { value: 0 },
			u_intensity: { value: intensity },
			u_pointer: { value: new THREE.Vector2(0, 0) },
			u_color1: { value: new THREE.Color(color1) },
			u_color2: { value: new THREE.Color(color2) },
		}),
		[color1, color2, intensity],
	);

	useFrame((state) => {
		if (!meshRef.current || paused) return;

		const t = state.clock.getElapsedTime();
		uniforms.u_time.value = t;

		// Smoothly cycle u_color1/u_color2 through the provided palette (no shader changes).
		if (t >= nextColorAtRef.current) {
			const nextA = pickNextIndex(paletteColors.length, colorIdxRef.current.a);
			const nextB = pickNextIndex(paletteColors.length, colorIdxRef.current.b);
			colorIdxRef.current.a = nextA;
			colorIdxRef.current.b = nextB;
			colorFromRef.current.a.copy(uniforms.u_color1.value);
			colorFromRef.current.b.copy(uniforms.u_color2.value);
			colorToRef.current.a.set(paletteColors[nextA] ?? color1);
			colorToRef.current.b.set(paletteColors[nextB] ?? color2);
			nextColorAtRef.current = t + 4 + Math.random() * 3; // 4–7s
		}
		uniforms.u_color1.value.lerp(colorToRef.current.a, 0.02);
		uniforms.u_color2.value.lerp(colorToRef.current.b, 0.02);

		// Pointer damping identical to original (lerp 0.08)
		pointerTarget.set(pointer.x, pointer.y);
		uniforms.u_pointer.value.lerp(pointerTarget, 0.08);

		// Rotation + scroll influence (ported 1:1 from original)
		meshRef.current.rotation.y += 0.0012 + pointer.x * 0.002;
		meshRef.current.rotation.x += 0.0006 + pointer.y * 0.001;

		const s = 0.94 + scrollProgress * 0.1;
		meshRef.current.scale.setScalar(s);

		uniforms.u_intensity.value = intensity + Math.sin(t * 0.8) * 0.06 + scrollProgress * 0.1;
	});

	return (
		<mesh ref={meshRef} rotation={[-0.2, 0.6, 0]}>
			<icosahedronGeometry args={[radius, detail]} />
			<shaderMaterial uniforms={uniforms} vertexShader={vertexShader} fragmentShader={fragmentShader} transparent />
		</mesh>
	);
}

