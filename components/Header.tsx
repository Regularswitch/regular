'use client';

import Link from 'next/link';
import { useEffect, useMemo, useRef, useState } from 'react';
import { gsap } from 'gsap';
import { usePathname, useRouter } from 'next/navigation';

import LogoMark from './LogoMark';
import translate, { getCookie, setCookie } from './Translate';
import ThemeToggle from './ThemeToggle';

type HeaderProps = {
	isLight?: boolean;
};

type NavLink = {
	label: string;
	href: string;
	external?: boolean;
};

function getLanguage(): 'PT' | 'EN' {
	const c = getCookie('language');
	return c === 'EN' ? 'EN' : 'PT';
}

const SCROLL_TOP_THRESHOLD = 24;
const HIDE_AFTER_SCROLL = 72;
const SCROLL_DELTA = 8;
const TOP_HOVER_ZONE = 16;

export default function Header({ isLight = false }: HeaderProps) {
	const router = useRouter();
	const pathname = usePathname() ?? '';

	const [isOpen, setIsOpen] = useState(false);
	const [isClosing, setIsClosing] = useState(false);
	const [isMobileMenu, setIsMobileMenu] = useState(false);
	const [language, setLanguage] = useState<'PT' | 'EN'>('PT');
	const [scrolled, setScrolled] = useState(false);
	const [isHeaderHidden, setIsHeaderHidden] = useState(false);

	const tl = useRef<gsap.core.Timeline | null>(null);
	const lastScrollY = useRef(0);
	const isOpenRef = useRef(false);
	const isTopHoverRef = useRef(false);
	const headerBarRef = useRef<HTMLDivElement | null>(null);
	const headerHideTween = useRef<gsap.core.Tween | null>(null);
	const navRef = useRef<HTMLDivElement | null>(null);
	const bgRef = useRef<HTMLDivElement | null>(null);
	const panelsRef = useRef<Array<HTMLDivElement | null>>([]);
	const itemsRef = useRef<Array<HTMLLIElement | null>>([]);
	const barTopRef = useRef<SVGLineElement | null>(null);
	const barBotRef = useRef<SVGLineElement | null>(null);

	useEffect(() => {
		setLanguage(pathname.startsWith('/PT') ? 'PT' : getLanguage());
	}, [pathname]);

	useEffect(() => {
		const mql = window.matchMedia('(max-width: 768px)');
		const sync = () => setIsMobileMenu(mql.matches);
		sync();
		mql.addEventListener('change', sync);
		return () => mql.removeEventListener('change', sync);
	}, []);

	useEffect(() => {
		document.body.style.overflow = isOpen ? 'hidden' : '';
		return () => {
			document.body.style.overflow = '';
		};
	}, [isOpen]);

	useEffect(() => {
		isOpenRef.current = isOpen;
		if (isOpen) setIsHeaderHidden(false);
	}, [isOpen]);

	useEffect(() => {
		const onScroll = () => {
			const y = window.scrollY;
			const atTop = y <= SCROLL_TOP_THRESHOLD;

			setScrolled(!atTop);

			if (isOpenRef.current || atTop || isTopHoverRef.current) {
				setIsHeaderHidden(false);
				lastScrollY.current = y;
				return;
			}

			if (y < HIDE_AFTER_SCROLL) {
				setIsHeaderHidden(false);
			} else {
				const delta = y - lastScrollY.current;
				if (delta > SCROLL_DELTA) setIsHeaderHidden(true);
				else if (delta < -SCROLL_DELTA) setIsHeaderHidden(false);
			}

			lastScrollY.current = y;
		};

		const onMouseMove = (e: MouseEvent) => {
			const hover = e.clientY <= TOP_HOVER_ZONE;
			if (hover === isTopHoverRef.current) return;
			isTopHoverRef.current = hover;
			if (hover) setIsHeaderHidden(false);
		};

		onScroll();
		window.addEventListener('scroll', onScroll, { passive: true });
		window.addEventListener('mousemove', onMouseMove, { passive: true });

		return () => {
			window.removeEventListener('scroll', onScroll);
			window.removeEventListener('mousemove', onMouseMove);
		};
	}, []);

	useEffect(() => {
		const bar = headerBarRef.current;
		if (!bar) return;

		const shouldHide = isHeaderHidden && !isOpen;

		headerHideTween.current?.kill();

		if (shouldHide) {
			headerHideTween.current = gsap.to(bar, {
				yPercent: -100,
				duration: 0.38,
				ease: 'power2.in',
				overwrite: true,
			});
			return;
		}

		headerHideTween.current = gsap.to(bar, {
			yPercent: 0,
			duration: 0.72,
			ease: 'power4.out',
			overwrite: true,
		});
	}, [isHeaderHidden, isOpen]);

	useEffect(() => {
		const bar = headerBarRef.current;
		if (!bar) return;
		gsap.set(bar, { yPercent: 0 });
	}, [pathname]);

	const prefix = language === 'PT' ? 'PT' : '';

	const textColor = isLight ? 'text-white' : 'text-[color:var(--fg)]';

	const links = useMemo<NavLink[]>(
		() =>
			language === 'PT'
				? [
						{ label: 'Projetos', href: `/${prefix}/work`.replace('//', '/') },
						{ label: 'Capacidades', href: `/${prefix}/capacidades`.replace('//', '/') },
						{ label: 'Educação', href: `/${prefix}/education`.replace('//', '/') },
						{ label: 'Sobre Nós', href: `/${prefix}/about`.replace('//', '/') },
						{ label: 'Contato', href: `/${prefix}/contact-3`.replace('//', '/') },
					]
				: [
						{ label: 'Projects', href: `/${prefix}/work`.replace('//', '/') },
						{ label: 'Capabilities', href: `/${prefix}/capacidades`.replace('//', '/') },
						{ label: 'Education', href: `/${prefix}/education`.replace('//', '/') },
						{ label: 'About', href: `/${prefix}/about`.replace('//', '/') },
						{ label: 'Contact', href: `/${prefix}/contact-3`.replace('//', '/') },
					],
		[prefix, language],
	);

	function setTabbables(enabled: boolean) {
		const root = navRef.current;
		if (!root) return;
		root.querySelectorAll<HTMLAnchorElement>('a, button').forEach((el) => {
			el.setAttribute('tabindex', enabled ? '0' : '-1');
		});
	}

	function buildOpen() {
		const nav = navRef.current;
		const bg = bgRef.current;
		const [top, middle, bottom] = panelsRef.current;
		const barTop = barTopRef.current;
		const barBot = barBotRef.current;
		if (!nav || !bg || !top || !middle || !bottom || !barTop || !barBot) return;

		const bars = [barTop, barBot];
		const panels = [top, middle, bottom];

		// Always start from a known state (prevents cumulative transforms)
		gsap.set(panels, { clearProps: 'transform' });
		gsap.set(panels, { xPercent: 110, yPercent: 0, rotation: 0 });
		gsap.set(bg, { opacity: 0 });
		gsap.set(nav, { visibility: 'hidden', pointerEvents: 'none' });

		tl.current?.kill();
		tl.current = gsap
			.timeline()
			.set(nav, { visibility: 'visible', pointerEvents: 'auto' })
			.to(bg, { opacity: 1, duration: 0.35, ease: 'power2.out' }, 0)
			.fromTo(
				panels,
				{ xPercent: 110, yPercent: 0, rotation: 0 },
				{ xPercent: 0, duration: 0.6, ease: 'back.out(1.4)', stagger: 0.12 },
				0,
			)
			.fromTo(
				itemsRef.current.filter(Boolean),
				{ opacity: 0, x: -20 },
				{ opacity: 1, x: 0, duration: 0.9, ease: 'expo.out', stagger: 0.03 },
				0.08,
			)
			.fromTo(
				barTop,
				{ attr: { x1: 3, y1: 7, x2: 17, y2: 7 } },
				{ attr: { x1: 5, y1: 5, x2: 15, y2: 15 }, duration: 0.35, ease: 'back.out(1.4)' },
				0.06,
			)
			.fromTo(
				barBot,
				{ attr: { x1: 3, y1: 13, x2: 17, y2: 13 } },
				{ attr: { x1: 15, y1: 5, x2: 5, y2: 15 }, duration: 0.35, ease: 'back.out(1.4)' },
				0.06,
			);

		// keep bars visible on open
		gsap.set(bars, { opacity: 1 });
	}

	function buildClose() {
		const nav = navRef.current;
		const bg = bgRef.current;
		const [top, middle, bottom] = panelsRef.current;
		if (!nav || !bg || !top || !middle || !bottom) return;

		const barTop = barTopRef.current;
		const barBot = barBotRef.current;
		const panels = [top, middle, bottom];

		// If bars aren't mounted yet (portal not ready), force-close without animation.
		if (!barTop || !barBot) {
			tl.current?.kill();
			gsap.set(bg, { opacity: 0 });
			gsap.set(nav, { visibility: 'hidden', pointerEvents: 'none' });
			gsap.set(panels, { clearProps: 'transform' });
			setIsClosing(false);
			return;
		}

		tl.current?.kill();
		tl.current = gsap
			.timeline()
			.to(
				[barTop, barBot],
				{
					attr: { x1: 3, y1: (i: number) => (i === 0 ? 7 : 13), x2: 17, y2: (i: number) => (i === 0 ? 7 : 13) },
					duration: 0.2,
					ease: 'power3.in',
				},
				0,
			)
			.to(
				[bottom, middle, top],
				{
					yPercent: 160,
					rotation: () => gsap.utils.random(-15, 15),
					duration: 0.9,
					ease: 'power3.in',
					stagger: 0.03,
				},
				0,
			)
			.to(bg, { opacity: 0, duration: 0.25, ease: 'power2.in' }, 0.5)
			.set(nav, { visibility: 'hidden', pointerEvents: 'none' })
			// Reset panels so next open is deterministic
			.set(panels, { clearProps: 'transform' });

		tl.current.eventCallback('onComplete', () => {
			setIsClosing(false);
		});
	}

	function openMenu() {
		setIsClosing(false);
		setIsOpen(true);
		setTabbables(true);
		buildOpen();
	}

	function closeMenu() {
		setIsOpen(false);
		setIsClosing(true);
		setTabbables(false);
		buildClose();
	}

	function toggleMenu() {
		if (isOpen) closeMenu();
		else openMenu();
	}

	useEffect(() => {
		const onKeyDown = (e: KeyboardEvent) => {
			if (e.key === 'Escape' && isOpen) closeMenu();
		};
		document.addEventListener('keydown', onKeyDown);
		return () => document.removeEventListener('keydown', onKeyDown);
	}, [isOpen]);

	useEffect(() => {
		// close overlay on route change
		closeMenu();
		setIsHeaderHidden(false);
		lastScrollY.current = 0;
		setScrolled(false);
		isTopHoverRef.current = false;
	}, [pathname]);

	function setLanguageCookie(next: 'PT' | 'EN') {
		setCookie('language', next);
		setLanguage(next);
		const cleaned = pathname.replace('/PT', '');
		const nextPath = next === 'PT' ? `/PT${cleaned}` : cleaned || '/';
		router.replace(nextPath);
	}

	return (
		<>
			<div
				ref={headerBarRef}
				className={`fixed top-0 left-0 right-0 z-600 flex items-center justify-between px-7 py-4 lg:pb-8 will-change-transform transition-[background-color,backdrop-filter,border-color] duration-300 ease-out ${
					scrolled ? 'bg-(--bg)/85 backdrop-blur-md border-b border-black/5 dark:border-white/10' : 'border-b border-transparent'
				}`}
			>
				<span className={`z-310 ${isOpen && isMobileMenu ? 'opacity-0 pointer-events-none' : ''}`}>
					<Link href={`/${prefix}`.replace('//', '/')}>
						<LogoMark
							className={`h-8 w-auto ${
								// During mobile close animation, keep contrast over fading backdrop.
								isClosing && isMobileMenu ? 'text-white' : textColor
								}`}
						/>
					</Link>
				</span>

				{/* Desktop nav */}
				<nav className={`hidden lg:flex items-center gap-6 text-[15px] leading-[20px] ${textColor}`}>
					{links.map((l) => (
						<Link key={l.href} href={l.href} className={`hover:opacity-70 ${textColor}`}>
							{l.label}
						</Link>
					))}

				</nav>
				<div className="hidden lg:flex items-center gap-3">
					<button
						type="button"
						onClick={() => setLanguageCookie(language === 'PT' ? 'EN' : 'PT')}
						className={`rounded w-[34px] h-[28px] text-xs border border-black/10 dark:border-white/15 bg-(--surface) ${textColor} hover:opacity-80`}
						aria-label={language === 'PT' ? 'Mudar para inglês' : 'Mudar para português'}
					>
						{language === 'PT' ? 'EN' : 'PT'}
					</button>
					<ThemeToggle />
				</div>

				{/* Mobile toggle */}
				<button
					type="button"
					onClick={toggleMenu}
					aria-expanded={isOpen}
					aria-label={isOpen ? 'Close menu' : 'Open menu'}
					className={`lg:hidden relative z-10 w-11 h-11 flex items-center justify-center ${isOpen ? 'text-black' : textColor}`}
				>
					<svg width="40" height="40" viewBox="0 0 20 20" fill="none">
						<line ref={barTopRef} x1="3" y1="7" x2="17" y2="7" stroke="currentColor" strokeWidth="1.5" strokeLinecap="round" />
						<line ref={barBotRef} x1="3" y1="13" x2="17" y2="13" stroke="currentColor" strokeWidth="1.5" strokeLinecap="round" />
					</svg>
				</button>
			</div>

			{/* Mobile overlay */}
			<div ref={navRef} className="fixed inset-0 z-500 p-4 pointer-events-none" style={{ visibility: 'hidden' }}>
				<div
					ref={bgRef}
					className="absolute inset-0 bg-black/40 opacity-0"
					onClick={() => {
						if (isOpen) closeMenu();
					}}
				/>

				<div className="relative flex flex-col items-end gap-2">
					<div
						ref={(el) => {
							panelsRef.current[0] = el;
						}}
						className="w-full max-w-[700px] rounded-[10px] bg-white text-black overflow-y-auto"
					>
						<div className="md:hidden px-10 pt-8">
							<Link href={`/${prefix}`.replace('//', '/')} onClick={() => closeMenu()} aria-label="RSW — início">
								<LogoMark className="h-8 w-auto text-black" />
							</Link>
						</div>
						<ul className="list-none flex flex-col p-10 pt-14">
							{links
								.filter((l) => !l.external)
								.map((l, idx) => (
									<li
										key={l.href}
										ref={(el) => {
											itemsRef.current[idx] = el;
										}}
										className="overflow-hidden"
									>
										<Link
											href={l.href}
											onClick={() => closeMenu()}
											className="block py-2 text-[clamp(1.5rem,4vw,1.8rem)] font-semibold leading-[1.1] tracking-[-0.03em]"
										>
											{l.label}
										</Link>
									</li>
								))}
						</ul>

						<div className="px-10 pb-8 text-xs opacity-70">
							<button
								type="button"
								onClick={() => setLanguageCookie(language === 'PT' ? 'EN' : 'PT')}
								className="mr-3 inline-flex items-center justify-center rounded px-3 py-2 border border-black/15"
								aria-label={language === 'PT' ? 'Mudar para inglês' : 'Mudar para português'}
							>
								{language === 'PT' ? 'EN' : 'PT'}
							</button>
							<span className="inline-flex align-middle">
								<ThemeToggle />
							</span>
						</div>
					</div>

					<div
						ref={(el) => {
							panelsRef.current[1] = el;
						}}
						className="w-full max-w-[700px] rounded-[10px] bg-linear-to-r from-purple-600 via-pink-500 to-yellow-400 text-black p-6"
					>
						<div className="text-(--fg) text-[0.65rem] uppercase tracking-widest opacity-60 mb-3">What's New</div>
						<div className="flex items-center gap-4">
							{/* <div className="w-12 h-12 rounded-xl bg-black/10 flex items-center justify-center">↯</div> */}
							<div>
								<div className="text-(--fg) font-semibold text-lg leading-tight">Regular Switch</div>
								<div className="text-(--fg) text-sm opacity-70">New website</div>
							</div>
						</div>
					</div>

					<div
						ref={(el) => {
							panelsRef.current[2] = el;
						}}
						className="w-full max-w-[700px] rounded-[10px] bg-black text-white/70 flex items-center p-6"
					>
						<ul className="list-none flex flex-wrap gap-4 text-sm">
							<li>
								<a href="https://www.instagram.com/regular.switch" target="_blank" rel="noopener noreferrer" className="hover:text-white">
									Instagram
								</a>
							</li>
							<li>
								<a href="mailto:contact@regularswitch.com" className="hover:text-white">
									Email
								</a>
							</li>
							<li>
								<a href="tel:+5511945408448" className="hover:text-white">
									Tel
								</a>
							</li>
						</ul>
					</div>
				</div>
			</div>
		</>
	);
}

