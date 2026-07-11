import Link from 'next/link';

import { pagePath, PROJECTS_PAGE_SLUG } from '../../lib/pageSlugs';
import { isHomeProject } from '../../lib/projectCategories';
import { withLocalePrefix } from '../../lib/resolveSiteUi';
import { sortProjectsByDate } from '../../lib/sortProjects';
import type { Category, Projects, SiteUiLabels } from '../../types';
import { getGridSpan, INITIAL_PROJECTS_COUNT } from '../ProjectsListing/constants';
import ProjectGridCard from '../ProjectsListing/ProjectGridCard';
import { SectionHeadingArrow } from '../SiteIcons';

const MAX_PROJECTS = INITIAL_PROJECTS_COUNT;

type SelectedProjectsProps = {
	projects: Projects;
	categories: Category[];
	locale?: 'en' | 'pt';
	labels?: Pick<SiteUiLabels, 'selectedProjects' | 'seeMoreProjects'>;
};

export default function SelectedProjects({ projects, categories, locale = 'en', labels }: SelectedProjectsProps) {
	const selected = sortProjectsByDate(projects)
		.filter((p) => isHomeProject(p, categories))
		.slice(0, MAX_PROJECTS);

	if (!selected.length) return null;

	const projectsHref = withLocalePrefix(pagePath(PROJECTS_PAGE_SLUG), locale);
	const title = labels?.selectedProjects ?? (locale === 'pt' ? 'Projetos Selecionados' : 'Selected Projects');
	const cta = labels?.seeMoreProjects ?? (locale === 'pt' ? 'Veja mais projetos' : 'See more projects');

	return (
		<section className="selected-projects py-6 md:py-10" aria-label={title}>
			<div className="mb-8 md:mb-12">
				<h2 className="inline-flex items-center gap-1.5 text-base font-medium text-(--fg) md:text-lg">
					{title}
					<SectionHeadingArrow />
				</h2>
			</div>

			<div className="selected-projects-grid">
				{selected.map((project, index) => (
					<ProjectGridCard
						key={project.id}
						project={project}
						categories={categories}
						span={getGridSpan(index)}
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
