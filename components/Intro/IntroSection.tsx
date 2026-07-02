import type { Intro } from '../../types';

const DEFAULT_INTRO_EN: Intro = {
	headline:
		'Creating <strong>visual identities, digital experiences</strong> and <strong>cultural narratives</strong> for brands, institutions and projects that move between <strong>strategy, creativity</strong> and <strong>contemporary impact</strong>.',
	body: '<p>We are RegularSwitch. We develop identities, narratives and creative ecosystems through branding, digital and visual direction. From strategic conception to execution, we build projects that bring brands, people and territories closer in a relevant and contemporary way.</p>',
};

const DEFAULT_INTRO_PT: Intro = {
	headline:
		'Criando <strong>identidades visuais, experiências digitais</strong> e <strong>narrativas culturais</strong> para marcas, instituições e projetos que transitam entre <strong>estratégia, criatividade</strong> e <strong>impacto contemporâneo</strong>.',
	body: '<p>Nós somos a RegularSwitch. Desenvolvemos identidades, narrativas e ecossistemas criativos através do branding, do digital e da direção visual. Da concepção estratégica à execução, construímos projetos que aproximam marcas, pessoas e territórios de forma relevante e contemporânea.</p>',
};

type IntroSectionProps = {
	intro: Intro | null;
	locale?: 'en' | 'pt';
};

export default function IntroSection({ intro, locale = 'en' }: IntroSectionProps) {
	const fallback = locale === 'pt' ? DEFAULT_INTRO_PT : DEFAULT_INTRO_EN;
	const { headline, body } = intro ?? fallback;

	return (
		<section className="intro-section container mx-auto px-7 py-12 md:py-20 lg:py-[150px]" aria-label="Intro">
			<div
				className="intro-headline font-hk text-[clamp(1.75rem,4.5vw,3.125rem)] font-extrabold leading-[1.05] tracking-[-0.02em]"
				dangerouslySetInnerHTML={{ __html: headline }}
			/>
			{body ? (
				<div
					className="intro-body mt-8 max-w-4xl font-hk text-base leading-relaxed md:mt-12 md:text-lg lg:text-xl"
					dangerouslySetInnerHTML={{ __html: body }}
				/>
			) : null}
		</section>
	);
}

export { DEFAULT_INTRO_EN, DEFAULT_INTRO_PT };
