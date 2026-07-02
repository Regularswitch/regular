import { getBaseUrl } from '../../../../lib/getBaseUrl';
import PtProjectSlugClient from '../../../../components/PtProjectSlugClient';

export const revalidate = 10;
export const dynamicParams = true;

type PageProps = {
	params: Promise<{ slug: string }>;
};

export default async function PtProjectSlugPage({ params }: PageProps) {
	const { slug } = await params;
	const base = getBaseUrl();

	const allPosts = await fetch(`${base}/api/project/${slug}`, { headers: { Cookie: 'language=PT' } })
		.then((r) => r.json())
		.catch((error) => {
			console.error('Error fetching PT project', error);
			return [];
		});

	return <PtProjectSlugClient allPosts={allPosts} lang="PT" />;
}

