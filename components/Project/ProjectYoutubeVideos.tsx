'use client';

import { useMemo } from 'react';

import { normalizeYoutubeVideos, youtubeEmbedUrl } from '../../lib/projects/youtube';
import type { ProjectStructuredData } from '../../types';

type ProjectYoutubeVideosProps = {
	videos?: ProjectStructuredData['youtubeVideos'] | string[] | null;
	title: string;
	locale?: 'en' | 'pt';
};

export default function ProjectYoutubeVideos({
	videos,
	title,
	locale = 'en',
}: ProjectYoutubeVideosProps) {
	const items = useMemo(() => normalizeYoutubeVideos(videos), [videos]);

	if (!items.length) return null;

	const label = locale === 'pt' ? 'Vídeos do YouTube' : 'YouTube videos';

	return (
		<section className="project-youtube" aria-label={label}>
			<div className="project-gallery-grid">
				{items.map((item, index) => (
					<div key={item.id} className="project-gallery-item project-gallery-item--wide">
						<div className="project-youtube-frame overflow-hidden rounded-[5px] bg-(--surface)">
							<iframe
								src={youtubeEmbedUrl(item.id)}
								title={`${title} — ${label} ${index + 1}`}
								allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
								allowFullScreen
								loading="lazy"
								referrerPolicy="strict-origin-when-cross-origin"
							/>
						</div>
					</div>
				))}
			</div>
		</section>
	);
}
