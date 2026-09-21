type JsonLdProps = {
	id: string;
	data: Record<string, unknown> | null | undefined;
};

/**
 * Injeta JSON-LD no documento (SEO / AEO / GEO).
 * `type` precisa ser o 1º atributo: aeo.js extrai com
 * `/<script\s+type=["']application\/ld\+json["']/`.
 */
export default function JsonLd({ id, data }: JsonLdProps) {
	if (!data || typeof data !== 'object') return null;

	return (
		<script
			type="application/ld+json"
			id={id}
			suppressHydrationWarning
			dangerouslySetInnerHTML={{ __html: JSON.stringify(data) }}
		/>
	);
}
