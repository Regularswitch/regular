import { cookies } from 'next/headers';
import type { ReactNode } from 'react';
import { GetFooterApi, GetHeaderNavApi, GetSiteUiApi } from '../components/ApiWp';
import CookieConsent from '../components/CookieConsent/CookieConsent';
import CustomCursor from '../components/CustomCursor/CustomCursor';
import FooterComponents from '../components/FooterComponents';
import Header from '../components/Header';
import { LegalPoliciesProvider } from '../components/Legal/LegalPoliciesProvider';
import { SiteUiProvider } from '../components/SiteUi/SiteUiProvider';
import { buildNavActiveGradient } from '../lib/site/blobDefaults';
import { hankenGrotesk } from '../lib/config/fonts';
import { buildSiteUiWithHeaderNav } from '../lib/site/resolveSiteUi';
import '../styles/globals.css';

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
	const blobNavGradient = buildNavActiveGradient();

	return (
		<html
			lang="en"
			className={`${hankenGrotesk.variable}${theme === 'dark' ? ' dark' : ''}`}
			style={{ ['--blob-nav-gradient' as string]: blobNavGradient }}
		>
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
				<CustomCursor />
				<SiteUiProvider siteUi={buildSiteUiWithHeaderNav(siteUi, headerNav)}>
					<LegalPoliciesProvider footerEn={footerEn} footerPt={footerPt}>
						<Header />
						<main className="pt-16 px-7 sm:pt-14 lg:pt-18">{children}</main>
						<FooterComponents footerEn={footerEn} footerPt={footerPt} />
						<CookieConsent />
					</LegalPoliciesProvider>
				</SiteUiProvider>
			</body>
		</html>
	);
}
