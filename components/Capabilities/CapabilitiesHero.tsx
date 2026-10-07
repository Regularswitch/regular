type CapabilitiesHeroProps = {
	headline: string;
};

export default function CapabilitiesHero({ headline }: CapabilitiesHeroProps) {
	if (!headline?.trim()) return null;

	return (
		<section
			className="capabilities-hero py-10 md:grid md:grid-cols-2 md:items-start md:gap-12 md:py-14 lg:gap-16"
			aria-label="Capabilities"
		>
			<h1
				className="intro-headline min-w-0 font-hk"
				dangerouslySetInnerHTML={{ __html: headline }}
			/>
			<div className="hidden min-w-0 md:block" aria-hidden />
		</section>
	);
}
