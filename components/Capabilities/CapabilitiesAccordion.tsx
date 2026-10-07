'use client';

import Image from 'next/image';
import Link from 'next/link';
import { useMemo, useState } from 'react';

import BezierDivider from '../BezierDivider/BezierDivider';
import { AccordionPlusIcon } from '../SiteIcons';
import type { CapabilitySection } from '../../lib/content/capabilities/defaults';
import { categoryArchivePath } from '../../lib/projects/categories';
import { projectImageAltFallback } from '../../lib/projects/altText';
import { withLocalePrefix } from '../../lib/site/resolveSiteUi';
import { wpMediaUrl } from '../../lib/wp/mediaUrl';

type CapabilitiesAccordionProps = {
	sections: CapabilitySection[];
	defaultOpenIndex?: number;
	locale?: 'en' | 'pt';
};

function sectionImageUrl(section?: CapabilitySection): string | undefined {
	if (!section?.image) return undefined;
	return wpMediaUrl(section.image) ?? section.image;
}

export default function CapabilitiesAccordion({
	sections,
	defaultOpenIndex = -1,
	locale = 'en',
}: CapabilitiesAccordionProps) {
	const visibleSections = useMemo(
		() => sections.filter((section) => section.title?.trim()),
		[sections],
	);

	const initialIndex =
		defaultOpenIndex >= 0 && defaultOpenIndex < visibleSections.length ? defaultOpenIndex : -1;

	const [openIndex, setOpenIndex] = useState(initialIndex);
	const [pinnedImage, setPinnedImage] = useState<string | undefined>(() =>
		sectionImageUrl(visibleSections[initialIndex >= 0 ? initialIndex : 0]),
	);
	const [pinnedSlug, setPinnedSlug] = useState<string | undefined>(
		() => visibleSections[initialIndex >= 0 ? initialIndex : 0]?.imageProjectSlug,
	);

	const activeSection = openIndex >= 0 ? visibleSections[openIndex] : undefined;
	const activeImage = sectionImageUrl(activeSection) ?? pinnedImage;
	const activeSlug = activeSection?.imageProjectSlug ?? pinnedSlug;
	const imageAlt = projectImageAltFallback(
		activeSlug?.replace(/-/g, ' ') || activeSection?.title.replace(/<[^>]+>/g, '') || 'Capabilities',
		locale,
	);
	const projectHref = activeSlug ? withLocalePrefix(`/project/${activeSlug}`, locale) : null;

	if (!visibleSections.length) return null;

	const seeProjectLabel = locale === 'pt' ? 'Ver projeto' : 'See project';
	const seeCategoryLabel = locale === 'pt' ? 'Ver projetos relacionados' : 'See related projects';

	const imageEl = activeImage ? (
		<div className="capabilities-side-image relative aspect-square min-w-0 overflow-hidden rounded-[5px] md:sticky md:top-28">
			<Image
				key={activeImage}
				src={activeImage}
				alt={imageAlt}
				fill
				sizes="(max-width: 768px) 100vw, 45vw"
				className="object-cover object-center transition-opacity duration-300"
			/>
		</div>
	) : (
		<div
			className="capabilities-side-image relative aspect-square min-w-0 overflow-hidden rounded-[5px] md:sticky md:top-28"
			aria-hidden
		/>
	);

	return (
		<section className="capabilities-accordion-section md:grid md:grid-cols-2 md:items-start md:gap-12 lg:gap-16">
			<div className="mb-10 min-w-0 md:mb-0">
				{imageEl && projectHref ? (
					<Link href={projectHref} className="block">
						{imageEl}
					</Link>
				) : (
					imageEl
				)}
			</div>

			<div className="capabilities-accordion min-w-0 md:col-start-2">
				<BezierDivider />
				{visibleSections.map((section, index) => {
					const isOpen = openIndex === index;
					const categoryHref = section.relatedCategorySlug
						? categoryArchivePath(section.relatedCategorySlug, locale)
						: null;
					const sectionProjectHref = section.imageProjectSlug
						? withLocalePrefix(`/project/${section.imageProjectSlug}`, locale)
						: null;

					return (
						<div key={section.title}>
							<button
								type="button"
								className="accordion-trigger flex w-full items-center justify-between gap-4 py-5 text-left"
								onClick={() => {
									if (isOpen) {
										setOpenIndex(-1);
										return;
									}
									const nextImage = sectionImageUrl(section);
									if (nextImage) setPinnedImage(nextImage);
									if (section.imageProjectSlug) setPinnedSlug(section.imageProjectSlug);
									setOpenIndex(index);
								}}
								aria-expanded={isOpen}
							>
								<span
									className={`accordion-trigger-title font-hk normal-case${isOpen ? ' is-open' : ''}`}
									dangerouslySetInnerHTML={{ __html: section.title }}
								/>
								<span
									className={`accordion-trigger-icon text-lg leading-none${isOpen ? ' is-open text-(--fg)' : ' text-(--muted)'}`}
									aria-hidden
								>
									<AccordionPlusIcon />
								</span>
							</button>

							<div className={`accordion-panel${isOpen ? ' is-open' : ''}`} aria-hidden={!isOpen}>
								<div className="accordion-panel-inner">
									<div className="accordion-panel-content capabilities-accordion-panel pb-8 pt-2">
										<div className="capabilities-accordion-content font-hk">
											{section.lead ? (
												<p className="text-lg leading-snug text-(--fg) md:text-xl md:leading-tight">
													{section.lead}
												</p>
											) : null}

											{section.body ? (
												<div
													className="capabilities-accordion-body mt-5 text-(--muted) md:mt-6"
													dangerouslySetInnerHTML={{ __html: section.body }}
												/>
											) : null}

											{section.services && section.services.length > 0 ? (
												<div className="mt-6 md:mt-8">
													{section.servicesTitle ? (
														<p className="text-sm font-medium text-(--fg) md:text-base">
															{section.servicesTitle}
														</p>
													) : null}
													<ul className="capabilities-accordion-list mt-3 space-y-1.5 text-(--muted)">
														{section.services.map((service) => (
															<li key={service}>{service}</li>
														))}
													</ul>
												</div>
											) : null}

											{(sectionProjectHref || categoryHref) && (
												<div className="mt-6 flex flex-wrap gap-x-5 gap-y-2 md:mt-8">
													{sectionProjectHref ? (
														<Link
															href={sectionProjectHref}
															className="font-hk text-sm text-(--fg) underline underline-offset-4 md:text-base"
														>
															{seeProjectLabel}
														</Link>
													) : null}
													{categoryHref ? (
														<Link
															href={categoryHref}
															className="font-hk text-sm text-(--fg) underline underline-offset-4 md:text-base"
														>
															{seeCategoryLabel}
														</Link>
													) : null}
												</div>
											)}
										</div>
									</div>
								</div>
							</div>
							<BezierDivider />
						</div>
					);
				})}
			</div>
		</section>
	);
}
