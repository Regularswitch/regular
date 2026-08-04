import PageHeroMedia from '../PageHero/PageHeroMedia';

type EducationHeroProps = {
	image?: string;
	video?: string;
	headline: string;
};

export default function EducationHero({ image, video, headline }: EducationHeroProps) {
	return (
		<section className="education-hero" aria-label="Education">
			<PageHeroMedia image={image} video={video} showEmptySlot asSection={false} fullBleed />

			<div
				className="intro-headline mt-10 max-w-4xl font-hk text-[clamp(1.75rem,4.5vw,3.125rem)] font-medium leading-[1.05] tracking-[-0.02em] md:mt-14"
				dangerouslySetInnerHTML={{ __html: headline }}
			/>
		</section>
	);
}
