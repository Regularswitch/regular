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
				className="intro-headline mx-auto mt-10 max-w-4xl text-center font-hk text-[clamp(1.35rem,3.6vw,2.25rem)] font-medium leading-[1.2] tracking-[-0.02em] md:mt-14"
				dangerouslySetInnerHTML={{ __html: headline }}
			/>
		</section>
	);
}
