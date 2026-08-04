'use client';

import { forwardRef, useEffect, useState } from 'react';
import Image from 'next/image';
import { FastAverageColor } from 'fast-average-color';

import { mediaUrlCandidates, resolveLoadableImageUrl } from '../lib/wp/mediaUrl';

const DEFAULT_TEXT_COLOR = 'white';

function luminanceToTextColor(value: [number, number, number, number]) {
	const luminance = (0.299 * value[0] + 0.587 * value[1] + 0.114 * value[2]) / 255;
	return luminance > 0.52 ? 'black' : 'white';
}

const BackgroundProject = forwardRef<
	HTMLDivElement,
	{ bg: string; video?: string; visible: boolean; onColorExtract: (color: string) => void }
>(({ bg, video, visible, onColorExtract }, ref) => {
	const [imageSrc, setImageSrc] = useState(bg);

	useEffect(() => {
		setImageSrc(bg);
	}, [bg]);

	useEffect(() => {
		if (!bg || bg.endsWith('.mp4')) return;

		let cancelled = false;
		const fac = new FastAverageColor();

		(async () => {
			const loadableUrl = await resolveLoadableImageUrl(bg);
			if (cancelled) return;

			if (!loadableUrl) {
				onColorExtract(DEFAULT_TEXT_COLOR);
				return;
			}

			setImageSrc(loadableUrl);

			try {
				const color = await fac.getColorAsync(loadableUrl);
				if (!cancelled) onColorExtract(luminanceToTextColor(color.value));
			} catch {
				if (!cancelled) onColorExtract(DEFAULT_TEXT_COLOR);
			}
		})();

		return () => {
			cancelled = true;
			fac.destroy();
		};
	}, [bg, onColorExtract]);

	const handleImageError = () => {
		const candidates = mediaUrlCandidates(imageSrc);
		const currentIndex = candidates.indexOf(imageSrc);
		const next = candidates[currentIndex + 1];
		if (next) setImageSrc(next);
	};

	return (
		<div
			ref={ref}
			className={`bg-container fixed top-0 left-0 z-[-1] w-full transition-opacity duration-300 ${visible ? 'opacity-100' : 'opacity-0'}`}
		>
			<div className="relative h-56 w-full overflow-hidden md:h-auto lg:h-auto">
				{!video && imageSrc ? (
					<Image
						alt="Background"
						src={imageSrc}
						width={1920}
						height={1080}
						priority
						unoptimized={imageSrc.includes('regularswitch-wp.local')}
						onError={handleImageError}
						style={{ width: '100%', height: 'auto', objectFit: 'contain' }}
					/>
				) : null}
				{video ? (
					<video src={video} muted autoPlay loop playsInline className="h-auto w-full object-contain" />
				) : null}
			</div>
		</div>
	);
});

BackgroundProject.displayName = 'BackgroundProject';

export default BackgroundProject;
