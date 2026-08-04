'use client';

import Image from 'next/image';

import type { EducationGallery } from '../../lib/content/education/defaults';
import { wpMediaUrl } from '../../lib/wp/mediaUrl';

type EducationGalleryBlockProps = {
	gallery: EducationGallery;
};

export default function EducationGalleryBlock({ gallery }: EducationGalleryBlockProps) {
	const images = gallery.images
		.map((url) => wpMediaUrl(url) ?? url)
		.filter(Boolean);

	if (!images.length && !gallery.caption) return null;

	return (
		<div className={`education-gallery education-gallery--${gallery.layout}`}>
			{images.length ? (
				<div className="education-gallery-grid">
					{images.map((src, index) => (
						<div key={`${src}-${index}`} className="education-gallery-item">
							<div className="education-gallery-frame relative overflow-hidden rounded-[5px] bg-(--surface)">
								<Image
									src={src}
									alt=""
									fill
									sizes={
										gallery.layout === 'triple'
											? '(max-width: 768px) 100vw, 33vw'
											: gallery.layout === 'grid-2x2'
												? '(max-width: 768px) 50vw, 25vw'
												: '(max-width: 768px) 100vw, 50vw'
									}
									className="object-cover object-center"
								/>
							</div>
						</div>
					))}
				</div>
			) : null}

			{gallery.caption ? (
				<p className="education-gallery-caption mt-3 font-hk text-xs leading-relaxed text-(--muted) md:text-sm">
					{gallery.caption}
				</p>
			) : null}
		</div>
	);
}
