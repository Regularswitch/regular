'use client';

import DateTimeComponent from '../DateTimeComponent';
import type { ContactContent } from '../../lib/contactDefaults';
import ContactHero from './ContactHero';

type ContactPageProps = {
	content: ContactContent;
	locale?: 'en' | 'pt';
};

export default function ContactPage({ content, locale = 'en' }: ContactPageProps) {
	const cityLabel = locale === 'pt' ? 'São Paulo' : 'São Paulo';
	const hasHero = Boolean(content.heroImage || content.heroVideo);

	return (
		<article className="contact-page">
			{hasHero ? <ContactHero image={content.heroImage} video={content.heroVideo} /> : null}

			<section className="contact-intro py-10 md:py-14">
				<div
					className="intro-headline max-w-4xl font-hk text-[clamp(1.75rem,4.5vw,3.125rem)] font-medium leading-[1.05] tracking-[-0.02em]"
					dangerouslySetInnerHTML={{ __html: content.headline }}
				/>
			</section>

			<section className="contact-blocks pb-12 md:pb-16">
				<div className="grid gap-12 sm:grid-cols-2 lg:gap-16">
					{content.blocks.map((block) => (
						<div key={block.title} className="contact-block">
							<h2 className="font-hk text-sm font-semibold tracking-[0.18em] text-(--fg) md:text-base">
								{block.title}
							</h2>
							<div
								className="contact-block-body mt-5 font-hk text-lg leading-[1.65] text-(--muted) md:mt-6 md:text-xl md:leading-[1.7]"
								dangerouslySetInnerHTML={{ __html: block.body }}
							/>
						</div>
					))}
				</div>
			</section>

			<section className="contact-datetime border-t border-white/10 py-12 md:py-16" aria-label={cityLabel}>
				<DateTimeComponent locale={locale} />
			</section>

			<div className="h-10" />
		</article>
	);
}
