'use client';

import Image from 'next/image';
import Link from 'next/link';

import { sortProjectsByDate } from '../../lib/sortProjects';
import { getProjectHeroImage } from '../../lib/projectImages';
import type { Project, Projects } from '../../types';
import { SectionHeadingArrow } from '../SiteIcons';
import { useSiteUiLayout, useSiteUiLocale } from '../SiteUi/SiteUiProvider';

type LatestProjectsProps = {
	projects: Projects;
	locale?: 'en' | 'pt';
};

function projectHref(slug: string, locale: 'en' | 'pt') {
	const prefix = locale === 'pt' ? '/PT' : '';
	return `${prefix}/project/${slug}`.replace(/^\/\//, '/') || `/project/${slug}`;
}

export default function LatestProjects({ projects, locale = 'en' }: LatestProjectsProps) {
	const siteUi = useSiteUiLocale(locale);
	const layout = useSiteUiLayout();
	const latest = sortProjectsByDate(projects).slice(0, layout.latestCount);

	if (!latest.length) return null;

	const title = siteUi.labels.latestProjects;

	return (
		<section className="latest-projects py-6 md:py-10" aria-label={title}>
			<div className="mb-8 md:mb-10">
				<h2 className="inline-flex items-center gap-1.5 text-base font-medium text-(--fg) md:text-lg">
					{title}
					<SectionHeadingArrow />
				</h2>
			</div>

			<div className={`latest-projects-grid latest-projects-grid--${layout.latestCount}`}>
				{latest.map((project) => (
					<LatestProjectCard
						key={project.id}
						project={project}
						href={projectHref(project.slug, locale)}
					/>
				))}
			</div>
		</section>
	);
}

function LatestProjectCard({ project, href }: { project: Project; href: string }) {
	const cardImage = getProjectHeroImage(project);

	return (
		<Link href={href} className="latest-projects-card group block">
			<div className="latest-projects-card-image relative overflow-hidden bg-(--surface)">
				{cardImage ? (
					<Image
						src={cardImage}
						alt={project.title ?? project.slug}
						width={640}
						height={480}
						sizes="(max-width: 768px) 100vw, 25vw"
						className="h-full w-full object-cover transition-transform duration-500 group-hover:scale-[1.03]"
					/>
				) : null}
			</div>
		</Link>
	);
}
