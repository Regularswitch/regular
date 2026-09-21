import type { MetadataRoute } from 'next';

import { getBaseUrl } from '../lib/config/getBaseUrl';
import { absoluteUrl } from '../lib/seo/localePaths';

export default function robots(): MetadataRoute.Robots {
	const base = getBaseUrl();

	return {
		rules: {
			userAgent: '*',
			allow: '/',
		},
		sitemap: absoluteUrl(base, '/sitemap.xml'),
		host: base,
	};
}