import PageHeroMedia from '../PageHero/PageHeroMedia';

type AboutHeroProps = {
	image?: string;
	video?: string;
};

export default function AboutHero({ image, video }: AboutHeroProps) {
	return <PageHeroMedia image={image} video={video} label="About" className="about-hero" />;
}
