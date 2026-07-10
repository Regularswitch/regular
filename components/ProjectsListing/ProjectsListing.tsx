'use client';

import { useMemo } from 'react';

import { sortProjectsByDate } from '../../lib/sortProjects';
import type { Category, Intro, Projects } from '../../types';
import LatestProjects from '../LatestProjects/LatestProjects';
import { INITIAL_PROJECTS_COUNT } from './constants';
import ProjectsGridSection from './ProjectsGridSection';
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

			<ProjectsGridSection
				projects={sorted}
				categories={categories}
				locale={locale}
				initialCount={INITIAL_PROJECTS_COUNT}
				hrefForSlug={(slug) => projectHref(slug, locale)}
			/>

			<LatestProjects projects={sorted} locale={locale} />

			<div className="h-10" />
		</>
	);
}
