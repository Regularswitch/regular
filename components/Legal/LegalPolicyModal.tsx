'use client';

import { useEffect, useId } from 'react';

type LegalPolicyModalProps = {
	open: boolean;
	title: string;
	bodyHtml: string;
	onClose: () => void;
	closeLabel?: string;
};

export default function LegalPolicyModal({
	open,
	title,
	bodyHtml,
	onClose,
	closeLabel = 'Close',
}: LegalPolicyModalProps) {
	const titleId = useId();

	useEffect(() => {
		if (!open) return;

		const onKeyDown = (event: KeyboardEvent) => {
			if (event.key === 'Escape') onClose();
		};

		const previousOverflow = document.body.style.overflow;
		document.body.style.overflow = 'hidden';
		window.addEventListener('keydown', onKeyDown);

		return () => {
			document.body.style.overflow = previousOverflow;
			window.removeEventListener('keydown', onKeyDown);
		};
	}, [open, onClose]);

	if (!open) return null;

	return (
		<div
			className="legal-policy-modal"
			role="dialog"
			aria-modal="true"
			aria-labelledby={titleId}
		>
			<button
				type="button"
				className="legal-policy-modal-backdrop"
				aria-label={closeLabel}
				onClick={onClose}
			/>
			<div className="legal-policy-modal-panel">
				<div className="legal-policy-modal-header">
					<h2 id={titleId} className="legal-policy-modal-title font-hk">
						{title}
					</h2>
					<button
						type="button"
						className="legal-policy-modal-close custom-cursor-target"
						onClick={onClose}
						aria-label={closeLabel}
					>
						×
					</button>
				</div>
				<div
					className="legal-policy-modal-body font-hk"
					dangerouslySetInnerHTML={{ __html: bodyHtml }}
				/>
			</div>
		</div>
	);
}
