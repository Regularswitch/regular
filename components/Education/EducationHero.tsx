import Image from 'next/image';

import { wpMediaUrl } from '../../lib/wpMediaUrl';

type EducationHeroProps = {
	image?: string;
	headline: string;
};

export default function EducationHero({ image, headline }: EducationHeroProps) {
	const heroSrc = image ? (wpMediaUrl(image) ?? image) : undefined;

	return (
		<section className="education-hero px-7 pt-8 md:pt-12" aria-label="Education">
			{heroSrc ? (
				<div className="education-hero-image relative aspect-[16/10] overflow-hidden rounded-xl bg-(--surface) md:aspect-[16/9]">
					<Image
						src={heroSrc}
						alt=""
						fill
						priority
						sizes="(max-width: 768px) 100vw, 90vw"
						className="object-cover object-center"
					/>
				</div>
			) : null}

			<div
				className="intro-headline mt-8 font-hk text-[clamp(1.75rem,4.5vw,3.125rem)] font-extrabold leading-[1.05] tracking-[-0.02em] md:mt-10"
				dangerouslySetInnerHTML={{ __html: headline }}
			/>
		</section>
	);
}
