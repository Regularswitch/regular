'use client';

import { useEffect, useState } from 'react';

type Theme = 'dark' | 'light';

function getCookie(name: string): string {
	const value = `; ${document.cookie}`;
	const parts = value.split(`; ${name}=`);
	return parts?.pop()?.split(';')?.shift() || '';
}

function setCookie(name: string, value: string) {
	document.cookie = `${name}=${value};path=/;max-age=31536000;samesite=lax`;
}

function applyTheme(theme: Theme) {
	const root = document.documentElement;
	if (theme === 'dark') root.classList.add('dark');
	else root.classList.remove('dark');
}

export default function ThemeToggle() {
	const [theme, setTheme] = useState<Theme>('dark');

	useEffect(() => {
		const t = (getCookie('theme') as Theme) || 'dark';
		setTheme(t);
		applyTheme(t);
	}, []);

	return (
		<button
			type="button"
			onClick={() => {
				const next: Theme = theme === 'dark' ? 'light' : 'dark';
				setTheme(next);
				setCookie('theme', next);
				applyTheme(next);
			}}
			className="inline-flex items-center justify-center rounded w-[28px] h-[28px] select-none border border-black/10 dark:border-white/15 bg-(--surface) text-(--fg) hover:opacity-80"
			aria-label="Alternar tema"
		>
			{theme === 'dark' ? (
				<svg viewBox="0 0 24 24" width="16" height="16" aria-hidden="true">
					<path
						fill="currentColor"
						d="M21.752 15.002A8.98 8.98 0 0 1 12.01 3.01a.75.75 0 0 0-.94-.94A10.5 10.5 0 1 0 22.692 15.94a.75.75 0 0 0-.94-.938Z"
					/>
				</svg>
			) : (
				<svg viewBox="0 0 24 24" width="16" height="16" aria-hidden="true">
					<path
						fill="currentColor"
						d="M12 18.25a6.25 6.25 0 1 1 0-12.5a6.25 6.25 0 0 1 0 12.5ZM12 1.75a.75.75 0 0 1 .75.75v1.75a.75.75 0 0 1-1.5 0V2.5a.75.75 0 0 1 .75-.75Zm0 18.25a.75.75 0 0 1 .75.75v1.75a.75.75 0 0 1-1.5 0V20.75a.75.75 0 0 1 .75-.75ZM22.25 12a.75.75 0 0 1-.75.75h-1.75a.75.75 0 0 1 0-1.5h1.75a.75.75 0 0 1 .75.75ZM4.25 12a.75.75 0 0 1-.75.75H1.75a.75.75 0 0 1 0-1.5H3.5a.75.75 0 0 1 .75.75Zm15.38-7.63a.75.75 0 0 1 0 1.06l-1.24 1.24a.75.75 0 1 1-1.06-1.06l1.24-1.24a.75.75 0 0 1 1.06 0ZM7.91 16.09a.75.75 0 0 1 0 1.06l-1.24 1.24a.75.75 0 0 1-1.06-1.06l1.24-1.24a.75.75 0 0 1 1.06 0Zm11.72 1.24a.75.75 0 0 1-1.06 0l-1.24-1.24a.75.75 0 1 1 1.06-1.06l1.24 1.24a.75.75 0 0 1 0 1.06ZM7.91 7.91a.75.75 0 0 1-1.06 0L5.61 6.67a.75.75 0 1 1 1.06-1.06l1.24 1.24a.75.75 0 0 1 0 1.06Z"
					/>
				</svg>
			)}
		</button>
	);
}

