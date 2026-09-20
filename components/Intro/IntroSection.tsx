import type { Intro } from '../../types';

const DEFAULT_BALLOONS_EN = [
	'Strategy',
	'Narrative',
	'Branding',
	'Visual Systems',
	'Digital Experiences',
	'Content',
	'Campaigns',
	'Generative Design',
	'Motion Design',
	'Editorial Design',
	'Expography',
];

const DEFAULT_BALLOONS_PT = [
	'Estratégia',
	'Narrativa',
	'Branding',
	'Sistemas Visuais',
	'Experiências Digitais',
	'Conteúdo',
	'Campanhas',
	'Design Generativo',
	'Motion Design',
	'Design Editorial',
	'Expografia',
];

const DEFAULT_INTRO_EN: Intro = {
	headline:
		'Creating <strong>visual identities</strong>, <strong>digital experiences and cultural narratives</strong> for brands, institutions and projects that move between <strong>strategy</strong>, <strong>creativity and contemporary impact</strong>.',
	body: '<p>We are RegularSwitch. We develop identities, narratives and creative ecosystems through branding, digital and visual direction. From strategic conception to execution, we build projects that bring brands, people and territories closer in a relevant and contemporary way.</p>',
	balloons: DEFAULT_BALLOONS_EN,
};

const DEFAULT_INTRO_PT: Intro = {
	headline:
		'Criando <strong>identidades visuais</strong>, <strong>experiências digitais e narrativas culturais</strong> para marcas, instituições e projetos que transitam entre <strong>estratégia</strong>, <strong>criatividade e impacto contemporâneo</strong>.',
	body: '<p>Nós somos a RegularSwitch. Desenvolvemos identidades, narrativas e ecossistemas criativos através do branding, do digital e da direção visual. Da concepção estratégica à execução, construímos projetos que aproximam marcas, pessoas e territórios de forma relevante e contemporânea.</p>',
	balloons: DEFAULT_BALLOONS_PT,
};

type IntroSectionProps = {
	intro: Intro | null;
	locale?: 'en' | 'pt';
};

export default function IntroSection({ intro, locale = 'en' }: IntroSectionProps) {
	const fallback = locale === 'pt' ? DEFAULT_INTRO_PT : DEFAULT_INTRO_EN;
	const headline = intro?.headline?.trim() ? intro.headline : fallback.headline;
	const body = intro?.body?.trim() ? intro.body : fallback.body;
	const balloons = Array.isArray(intro?.balloons) ? intro.balloons : (fallback.balloons ?? []);

	return (
		<section className="intro-section w-full py-6 md:py-10 lg:py-[80px]" aria-label="Intro">
			<div className="intro-copy md:grid md:grid-cols-2 md:items-start md:gap-12 lg:gap-16">
				<h1
					className="intro-headline min-w-0 font-hk"
					dangerouslySetInnerHTML={{ __html: headline }}
				/>
				{body ? (
					<div
						className="intro-body mt-8 min-w-0 max-w-none font-hk md:mt-0"
						dangerouslySetInnerHTML={{ __html: body }}
					/>
				) : null}
			</div>

			{balloons.length > 0 ? (
				<ul className="intro-balloons mt-10 flex w-full list-none flex-wrap gap-2 p-0 md:mt-14 md:gap-2.5" aria-label={locale === 'pt' ? 'Áreas' : 'Capabilities'}>
					{balloons.map((label) => (
						<li key={label}>
							<span className="intro-balloon">{label}</span>
						</li>
					))}
				</ul>
			) : null}
		</section>
	);
}

export { DEFAULT_INTRO_EN, DEFAULT_INTRO_PT, DEFAULT_BALLOONS_EN, DEFAULT_BALLOONS_PT };
