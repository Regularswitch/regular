'use client';

import Image from 'next/image';
import Link from 'next/link';
import { useState } from 'react';

import { getVisibleCategoryTags } from '../../lib/projects/categories';
import { getProjectHeroImage, isGifUrl } from '../../lib/projects/images';
import type { Category, Project } from '../../types';
import type { GridSpan } from './constants';

type ProjectGridCardProps = {
	project: Project;
	categories: Category[];
	span: GridSpan;
	href: string;
};

export default function ProjectGridCard({ project, categories, span, href }: ProjectGridCardProps) {
	const tags = getVisibleCategoryTags(project.category ?? [], categories);
	const cardImage = getProjectHeroImage(project);
	const [imageLoaded, setImageLoaded] = useState(false);
	const isFeatured = span === 'featured' || span === 'full';
	const isThird = span === 'third';
	const isQuarter = span === 'quarter';

	const itemClass = [
		'selected-projects-item',
		isFeatured ? 'selected-projects-item--featured' : '',
		span === 'full' ? 'selected-projects-item--full' : '',
		isThird ? 'selected-projects-item--third' : '',
		isQuarter ? 'selected-projects-item--quarter' : '',
		span === 'half' ? 'selected-projects-item--half' : '',
	]
		.filter(Boolean)
		.join(' ');

	return (
		<article className={itemClass}>
			<Link href={href} className="group block">
				<div
					className={`selected-projects-card-image relative overflow-hidden${
						!cardImage || imageLoaded ? ' is-loaded' : ' is-loading'
					}`}
				>
					<span className="selected-projects-card-shimmer" aria-hidden />
					{cardImage ? (
						<Image
							src={cardImage}
							alt={project.title ?? project.slug}
							sizes={
								isFeatured
									? '(max-width: 768px) 100vw, 90vw'
									: isThird
										? '(max-width: 768px) 100vw, 33vw'
										: isQuarter
											? '(max-width: 768px) 100vw, 25vw'
											: '(max-width: 768px) 100vw, 50vw'
							}
							width={isFeatured ? 1600 : isQuarter ? 800 : 1200}
							height={isFeatured ? 600 : isQuarter ? 800 : 900}
							unoptimized={isGifUrl(cardImage)}
							onLoadingComplete={() => setImageLoaded(true)}
							className={`selected-projects-card-media h-full w-full object-cover transition-transform duration-500 group-hover:scale-[1.03]${
								imageLoaded ? ' is-visible' : ''
							}`}
						/>
					) : (
						<span className="selected-projects-card-empty" aria-hidden />
					)}
				</div>

				<h3 className="selected-projects-card-title mt-4 font-hk text-lg font-medium uppercase tracking-tight text-(--fg) md:mt-5 md:text-xl">
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
