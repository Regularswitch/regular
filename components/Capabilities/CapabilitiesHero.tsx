type CapabilitiesHeroProps = {
	headline: string;
};

export default function CapabilitiesHero({ headline }: CapabilitiesHeroProps) {
	return (
		<section className="capabilities-hero py-12 md:py-20" aria-label="Capabilities">
			<div
				className="intro-headline font-hk text-[clamp(1.75rem,4.5vw,3.125rem)] font-medium leading-[1.05] tracking-[-0.02em]"
				dangerouslySetInnerHTML={{ __html: headline }}
			/>
		</section>
	);
}
