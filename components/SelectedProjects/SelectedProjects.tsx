'use client';

import Link from 'next/link';

import { CONTACT_PAGE_SLUG, pagePath, PROJECTS_PAGE_SLUG } from '../../lib/site/pageSlugs';
import { isHomeProject } from '../../lib/projects/categories';
import {
	homeProjectLimit,
	isFeaturedOnHome,
	pickHomeProjects,
	resolveFeaturedIndex,
} from '../../lib/projects/featured';
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
	const homePool = sortProjectsByDate(projects).filter((p) => isHomeProject(p, categories));
	const hasFeatured = homePool.some(isFeaturedOnHome);
	/** Exatamente 2 linhas: 4 iguais, ou 1 destaque + 2 (grid 2 col). */
	const maxProjects = homeProjectLimit(layout.homeColumns, hasFeatured);

	const selected = pickHomeProjects(homePool, maxProjects);
	const featuredIndex = resolveFeaturedIndex(selected);

	if (!selected.length) return null;

	const projectsHref = withLocalePrefix(pagePath(PROJECTS_PAGE_SLUG), locale);
	const contactHref = withLocalePrefix(pagePath(CONTACT_PAGE_SLUG), locale);
	const title = labels?.selectedProjects ?? (locale === 'pt' ? 'Projetos Selecionados' : 'Selected Projects');
	const cta = labels?.seeMoreProjects ?? (locale === 'pt' ? 'Veja mais projetos' : 'See more projects');
	const contactCta = locale === 'pt' ? 'Contato' : 'Contact';
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

			<div className="selected-projects-ctas mt-12 md:mt-16">
				<Link href={projectsHref} className="selected-projects-cta font-hk">
					{cta}
				</Link>
				<Link href={contactHref} className="selected-projects-cta font-hk">
					{contactCta}
				</Link>
			</div>
		</section>
	);
}
