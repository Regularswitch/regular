'use client';

import { useMemo, useState } from 'react';

import { sortProjectsByDate } from '../../lib/sortProjects';
import type { Category, Intro, Projects } from '../../types';
import LatestProjects from '../LatestProjects/LatestProjects';
import { getGridSpan, INITIAL_PROJECTS_COUNT, PROJECTS_BATCH_SIZE } from './constants';
import ProjectGridCard from './ProjectGridCard';
import ProjectsListingHero from './ProjectsListingHero';

type ProjectsListingProps = {
	projects: Projects;
	categories: Category[];
	intro: Intro | null;
	locale?: 'en' | 'pt';
};

function projectHref(slug: string, locale: 'en' | 'pt') {
	const prefix = locale === 'pt' ? '/PT' : '';
	return `${prefix}/project/${slug}`.replace(/^\/\//, '/') || `/project/${slug}`;
}

export default function ProjectsListing({ projects, categories, intro, locale = 'en' }: ProjectsListingProps) {
	const sorted = useMemo(() => sortProjectsByDate(projects), [projects]);
	const [visibleCount, setVisibleCount] = useState<number>(INITIAL_PROJECTS_COUNT);

	const visible = sorted.slice(0, visibleCount);
	const hasMore = visibleCount < sorted.length;
	const cta = locale === 'pt' ? 'Veja mais projetos' : 'See more projects';

	if (!sorted.length) {
		return (
			<>
				<ProjectsListingHero intro={intro} locale={locale} />
				<p className="px-7 py-12 text-(--muted)">
					{locale === 'pt' ? 'Nenhum projeto encontrado.' : 'No projects found.'}
				</p>
			</>
		);
	}

	return (
		<>
			<ProjectsListingHero intro={intro} locale={locale} />

			<section className="selected-projects pb-12 pt-10 md:pb-20 md:pt-14" aria-label={locale === 'pt' ? 'Projetos' : 'Projects'}>
				<div className="selected-projects-grid px-7">
					{visible.map((project, index) => (
						<ProjectGridCard
							key={project.id}
							project={project}
							categories={categories}
							span={getGridSpan(index)}
							href={projectHref(project.slug, locale)}
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
							{cta}
						</button>
					</div>
				) : null}
			</section>

			<LatestProjects projects={sorted} locale={locale} />

			<div className="h-10" />
		</>
	);
}
