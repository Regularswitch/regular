'use client';

import {
	useCallback,
	useEffect,
	useRef,
	useState,
	type AnimationEvent,
	type ReactNode,
} from 'react';

const CLOSE_MS = 280;

type PolicyModalShellProps = {
	open: boolean;
	onClose: () => void;
	/** Se false, backdrop/Escape não fecham (ex.: consentimento obrigatório). */
	dismissible?: boolean;
	labelledBy?: string;
	label?: string;
	panelClassName?: string;
	children: ReactNode;
};

/**
 * Mantém o modal montado durante o fade-out para animar abertura e fechamento.
 */
export default function PolicyModalShell({
	open,
	onClose,
	dismissible = true,
	labelledBy,
	label,
	panelClassName = '',
	children,
}: PolicyModalShellProps) {
	const [mounted, setMounted] = useState(false);
	const [visible, setVisible] = useState(false);
	const closeTimerRef = useRef<ReturnType<typeof setTimeout> | null>(null);

	const finishClose = useCallback(() => {
		if (closeTimerRef.current) {
			clearTimeout(closeTimerRef.current);
			closeTimerRef.current = null;
		}
		setMounted(false);
		setVisible(false);
	}, []);

	// Abrir: monta → pinta estado inicial → aplica is-open no próximo frame.
	useEffect(() => {
		if (!open) return;

		if (closeTimerRef.current) {
			clearTimeout(closeTimerRef.current);
			closeTimerRef.current = null;
		}

		setMounted(true);
		let raf2 = 0;
		const raf1 = requestAnimationFrame(() => {
			raf2 = requestAnimationFrame(() => setVisible(true));
		});
		return () => {
			cancelAnimationFrame(raf1);
			cancelAnimationFrame(raf2);
		};
	}, [open]);

	// Fechar: tira is-open e desmonta após a animação (timeout + animationend).
	useEffect(() => {
		if (open) return;
		setVisible(false);
		if (!mounted) return;

		const reduced =
			typeof window !== 'undefined' &&
			window.matchMedia('(prefers-reduced-motion: reduce)').matches;
		closeTimerRef.current = setTimeout(finishClose, reduced ? 0 : CLOSE_MS);
		return () => {
			if (closeTimerRef.current) {
				clearTimeout(closeTimerRef.current);
				closeTimerRef.current = null;
			}
		};
	}, [open, mounted, finishClose]);

	useEffect(() => {
		if (!mounted || !visible) return;

		const onKeyDown = (event: KeyboardEvent) => {
			if (event.key === 'Escape' && dismissible) onClose();
		};
		const prev = document.body.style.overflow;
		document.body.style.overflow = 'hidden';
		window.addEventListener('keydown', onKeyDown);
		return () => {
			document.body.style.overflow = prev;
			window.removeEventListener('keydown', onKeyDown);
		};
	}, [mounted, visible, dismissible, onClose]);

	const handlePanelAnimationEnd = useCallback(
		(event: AnimationEvent<HTMLDivElement>) => {
			if (event.target !== event.currentTarget) return;
			if (open || visible) return;
			const name = event.animationName;
			if (
				name !== 'cookie-prefs-panel-out' &&
				!name.endsWith('cookie-prefs-panel-out')
			) {
				return;
			}
			finishClose();
		},
		[finishClose, open, visible],
	);

	const requestClose = useCallback(() => {
		if (!dismissible) return;
		onClose();
	}, [dismissible, onClose]);

	if (!mounted) return null;

	// Sem classe no frame inicial (opacity 0); is-open após rAF; is-closing só ao fechar.
	const stateClass = visible ? ' is-open' : !open ? ' is-closing' : '';

	return (
		<div
			className={`cookie-prefs-modal${stateClass}`}
			role="dialog"
			aria-modal="true"
			aria-labelledby={labelledBy}
			aria-label={label}
		>
			<button
				type="button"
				className="cookie-prefs-modal-backdrop"
				aria-label="Close"
				onClick={requestClose}
			/>
			<div
				className={`cookie-prefs-modal-panel ${panelClassName}`.trim()}
				onAnimationEnd={handlePanelAnimationEnd}
			>
				{children}
			</div>
		</div>
	);
}
