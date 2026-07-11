import { wpMediaUrl } from './wpMediaUrl';
import type { Project, ProjectStructuredData, ProjectStructuredImage } from '../types';

export function structuredImageUrl(image?: ProjectStructuredImage | null): string | undefined {
	const url = image?.url;
	if (!url || typeof url !== 'string' || !url.trim()) return undefined;
	return wpMediaUrl(url) ?? url;
}

export function normalizeProjectData(
	data?: ProjectStructuredData | null,
): ProjectStructuredData | null {
	if (!data) return null;

	return {
		...data,
		heroImage: data.heroImage?.url
			? { ...data.heroImage, url: structuredImageUrl(data.heroImage) ?? data.heroImage.url }
			: data.heroImage,
		logoImage: data.logoImage?.url
			? { ...data.logoImage, url: structuredImageUrl(data.logoImage) ?? data.logoImage.url }
			: data.logoImage,
		gallery: data.gallery?.map((url) => wpMediaUrl(url) ?? url),
	};
}

/** Imagem de fundo do projeto (hero), com fallback para galeria e imagem destacada. */
export function getProjectHeroImage(
	project: Pick<Project, 'image_full' | 'project_data'>,
): string | undefined {
	const hero = structuredImageUrl(project.project_data?.heroImage);
	if (hero) return hero;

	const gallery = project.project_data?.gallery;
	if (gallery?.length) {
		const first = gallery[0];
		if (first) return wpMediaUrl(first) ?? first;
	}

	if (project.image_full) return wpMediaUrl(project.image_full) ?? project.image_full;
	return undefined;
}
