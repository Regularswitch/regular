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
	/** Galeria principal após o header (mid; top legado vira fallback). */
	const primaryGallery = institution.midGallery ?? institution.topGallery;
	const hasContent =
		hasHeader || primaryGallery || institution.bottomGallery || institution.description;

	if (!hasContent) return null;

	return (
		<section className="education-institution space-y-6 md:space-y-8">
			{hasHeader ? (
				<header className="education-institution-header flex items-center gap-4 md:gap-5">
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
						<h2 className="education-institution-name max-w-[16rem] font-hk text-lg font-medium leading-snug text-(--fg) md:max-w-[18rem] md:text-xl">
							{institution.name}
						</h2>
					) : null}
				</header>
			) : null}

			{primaryGallery ? <EducationGalleryBlock gallery={primaryGallery} /> : null}

			{institution.description ? (
				<div
					className="education-institution-description font-hk text-sm leading-relaxed text-(--fg) md:text-base md:leading-[1.65]"
					dangerouslySetInnerHTML={{ __html: institution.description }}
				/>
			) : null}

			{institution.bottomGallery ? (
				<EducationGalleryBlock gallery={institution.bottomGallery} />
			) : null}
		</section>
	);
}
