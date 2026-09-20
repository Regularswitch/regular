type CapabilitiesHeroProps = {
	headline: string;
};

export default function CapabilitiesHero({ headline }: CapabilitiesHeroProps) {
	return (
		<section className="capabilities-hero py-12 md:py-20" aria-label="Capabilities">
			<h1
				className="intro-headline font-hk"
				dangerouslySetInnerHTML={{ __html: headline }}
			/>
		</section>
	);
}
