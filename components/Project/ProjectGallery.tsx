import Image from 'next/image';

import type { ProjectGalleryRow } from '../../lib/parseProjectContent';
import { wpMediaUrl } from '../../lib/wpMediaUrl';

type ProjectGalleryProps = {
	rows: ProjectGalleryRow[];
	title: string;
};

export default function ProjectGallery({ rows, title }: ProjectGalleryProps) {
	if (!rows.length) return null;

	return (
		<section className="project-gallery space-y-4 md:space-y-5" aria-label="Galeria do projeto">
			{rows.map((row, rowIndex) => (
				<div
					key={`row-${rowIndex}`}
					className={`project-gallery-row project-gallery-row--${row.columns}`}
				>
					{row.images.map((src, imageIndex) => {
						const resolved = wpMediaUrl(src) ?? src;

						return (
							<div key={`${src}-${imageIndex}`} className="project-gallery-item overflow-hidden rounded-2xl bg-(--surface)">
								<Image
									src={resolved}
									alt={`${title} — imagem ${rowIndex + 1}.${imageIndex + 1}`}
									width={1200}
									height={900}
									sizes={row.columns === 3 ? '(max-width: 768px) 100vw, 33vw' : '(max-width: 768px) 100vw, 50vw'}
									className="h-full w-full object-cover"
								/>
							</div>
						);
					})}
				</div>
			))}
		</section>
	);
}
