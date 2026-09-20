import PageHeroMedia from '../PageHero/PageHeroMedia';

type EducationHeroProps = {
	image?: string;
	video?: string;
};

export default function EducationHero({ image, video }: EducationHeroProps) {
	return <PageHeroMedia image={image} video={video} label="Education" className="education-hero" />;
}
