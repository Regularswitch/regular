import Image from 'next/image';
import Link from 'next/link';

import type { Category, Project } from '../../types';
import { getVisibleCategoryTags } from '../../lib/projectCategories';
import type { GridSpan } from './constants';

type ProjectGridCardProps = {
	project: Project;
	categories: Category[];
	span: GridSpan;
	href: string;
};

export default function ProjectGridCard({ project, categories, span, href }: ProjectGridCardProps) {
	const tags = getVisibleCategoryTags(project.category ?? [], categories);

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
