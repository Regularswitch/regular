'use client';

import Link from 'next/link';
import { useMemo } from 'react';

import LatestProjects from '../LatestProjects/LatestProjects';
import { useSiteUiLocale } from '../SiteUi/SiteUiProvider';
import { PROJECTS_PAGE_SLUG, pagePath } from '../../lib/pageSlugs';
import { withLocalePrefix } from '../../lib/resolveSiteUi';
import type { Project, ProjectMeta, Projects, ProjectStructuredData } from '../../types';
import {
	extractImagesFromHtml,
	parseAccordionSections,
} from '../../lib/parseProjectContent';
import ProjectAccordion from './ProjectAccordion';
import ProjectGallery from './ProjectGallery';
import ProjectHero from './ProjectHero';

type ProjectPageProps = {
	project: Project;
	meta: ProjectMeta | null;
	latestProjects: Projects;
	locale?: 'en' | 'pt';
};

const ACCORDION_TITLES = {
	en: ['CONTEXT', 'CREATIVE DIRECTION', 'SOLUTION', 'IMPACT'],
	pt: ['CONTEXTO', 'DIREÇÃO CRIATIVA', 'SOLUÇÃO', 'IMPACTO'],
} as const;

function mediaUrl(value?: { url?: string | false } | string | false | null) {
	if (!value) return undefined;
	if (typeof value === 'string') return value || undefined;
	const url = value.url;
	return typeof url === 'string' && url ? url : undefined;
}

function structuredAccordion(structured: ProjectStructuredData | null | undefined, locale: 'en' | 'pt') {
	if (!structured?.accordion?.length) return null;

	const fallbackTitles = ACCORDION_TITLES[locale];

	return structured.accordion
		.map((section, i) => ({
			title:
				section.title?.trim() ||
				fallbackTitles[section.index - 1] ||
				fallbackTitles[i] ||
				fallbackTitles[0],
			body: section.body,
		}))
		.filter((section) => section.body.trim());
}

export default function ProjectPage({ project, meta, latestProjects, locale = 'en' }: ProjectPageProps) {
	const projectsHref = withLocalePrefix(pagePath(PROJECTS_PAGE_SLUG), locale);
	const siteUi = useSiteUiLocale(locale);
	const cta = siteUi.labels.seeMoreProjects;

	const structured = project.project_data ?? meta?.project_data ?? null;

	const heroImage =
		mediaUrl(structured?.heroImage) || mediaUrl(meta?.img_single) || project.image_full;
	const logoImage =
		mediaUrl(structured?.logoImage) || mediaUrl(meta?.img_primary) || mediaUrl(meta?.img_secondary);

	const summary = project.more?.trim() || '';
	const contentHtml = project.content || '';

	const { accordionSections, galleryImages } = useMemo(() => {
		const fromStructured = structuredAccordion(structured, locale);
		const images =
			structured?.gallery && structured.gallery.length > 0
				? structured.gallery
				: extractImagesFromHtml(contentHtml);

		return {
			accordionSections: fromStructured?.length
				? fromStructured
				: parseAccordionSections(contentHtml, locale),
			galleryImages: images,
		};
	}, [contentHtml, locale, structured]);

	const filteredLatest = latestProjects.filter((item) => item.slug !== project.slug);
	const showLogo = Boolean(logoImage && logoImage !== heroImage);
	const showVignette = structured?.showVignette !== false;

	return (
		<article className="project-page">
			<ProjectHero
				image={heroImage}
				logo={showLogo ? logoImage : undefined}
				title={project.title ?? project.slug}
				showVignette={showVignette}
			/>

			<div className="project-page-content space-y-10 py-8 md:space-y-16 md:py-14">
				{(summary || accordionSections.length > 0) && (
					<section className="project-intro grid gap-8 md:grid-cols-2 md:items-start md:gap-16 lg:gap-20">
						{summary ? (
							<div
								className="project-summary intro-headline min-w-0 font-hk text-[clamp(1.35rem,4.5vw,2.5rem)] font-medium leading-[1.08] tracking-[-0.02em]"
								dangerouslySetInnerHTML={{ __html: summary }}
							/>
						) : (
							<div className="hidden md:block" />
						)}

						<ProjectAccordion sections={accordionSections} />
					</section>
				)}

				<ProjectGallery images={galleryImages} title={project.title ?? project.slug} locale={locale} />

				<div className="flex justify-center">
					<Link
						href={projectsHref}
						className="selected-projects-cta selected-projects-cta--full-mobile font-hk"
					>
						{cta}
					</Link>
				</div>
			</div>

			<LatestProjects projects={filteredLatest} locale={locale} />
		</article>
	);
}
