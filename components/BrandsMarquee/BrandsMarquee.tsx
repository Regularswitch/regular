import Image from 'next/image';

import type { Brand } from '../../types';

type BrandsMarqueeProps = {
	title?: string;
	brands: Brand[];
};

/** Repete marcas até cada metade do track preencher a tela (loop -50% sem vazio). */
function buildMarqueeTrack(brands: Brand[]) {
	const minItemsPerHalf = 24;
	const repeats = Math.max(2, Math.ceil(minItemsPerHalf / brands.length));
	const half = Array.from({ length: repeats }, () => brands).flat();
	return { track: [...half, ...half], halfLength: half.length };
}

function BrandMark({ name, logo }: { name: string; logo?: string }) {
	if (logo) {
		const isSvg = logo.endsWith('.svg');

		return (
			<Image
				src={logo}
				alt={name}
				width={180}
				height={48}
				unoptimized={isSvg}
				className="brand-mark h-8 w-auto max-w-[140px] object-contain opacity-90 md:h-10 md:max-w-[180px]"
			/>
		);
	}

	return (
		<span className="whitespace-nowrap text-lg font-semibold tracking-tight text-(--fg) opacity-90 md:text-2xl">
			{name}
		</span>
	);
}

export default function BrandsMarquee({ title = 'Marcas que confiam na gente', brands }: BrandsMarqueeProps) {
	if (!brands.length) return null;

	const { track, halfLength } = buildMarqueeTrack(brands);

	return (
		<section className="py-12 md:py-20" aria-label={title}>
			<div className="mb-8 flex items-end justify-between px-7 md:mb-12">
				<h2 className="text-base font-medium text-(--fg) md:text-lg">
					{title} <span aria-hidden>↘</span>
				</h2>
			</div>

			<div className="brands-marquee overflow-hidden">
				<div className="brands-marquee-track flex w-max items-center gap-12 md:gap-20">
					{track.map((brand, index) => (
						<div
							key={`${brand.id}-${index}`}
							className="flex shrink-0 items-center justify-center px-2 md:px-4"
							aria-hidden={index >= halfLength}
						>
							<BrandMark name={brand.name} logo={brand.logo} />
						</div>
					))}
				</div>
			</div>
		</section>
	);
}
