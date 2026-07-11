import {
	ArrowDownRight,
	ChevronLeft,
	ChevronRight,
	Plus,
	type LucideProps,
} from 'lucide-react';

const DEFAULT_STROKE = 1.75;

export function SectionHeadingArrow(props: LucideProps) {
	return <ArrowDownRight size={16} strokeWidth={DEFAULT_STROKE} aria-hidden {...props} />;
}

export function NavChevronLeft(props: LucideProps) {
	return <ChevronLeft size={18} strokeWidth={DEFAULT_STROKE} aria-hidden {...props} />;
}

export function NavChevronRight(props: LucideProps) {
	return <ChevronRight size={18} strokeWidth={DEFAULT_STROKE} aria-hidden {...props} />;
}

export function AccordionPlusIcon(props: LucideProps) {
	return <Plus size={18} strokeWidth={DEFAULT_STROKE} aria-hidden {...props} />;
}
