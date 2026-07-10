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

	return (
		<article className="contact-page">
			<ContactHero image={content.heroImage} />

			<section className="contact-intro px-7 py-10 md:py-14">
				<div
					className="intro-headline max-w-4xl font-hk text-[clamp(1.75rem,4.5vw,3.125rem)] font-extrabold leading-[1.05] tracking-[-0.02em]"
					dangerouslySetInnerHTML={{ __html: content.headline }}
				/>
			</section>

			<section className="contact-blocks px-7 pb-12 md:pb-16">
				<div className="grid gap-10 sm:grid-cols-2 lg:gap-14">
					{content.blocks.map((block) => (
						<div key={block.title} className="contact-block">
							<h2 className="font-hk text-xs font-semibold tracking-[0.18em] text-(--fg) md:text-sm">
								{block.title}
							</h2>
							<div
								className="contact-block-body mt-4 font-hk text-base leading-relaxed text-(--muted) md:mt-5 md:text-lg"
								dangerouslySetInnerHTML={{ __html: block.body }}
							/>
						</div>
					))}
				</div>
			</section>

			<section className="contact-datetime border-t border-white/10 px-7 py-12 md:py-16" aria-label={cityLabel}>
				<DateTimeComponent locale={locale} />
			</section>

			<div className="h-10" />
		</article>
	);
}
