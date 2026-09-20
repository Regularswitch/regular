type HomeVideoSectionProps = {
	video?: string;
	poster?: string;
	locale?: 'en' | 'pt';
};

export default function HomeVideoSection({ video, poster, locale = 'en' }: HomeVideoSectionProps) {
	const src = video?.trim() || '';
	const posterSrc = poster?.trim() || '';
	if (!src && !posterSrc) return null;

	const label = locale === 'pt' ? 'Vídeo' : 'Video';

	return (
		<section className="home-video-section pt-6 pb-0 md:pt-10" aria-label={label}>
			<div className="home-video-frame relative aspect-video overflow-hidden rounded-[5px] bg-(--surface)">
				{src ? (
					<video
						className="absolute inset-0 h-full w-full object-cover object-center"
						src={src}
						poster={posterSrc || undefined}
						autoPlay
						muted
						loop
						playsInline
						preload="metadata"
					/>
				) : (
					<img src={posterSrc} alt="" className="absolute inset-0 h-full w-full object-cover object-center" />
				)}
			</div>
		</section>
	);
}
