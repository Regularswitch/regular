import { Hanken_Grotesk } from 'next/font/google';

export const hankenGrotesk = Hanken_Grotesk({
	subsets: ['latin'],
	weight: ['300', '400', '500', '700', '900'],
	style: ['normal', 'italic'],
	variable: '--font-hanken',
	display: 'swap',
});
