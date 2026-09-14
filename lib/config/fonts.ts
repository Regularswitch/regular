import { Hanken_Grotesk } from 'next/font/google';

export const hankenGrotesk = Hanken_Grotesk({
	subsets: ['latin'],
	weight: ['100', '200', '300', '400', '500', '600', '700', '800', '900'],
	style: ['normal', 'italic'],
	variable: '--font-hanken',
	display: 'swap',
});
