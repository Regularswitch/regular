type LegalWpPageProps = {
	title?: string;
	content: string;
};

export default function LegalWpPage({ title, content }: LegalWpPageProps) {
	return (
		<article className="legal-page mx-auto max-w-3xl py-10 md:py-14">
			{title ? (
				<h1 className="mb-8 font-hk text-[clamp(1.75rem,4vw,2.5rem)] font-medium leading-tight tracking-[-0.02em]">
					{title}
				</h1>
			) : null}
			<div
				className="legal-content font-hk text-base leading-relaxed text-(--fg) [&_a]:underline [&_p+p]:mt-4"
				dangerouslySetInnerHTML={{ __html: content }}
			/>
		</article>
	);
}
