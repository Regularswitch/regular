'use client';

import Image from 'next/image';

import type { EducationInstitution } from '../../lib/content/education/defaults';
import { wpMediaUrl } from '../../lib/wp/mediaUrl';
import EducationGalleryBlock from './EducationGalleryBlock';

type EducationInstitutionBlockProps = {
	institution: EducationInstitution;
};

export default function EducationInstitutionBlock({ institution }: EducationInstitutionBlockProps) {
	const logoSrc = institution.logo ? (wpMediaUrl(institution.logo) ?? institution.logo) : undefined;
	const hasHeader = Boolean(institution.name || logoSrc);
	const hasContent =
		hasHeader ||
		institution.topGallery ||
		institution.midGallery ||
		institution.bottomGallery ||
		institution.description;

	if (!hasContent) return null;

	return (
		<section className="education-institution space-y-8 md:space-y-10">
			{institution.topGallery ? <EducationGalleryBlock gallery={institution.topGallery} /> : null}

			{hasHeader ? (
				<header className="education-institution-header flex flex-wrap items-center gap-4 md:gap-5">
					{logoSrc ? (
						<div className="education-institution-logo relative h-12 w-12 shrink-0 overflow-hidden md:h-14 md:w-14">
							<Image
								src={logoSrc}
								alt=""
								fill
								sizes="56px"
								className="object-contain object-left"
							/>
						</div>
					) : null}
					{institution.name ? (
						<h2 className="font-hk text-lg font-medium leading-snug text-(--fg) md:text-xl">
							{institution.name}
						</h2>
					) : null}
				</header>
			) : null}

			{institution.midGallery ? <EducationGalleryBlock gallery={institution.midGallery} /> : null}

			{institution.description ? (
				<div
					className="education-institution-description max-w-3xl font-hk text-sm leading-relaxed text-(--muted) md:text-base"
					dangerouslySetInnerHTML={{ __html: institution.description }}
				/>
			) : null}

			{institution.bottomGallery ? (
				<EducationGalleryBlock gallery={institution.bottomGallery} />
			) : null}
		</section>
	);
}
