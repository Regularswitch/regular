'use client';

import Link from 'next/link';

import { pagePath, PROJECTS_PAGE_SLUG } from '../../lib/site/pageSlugs';
import { isHomeProject } from '../../lib/projects/categories';
import { homeFeaturedSlotIndex, orderHomeProjects } from '../../lib/projects/featured';
import { withLocalePrefix } from '../../lib/site/resolveSiteUi';
import { sortProjectsByDate } from '../../lib/projects/sort';
import type { Category, Projects, SiteUiLabels } from '../../types';
import { getHomeGridSpan } from '../ProjectsListing/constants';
import ProjectGridCard from '../ProjectsListing/ProjectGridCard';
import { SectionHeadingArrow } from '../SiteIcons';
import { useSiteUiLayout } from '../SiteUi/SiteUiProvider';

type SelectedProjectsProps = {
	projects: Projects;
	categories: Category[];
	locale?: 'en' | 'pt';
	labels?: Pick<SiteUiLabels, 'selectedProjects' | 'seeMoreProjects'>;
};

export default function SelectedProjects({ projects, categories, locale = 'en', labels }: SelectedProjectsProps) {
	const layout = useSiteUiLayout();
	const maxProjects = Math.max(layout.projectsInitialCount, layout.homeColumns === 3 ? 6 : 5);

	const selected = orderHomeProjects(
		sortProjectsByDate(projects)
			.filter((p) => isHomeProject(p, categories))
			.slice(0, maxProjects),
	);

	if (!selected.length) return null;

	const featuredIndex =
		layout.homeColumns === 1 ? 0 : homeFeaturedSlotIndex(selected.length);
	const projectsHref = withLocalePrefix(pagePath(PROJECTS_PAGE_SLUG), locale);
	const title = labels?.selectedProjects ?? (locale === 'pt' ? 'Projetos Selecionados' : 'Selected Projects');
	const cta = labels?.seeMoreProjects ?? (locale === 'pt' ? 'Veja mais projetos' : 'See more projects');
	const gridClass =
		layout.homeColumns === 1
			? 'selected-projects-grid selected-projects-grid--cols-1'
			: layout.homeColumns === 3
				? 'selected-projects-grid selected-projects-grid--cols-3'
				: 'selected-projects-grid';

	return (
		<section className="selected-projects py-6 md:py-10" aria-label={title}>
			<div className="mb-8 md:mb-12">
				<h2 className="inline-flex items-center gap-1.5 text-base font-medium text-(--fg) md:text-lg">
					{title}
					<SectionHeadingArrow />
				</h2>
			</div>

			<div className={gridClass}>
				{selected.map((project, index) => (
					<ProjectGridCard
						key={project.id}
						project={project}
						categories={categories}
						span={getHomeGridSpan(index, featuredIndex, layout.homeColumns)}
						href={withLocalePrefix(`/project/${project.slug}`, locale)}
					/>
				))}
			</div>

			<div className="mt-12 flex justify-center md:mt-16">
				<Link href={projectsHref} className="selected-projects-cta font-hk">
					{cta}
				</Link>
			</div>
		</section>
	);
}
