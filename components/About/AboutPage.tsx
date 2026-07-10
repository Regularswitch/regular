'use client';

import Link from 'next/link';

import type { AboutContent } from '../../lib/aboutDefaults';
import type { Projects } from '../../types';
import LatestProjects from '../LatestProjects/LatestProjects';
import { useSiteUiLocale } from '../SiteUi/SiteUiProvider';
import AboutAccordionPanel from './AboutAccordionPanel';
import AboutHero from './AboutHero';

type AboutPageProps = {
	content: AboutContent;
	latestProjects: Projects;
	locale?: 'en' | 'pt';
};

export default function AboutPage({ content, latestProjects, locale = 'en' }: AboutPageProps) {
	const prefix = locale === 'pt' ? '/PT' : '';
	const workHref = `${prefix}/work`.replace(/^\/\//, '/') || '/work';
	const siteUi = useSiteUiLocale(locale);
	const cta = siteUi.labels.seeMoreWork;

	return (
		<article className="about-page">
			<AboutHero image={content.heroImage} />

			<section className="about-intro px-7 py-10 md:grid md:grid-cols-2 md:gap-12 md:py-14 lg:gap-16">
				<div
					className="intro-headline font-hk text-[clamp(1.75rem,4.5vw,3.125rem)] font-extrabold leading-[1.05] tracking-[-0.02em]"
					dangerouslySetInnerHTML={{ __html: content.headline }}
				/>

				<div
					className="about-body intro-body mt-8 font-hk text-base leading-relaxed md:mt-0 md:text-lg"
					dangerouslySetInnerHTML={{ __html: content.body }}
				/>
			</section>

			<div className="px-7 pb-10 md:pb-14">
				<AboutAccordionPanel sections={content.accordionSections} />
			</div>

			<div className="flex justify-center px-7 pb-12 md:pb-16">
				<Link href={workHref} className="selected-projects-cta font-hk">
					{cta}
				</Link>
			</div>

			<LatestProjects projects={latestProjects} locale={locale} />

			<div className="h-10" />
		</article>
	);
}
