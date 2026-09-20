import { notFound } from 'next/navigation';
import type { Metadata } from 'next';

import CategoryArchivePage from '../../../components/CategoryArchive/CategoryArchivePage';
import { fetchCategoryArchivePage } from '../../../lib/fetch/categoryArchive';
import { HOME_PROJECTS_CATEGORY_SLUG } from '../../../lib/projects/categories';
import { buildPageMetadata, DEFAULT_SITE_NAME } from '../../../lib/seo/metadata';

export const revalidate = 60;
export const dynamicParams = true;

type PageProps = {
	params: Promise<{ slug: string }>;
};

export async function generateMetadata({ params }: PageProps): Promise<Metadata> {
	const { slug } = await params;
	if (!slug || slug === HOME_PROJECTS_CATEGORY_SLUG) {
		return buildPageMetadata({}, { fallbackTitle: DEFAULT_SITE_NAME, locale: 'en' });
	}

	const data = await fetchCategoryArchivePage(slug, 'en').catch(() => null);
	if (!data) {
		return buildPageMetadata({}, { fallbackTitle: DEFAULT_SITE_NAME, locale: 'en' });
	}

	const { category } = data;
	const title =
		category.seoTitle?.trim() ||
		`${category.h1?.trim() || category.title} | ${DEFAULT_SITE_NAME}`;

	return buildPageMetadata(
		{
			title: category.seoTitle,
			description: category.seoDescription,
		},
		{
			fallbackTitle: title,
			fallbackDescription: category.intro?.replace(/<[^>]+>/g, ' ').trim(),
			locale: 'en',
			path: `/category/${slug}`,
		},
	);
}

export default async function CategorySlugPage({ params }: PageProps) {
	const { slug } = await params;
	if (!slug || slug === HOME_PROJECTS_CATEGORY_SLUG) notFound();

	const data = await fetchCategoryArchivePage(slug, 'en').catch((error) => {
		console.error('Error fetching category archive', error);
		return null;
	});

	if (!data) notFound();

	return (
		<CategoryArchivePage
			category={data.category}
			projects={data.projects}
			categories={data.categories}
			locale="en"
			initialCount={data.initialCount}
		/>
	);
}
