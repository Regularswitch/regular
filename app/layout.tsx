import '../styles/globals.css';
import type { ReactNode } from 'react';

export const metadata = {
	title: 'Regular Switch',
};

export default function RootLayout({ children }: { children: ReactNode }) {
	return (
		<html lang="en">
			<head>
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
			<body>{children}</body>
		</html>
	);
}

