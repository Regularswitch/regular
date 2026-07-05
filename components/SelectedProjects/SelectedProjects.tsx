import Image from 'next/image';
import Link from 'next/link';

import type { Category, Project, Projects } from '../../types';
import { sortProjectsByDate } from '../../lib/sortProjects';

/** Categoria WordPress "Projetos selecionados" / home. */
export const SELECTED_PROJECTS_CATEGORY_ID = 17;

const GRID_SPANS = ['half', 'half', 'full', 'half', 'half'] as const;
const MAX_PROJECTS = GRID_SPANS.length;

type SelectedProjectsProps = {
	projects: Projects;
	categories: Category[];
	locale?: 'en' | 'pt';
};

function getCategoryName(categories: Category[], id: number) {
	return categories.find((c) => c.id === id)?.title ?? '';
}

export default function SelectedProjects({ projects, categories, locale = 'en' }: SelectedProjectsProps) {
	const selected = sortProjectsByDate(projects)
		.filter((p) => (p.category ?? []).includes(SELECTED_PROJECTS_CATEGORY_ID))
		.slice(0, MAX_PROJECTS);

	if (!selected.length) return null;

	const prefix = locale === 'pt' ? '/PT' : '';
	const workHref = `${prefix}/work`.replace(/^\/\//, '/') || '/work';
	const title = locale === 'pt' ? 'Projetos Selecionados' : 'Selected Projects';
	const cta = locale === 'pt' ? 'Veja mais projetos' : 'See more projects';

	return (
		<section className="selected-projects py-12 md:py-20" aria-label={title}>
			<div className="mb-8 px-7 md:mb-12">
				<h2 className="text-base font-medium text-(--fg) md:text-lg">
					{title} <span aria-hidden>↘</span>
				</h2>
			</div>

			<div className="selected-projects-grid px-7">
				{selected.map((project, index) => (
					<SelectedProjectCard
						key={project.id}
						project={project}
						categories={categories}
						span={GRID_SPANS[index] ?? 'half'}
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

type SelectedProjectCardProps = {
	project: Project;
	categories: Category[];
	span: (typeof GRID_SPANS)[number];
	href: string;
};

function SelectedProjectCard({ project, categories, span, href }: SelectedProjectCardProps) {
	const tags = (project.category ?? [])
		.map((id) => getCategoryName(categories, id))
		.filter(Boolean);

	return (
		<article className={`selected-projects-item${span === 'full' ? ' selected-projects-item--full' : ''}`}>
			<Link href={href} className="group block">
				<div className="selected-projects-card-image relative overflow-hidden bg-(--surface)">
					{project.image_full ? (
						<Image
							src={project.image_full}
							alt={project.title ?? project.slug}
							width={1200}
							height={span === 'full' ? 600 : 800}
							sizes={
								span === 'full'
									? '(max-width: 768px) 100vw, 90vw'
									: '(max-width: 768px) 100vw, 45vw'
							}
							className="h-full w-full object-cover transition-transform duration-500 group-hover:scale-[1.03]"
						/>
					) : null}
				</div>

				<h3 className="selected-projects-card-title mt-4 font-hk text-lg font-extrabold uppercase tracking-tight text-(--fg) md:mt-5 md:text-xl">
					{project.title}
				</h3>

				{tags.length > 0 ? (
					<ul className="selected-projects-tags mt-3 flex flex-wrap gap-2 md:mt-4">
						{tags.map((tag) => (
							<li key={tag}>
								<span className="selected-projects-tag">{tag}</span>
							</li>
						))}
					</ul>
				) : null}
			</Link>
		</article>
	);
}
