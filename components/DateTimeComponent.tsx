'use client';

import { useEffect, useState } from 'react';

type ContactLocale = 'en' | 'pt';

type CityClock = {
	id: 'sao-paulo' | 'paris';
	label: string;
	flag: string;
	flagLabel: string;
	timeZone: string;
};

const CITIES: CityClock[] = [
	{
		id: 'sao-paulo',
		label: 'São Paulo',
		flag: '🇧🇷',
		flagLabel: 'Brasil',
		timeZone: 'America/Sao_Paulo',
	},
	{
		id: 'paris',
		label: 'Paris',
		flag: '🇫🇷',
		flagLabel: 'France',
		timeZone: 'Europe/Paris',
	},
];

type DateTimeComponentProps = {
	locale?: ContactLocale;
};

type CityState = {
	date: string;
	time: string;
};

function dateLocaleForCity(cityId: CityClock['id'], siteLocale: ContactLocale): string {
	if (cityId === 'paris') return 'fr-FR';
	return siteLocale === 'pt' ? 'pt-BR' : 'en-US';
}

function formatClock(now: Date, dateLocale: string, timeZone: string): CityState {
	return {
		date: now.toLocaleDateString(dateLocale, {
			weekday: 'long',
			month: 'long',
			day: 'numeric',
			timeZone,
		}),
		time: now.toLocaleTimeString(dateLocale, {
			timeZone,
			hour12: false,
			hour: '2-digit',
			minute: '2-digit',
			second: '2-digit',
		}),
	};
}

export default function DateTimeComponent({ locale = 'en' }: DateTimeComponentProps) {
	const [clocks, setClocks] = useState<Record<string, CityState>>({});

	useEffect(() => {
		function updateTime() {
			const now = new Date();
			const next: Record<string, CityState> = {};

			for (const city of CITIES) {
				next[city.id] = formatClock(now, dateLocaleForCity(city.id, locale), city.timeZone);
			}

			setClocks(next);
		}

		updateTime();
		const interval = setInterval(updateTime, 1000);
		return () => clearInterval(interval);
	}, [locale]);

	return (
		<div className="contact-datetime-display font-hk text-(--fg)">
			<div className="contact-datetime-grid grid gap-10 sm:grid-cols-2 sm:gap-12 lg:gap-16">
				{CITIES.map((city) => {
					const clock = clocks[city.id];

					return (
						<div key={city.id} className="contact-datetime-city min-w-0">
							<p className="contact-datetime-city-label flex items-center gap-2 text-[clamp(1rem,2vw,1.25rem)] leading-tight text-(--muted)">
								<span className="text-[1.15em] leading-none" role="img" aria-label={city.flagLabel}>
									{city.flag}
								</span>
								<span>{city.label}</span>
							</p>
							<p className="contact-datetime-date mt-3 text-[clamp(1.35rem,2.8vw,2.25rem)] leading-tight capitalize">
								{clock?.date ?? '—'}
							</p>
							<p className="contact-datetime-time mt-2 text-[clamp(1.5rem,3vw,2.5rem)] leading-tight tabular-nums">
								{clock?.time ?? '—'}
							</p>
						</div>
					);
				})}
			</div>
		</div>
	);
}
