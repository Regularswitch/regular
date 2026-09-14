type JsonLdProps = {
	id: string;
	data: Record<string, unknown> | null | undefined;
};

/** Injeta JSON-LD no documento (SEO / AEO / GEO). Usar só em Server Components. */
export default function JsonLd({ id, data }: JsonLdProps) {
	if (!data || typeof data !== 'object') return null;

	return (
		<script
			id={id}
			type="application/ld+json"
			suppressHydrationWarning
			dangerouslySetInnerHTML={{ __html: JSON.stringify(data) }}
		/>
	);
}
