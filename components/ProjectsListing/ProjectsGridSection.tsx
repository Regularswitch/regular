'use client';

import { useMemo, useState } from 'react';

import type { Category, Projects } from '../../types';
import { useSiteUiLocale } from '../SiteUi/SiteUiProvider';
import { getGridSpan, PROJECTS_BATCH_SIZE } from './constants';
import ProjectGridCard from './ProjectGridCard';

type ProjectsGridSectionProps = {
	projects: Projects;
	categories: Category[];
	locale?: 'en' | 'pt';
	initialCount: number;
	hrefForSlug: (slug: string) => string;
	cta?: string;
	ariaLabel?: string;
	className?: string;
};

export default function ProjectsGridSection({
	projects,
	categories,
	locale = 'en',
	initialCount,
	hrefForSlug,
	cta,
	ariaLabel,
	className = 'selected-projects pb-12 pt-10 md:pb-20 md:pt-14',
}: ProjectsGridSectionProps) {
	const sorted = useMemo(() => projects, [projects]);
	const [visibleCount, setVisibleCount] = useState<number>(initialCount);

	const visible = sorted.slice(0, visibleCount);
	const hasMore = visibleCount < sorted.length;
	const siteUi = useSiteUiLocale(locale);
	const buttonLabel = cta ?? siteUi.labels.seeMoreProjects;

	if (!sorted.length) return null;

	return (
		<section className={className} aria-label={ariaLabel ?? (locale === 'pt' ? 'Projetos' : 'Projects')}>
			<div className="selected-projects-grid px-7">
				{visible.map((project, index) => (
					<ProjectGridCard
						key={project.id}
						project={project}
						categories={categories}
						span={getGridSpan(index)}
						href={hrefForSlug(project.slug)}
					/>
				))}
			</div>

			{hasMore ? (
				<div className="mt-12 flex justify-center px-7 md:mt-16">
					<button
						type="button"
						onClick={() => setVisibleCount((count) => count + PROJECTS_BATCH_SIZE)}
						className="selected-projects-cta font-hk"
					>
						{buttonLabel}
					</button>
				</div>
			) : null}
		</section>
	);
}
