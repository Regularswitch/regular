type CapabilitiesHeroProps = {
	headline: string;
	headlineRight?: string;
};

export default function CapabilitiesHero({ headline, headlineRight }: CapabilitiesHeroProps) {
	const left = headline?.trim() ?? '';
	const right = headlineRight?.trim() ?? '';
	if (!left && !right) return null;

	return (
		<section
			className="capabilities-hero flex flex-col py-12 md:grid md:grid-cols-2 md:items-start md:gap-12 md:py-20 md:pb-28 lg:gap-16"
			aria-label="Capabilities"
		>
			{left ? (
				<h1
					className="intro-headline min-w-0 font-hk"
					dangerouslySetInnerHTML={{ __html: left }}
				/>
			) : (
				<div className="hidden min-w-0 md:block" aria-hidden />
			)}
			{right ? (
				<div
					className={`intro-headline min-w-0 font-hk${left ? ' mt-8 md:mt-0' : ''}`}
					dangerouslySetInnerHTML={{ __html: right }}
				/>
			) : (
				<div className="hidden min-w-0 md:block" aria-hidden />
			)}
		</section>
	);
}
