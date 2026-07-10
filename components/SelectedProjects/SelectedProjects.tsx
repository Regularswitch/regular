import Link from 'next/link';

import type { Category, Projects, SiteUiLabels } from '../../types';
import { isHomeProject } from '../../lib/projectCategories';
import { sortProjectsByDate } from '../../lib/sortProjects';
import { getGridSpan, INITIAL_PROJECTS_COUNT } from '../ProjectsListing/constants';
import ProjectGridCard from '../ProjectsListing/ProjectGridCard';

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

	const prefix = locale === 'pt' ? '/PT' : '';
	const workHref = `${prefix}/work`.replace(/^\/\//, '/') || '/work';
	const title = labels?.selectedProjects ?? (locale === 'pt' ? 'Projetos Selecionados' : 'Selected Projects');
	const cta = labels?.seeMoreProjects ?? (locale === 'pt' ? 'Veja mais projetos' : 'See more projects');

	return (
		<section className="selected-projects py-12 md:py-20" aria-label={title}>
			<div className="mb-8 px-7 md:mb-12">
				<h2 className="text-base font-medium text-(--fg) md:text-lg">
					{title} <span aria-hidden>↘</span>
				</h2>
			</div>

			<div className="selected-projects-grid px-7">
				{selected.map((project, index) => (
					<ProjectGridCard
						key={project.id}
						project={project}
						categories={categories}
						span={getGridSpan(index)}
						href={`${prefix}/project/${project.slug}`.replace(/^\/\//, '/') || `/project/${project.slug}`}
					/>
				))}
			</div>

			<div className="mt-12 flex justify-center px-7 md:mt-16">
				<Link href={workHref} className="selected-projects-cta font-hk">
					{cta}
				</Link>
			</div>
		</section>
	);
}
