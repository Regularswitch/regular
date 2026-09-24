'use client';

import DateTimeComponent from '../DateTimeComponent';
import type { ContactContent } from '../../lib/content/contact/defaults';
import BezierDivider from '../BezierDivider/BezierDivider';

type ContactPageProps = {
	content: ContactContent;
	locale?: 'en' | 'pt';
};

export default function ContactPage({ content, locale = 'en' }: ContactPageProps) {
	const clocksLabel = locale === 'pt' ? 'São Paulo e Paris' : 'São Paulo and Paris';

	return (
		<article className="contact-page">
			<section className="contact-intro py-10 md:py-14">
				<h1
					className="intro-headline max-w-4xl font-hk"
					dangerouslySetInnerHTML={{ __html: content.headline }}
				/>
			</section>

			<section className="contact-blocks pb-12 md:pb-16">
				<div className="grid gap-12 sm:grid-cols-2 lg:gap-16">
					{content.blocks.map((block) => (
						<div key={block.title} className="contact-block">
							<h2 className="font-hk text-sm font-medium tracking-[0.18em] text-(--fg) md:text-base">
								{block.title}
							</h2>
							<div
								className="contact-block-body mt-5 font-hk text-(--muted) md:mt-6"
								dangerouslySetInnerHTML={{ __html: block.body }}
							/>
						</div>
					))}
				</div>
			</section>
			<BezierDivider />
			<section className="contact-datetime  py-12 md:py-16" aria-label={clocksLabel}>
				<DateTimeComponent locale={locale} />
			</section>

			<div className="h-10" />
		</article>
	);
}
