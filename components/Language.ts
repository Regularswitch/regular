
export type tipoLinguagens =  "PT" | "EN"

type requestNext = {
    query: {
        slug?: tipoLinguagens
    }
}

export default function Language(req: requestNext): tipoLinguagens {
    return req.query?.slug || 'PT'
}