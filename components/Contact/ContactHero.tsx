import PageHeroMedia from '../PageHero/PageHeroMedia';

type ContactHeroProps = {
	image?: string;
	video?: string;
};

export default function ContactHero({ image, video }: ContactHeroProps) {
	return <PageHeroMedia image={image} video={video} label="Contact" className="contact-hero" />;
}
