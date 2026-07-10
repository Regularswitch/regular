import Image from 'next/image';

import { wpMediaUrl } from '../../lib/wpMediaUrl';

type AboutHeroProps = {
	image?: string;
};

export default function AboutHero({ image }: AboutHeroProps) {
	const heroSrc = image ? (wpMediaUrl(image) ?? image) : undefined;

	if (!heroSrc) return null;

	return (
		<section className="about-hero px-7 pt-8 md:pt-12" aria-label="About">
			<div className="about-hero-image relative aspect-2/1 overflow-hidden rounded-xl bg-(--surface) md:aspect-21/9">
				<Image
					src={heroSrc}
					alt=""
					fill
					priority
					sizes="(max-width: 768px) 100vw, 90vw"
					className="object-cover object-center"
				/>
			</div>
		</section>
	);
}
