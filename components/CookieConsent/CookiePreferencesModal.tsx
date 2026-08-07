'use client';

import { useEffect, useId, useMemo, useState } from 'react';

import type { CookieCategory, CookieCategoryId, LegalContent } from '../../lib/content/legal/defaults';
import PolicyModalShell from '../Legal/PolicyModalShell';

export type CookiePrefs = Record<CookieCategoryId, boolean>;

type CookiePreferencesModalProps = {
	open: boolean;
	content: LegalContent;
	initialPrefs?: CookiePrefs | null;
	dismissible?: boolean;
	onClose: () => void;
	onRejectAll: () => void;
	onSubmit: (prefs: CookiePrefs) => void;
};

function prefsFromCategories(categories: CookieCategory[]): CookiePrefs {
	return {
		necessary: true,
		performance: categories.find((c) => c.id === 'performance')?.defaultOn ?? true,
		functional: categories.find((c) => c.id === 'functional')?.defaultOn ?? false,
		marketing: categories.find((c) => c.id === 'marketing')?.defaultOn ?? true,
	};
}

export default function CookiePreferencesModal({
	open,
	content,
	initialPrefs,
	dismissible = true,
	onClose,
	onRejectAll,
	onSubmit,
}: CookiePreferencesModalProps) {
	const titleId = useId();
	const [expanded, setExpanded] = useState<CookieCategoryId | null>('performance');
	const [prefs, setPrefs] = useState<CookiePrefs>(() =>
		initialPrefs ?? prefsFromCategories(content.categories),
	);

	useEffect(() => {
		if (!open) return;
		setPrefs(initialPrefs ?? prefsFromCategories(content.categories));
		setExpanded('performance');
	}, [open, content.categories, initialPrefs]);

	const categories = useMemo(() => content.categories, [content.categories]);

	return (
		<PolicyModalShell
			open={open}
			onClose={onClose}
			dismissible={dismissible}
			labelledBy={titleId}
		>
			<div className="cookie-prefs-modal-header">
				<h2 id={titleId} className="cookie-prefs-modal-title font-hk">
					{content.cookiesModalTitle}
				</h2>
				{dismissible ? (
					<button
						type="button"
						className="cookie-prefs-modal-close custom-cursor-target"
						onClick={onClose}
						aria-label="Close"
					>
						×
					</button>
				) : null}
			</div>

			{content.cookiesIntro?.trim() ? (
				<div
					className="cookie-prefs-modal-intro font-hk"
					dangerouslySetInnerHTML={{ __html: content.cookiesIntro }}
				/>
			) : null}

			<ul className="cookie-prefs-list">
				{categories.map((category) => {
					const isOpen = expanded === category.id;
					const enabled = category.locked ? true : prefs[category.id];
					return (
						<li key={category.id} className="cookie-prefs-item">
							<div className="cookie-prefs-item-row">
								<button
									type="button"
									className="cookie-prefs-item-toggle-expand"
									aria-expanded={isOpen}
									onClick={() =>
										setExpanded((current) => (current === category.id ? null : category.id))
									}
								>
									<span className="cookie-prefs-item-plus" aria-hidden>
										{isOpen ? '−' : '+'}
									</span>
									<span className="cookie-prefs-item-label font-hk">{category.title}</span>
								</button>
								<label className="cookie-prefs-switch">
									<input
										type="checkbox"
										checked={enabled}
										disabled={category.locked}
										onChange={(event) => {
											if (category.locked) return;
											setPrefs((current) => ({
												...current,
												[category.id]: event.target.checked,
											}));
										}}
									/>
									<span className="cookie-prefs-switch-track" aria-hidden />
								</label>
							</div>
							{isOpen ? (
								<div
									className="cookie-prefs-item-body font-hk"
									dangerouslySetInnerHTML={{ __html: category.description }}
								/>
							) : null}
						</li>
					);
				})}
			</ul>

			<div className="cookie-prefs-modal-actions">
				<button
					type="button"
					className="cookie-prefs-btn cookie-prefs-btn--ghost font-hk"
					onClick={onRejectAll}
				>
					{content.rejectAllLabel}
				</button>
				<button
					type="button"
					className="cookie-prefs-btn cookie-prefs-btn--solid font-hk"
					onClick={() => onSubmit({ ...prefs, necessary: true })}
				>
					{content.submitLabel}
				</button>
			</div>
		</PolicyModalShell>
	);
}
