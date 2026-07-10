import '../styles/globals.css';
import type { ReactNode } from 'react';
import { cookies } from 'next/headers';
import Header from '../components/Header';
import FooterComponents from '../components/FooterComponents';
import { SiteUiProvider } from '../components/SiteUi/SiteUiProvider';
import { GetFooterApi, GetHeaderNavApi, GetSiteUiApi } from '../components/ApiWp';
import { buildSiteUiWithHeaderNav } from '../lib/resolveSiteUi';

export const metadata = {
	title: 'Regular Switch',
};

export default async function RootLayout({ children }: { children: ReactNode }) {
	const theme = (await cookies()).get('theme')?.value === 'light' ? 'light' : 'dark';
	const [footerEn, footerPt, siteUi, headerNav] = await Promise.all([
		GetFooterApi({ slug: 'en' }),
		GetFooterApi({ slug: 'pt' }),
		GetSiteUiApi(),
		GetHeaderNavApi(),
	]);

	return (
		<html lang="en" className={theme === 'dark' ? 'dark' : undefined}>
			<head>
				<meta name="color-scheme" content="dark light" />
				<script
					type="application/ld+json"
					dangerouslySetInnerHTML={{
						__html: JSON.stringify({
							'@context': 'http://schema.org',
							'@type': 'Organization',
							name: 'RSW Regular Switch',
							url: 'https://regularswitch.com/',
							contactPoint: [
								{
									'@type': 'ContactPoint',
									telephone: '+55 11 94540-8448',
									contactType: 'customer service',
								},
							],
						}),
					}}
				/>
			</head>
			<body>
				<SiteUiProvider siteUi={buildSiteUiWithHeaderNav(siteUi, headerNav)}>
					<Header />
					<main className="pt-20 px-7 sm:pt-24 lg:pt-28">{children}</main>
					<FooterComponents footerEn={footerEn} footerPt={footerPt} />
				</SiteUiProvider>
			</body>
		</html>
	);
}
