type CapabilitiesHeroProps = {
	headline: string;
};

export default function CapabilitiesHero({ headline }: CapabilitiesHeroProps) {
	return (
		<section className="capabilities-hero px-7 pt-12 md:pt-20" aria-label="Capabilities">
			<div
				className="intro-headline font-hk text-[clamp(1.75rem,4.5vw,3.125rem)] font-extrabold leading-[1.05] tracking-[-0.02em]"
				dangerouslySetInnerHTML={{ __html: headline }}
			/>
		</section>
	);
}
