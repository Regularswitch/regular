import Image from 'next/image';

import { wpMediaUrl } from '../../lib/wpMediaUrl';

type ContactHeroProps = {
	image?: string;
};

export default function ContactHero({ image }: ContactHeroProps) {
	const heroSrc = image ? (wpMediaUrl(image) ?? image) : undefined;

	if (!heroSrc) return null;

	return (
		<section className="contact-hero" aria-label="Contact">
			<div className="contact-hero-image relative aspect-square overflow-hidden rounded-xl bg-(--surface) md:aspect-6/3">
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
