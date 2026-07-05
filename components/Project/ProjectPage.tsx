'use client';

import Link from 'next/link';
import { useMemo } from 'react';

import LatestProjects from '../LatestProjects/LatestProjects';
import type { Project, ProjectMeta, Projects, ProjectStructuredData } from '../../types';
import {
	buildGalleryRows,
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

	const titles = ACCORDION_TITLES[locale];

	return structured.accordion
		.map((section) => ({
			title: titles[section.index - 1] ?? titles[0],
			body: section.body,
		}))
		.filter((section) => section.body.trim());
}

export default function ProjectPage({ project, meta, latestProjects, locale = 'en' }: ProjectPageProps) {
	const prefix = locale === 'pt' ? '/PT' : '';
	const workHref = `${prefix}/work`.replace(/^\/\//, '/') || '/work';
	const cta = locale === 'pt' ? 'Veja mais projetos' : 'See more projects';

	const structured = project.project_data ?? meta?.project_data ?? null;

	const heroImage =
		mediaUrl(structured?.heroImage) || mediaUrl(meta?.img_single) || project.image_full;
	const logoImage =
		mediaUrl(structured?.logoImage) || mediaUrl(meta?.img_primary) || mediaUrl(meta?.img_secondary);

	const summary = project.more?.trim() || '';
	const contentHtml = project.content || '';

	const { accordionSections, galleryRows } = useMemo(() => {
		const fromStructured = structuredAccordion(structured, locale);
		const images =
			structured?.gallery && structured.gallery.length > 0
				? structured.gallery
				: extractImagesFromHtml(contentHtml);

		return {
			accordionSections: fromStructured?.length
				? fromStructured
				: parseAccordionSections(contentHtml, locale),
			galleryRows: buildGalleryRows(images),
		};
	}, [contentHtml, locale, structured]);

	const filteredLatest = latestProjects.filter((item) => item.slug !== project.slug);
	const showLogo = Boolean(logoImage && logoImage !== heroImage);

	return (
		<article className="project-page -mt-8 md:-mt-12">
			<ProjectHero
				image={heroImage}
				logo={showLogo ? logoImage : undefined}
				title={project.title ?? project.slug}
			/>

			<div className="project-page-content space-y-16 py-12 md:space-y-20 md:py-16">
				{(summary || accordionSections.length > 0) && (
					<section className="grid gap-10 md:grid-cols-2 md:gap-16">
						{summary ? (
							<div
								className="project-summary font-hk text-xl leading-snug text-(--fg) md:text-[clamp(1.5rem,2.5vw,2.25rem)] md:leading-tight"
								dangerouslySetInnerHTML={{ __html: summary }}
							/>
						) : (
							<div />
						)}

						<ProjectAccordion sections={accordionSections} />
					</section>
				)}

				<ProjectGallery rows={galleryRows} title={project.title ?? project.slug} />

				<div className="flex justify-center">
					<Link href={workHref} className="selected-projects-cta font-hk">
						{cta}
					</Link>
				</div>
			</div>

			<LatestProjects projects={filteredLatest} locale={locale} />
		</article>
	);
}
