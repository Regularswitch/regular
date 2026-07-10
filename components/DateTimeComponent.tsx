'use client';

import { useEffect, useState } from 'react';

type DateTimeComponentProps = {
	locale?: 'en' | 'pt';
};

export default function DateTimeComponent({ locale = 'en' }: DateTimeComponentProps) {
	const [date, setDate] = useState('');
	const [time, setTime] = useState('');

	useEffect(() => {
		function updateTime() {
			const dateLocale = locale === 'pt' ? 'pt-BR' : 'en-US';
			const dateOptions: Intl.DateTimeFormatOptions = {
				weekday: 'long',
				month: 'long',
				day: 'numeric',
				timeZone: 'America/Sao_Paulo',
			};

			const timeOptions: Intl.DateTimeFormatOptions = {
				timeZone: 'America/Sao_Paulo',
				hour12: false,
				hour: '2-digit',
				minute: '2-digit',
				second: '2-digit',
			};

			const now = new Date();
			setDate(now.toLocaleDateString(dateLocale, dateOptions));
			setTime(now.toLocaleTimeString(dateLocale, timeOptions));
		}

		updateTime();
		const interval = setInterval(updateTime, 1000);
		return () => clearInterval(interval);
	}, [locale]);

	const cityLabel = locale === 'pt' ? 'São Paulo' : 'São Paulo';

	return (
		<div className="contact-datetime-display font-hk text-(--fg)">
			<p className="contact-datetime-date text-[clamp(1.5rem,3vw,2.5rem)] leading-tight capitalize">{date}</p>
			<p className="contact-datetime-time mt-2 text-[clamp(1.5rem,3vw,2.5rem)] leading-tight">
				<span className="text-(--muted)">{cityLabel}: </span>
				<span>{time}</span>
			</p>
		</div>
	);
}
