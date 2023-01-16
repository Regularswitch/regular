const dictionaryEN: any = {
    "Sobre": "About",
    "Contato": "Contact",
    "Branding / Digital / Arquitetura Gráfica": "Branding / Digital / Graphic Architecture",
    "Regularswitch é uma agência de design multi-cultural com escritório no Brasil e na França. Trabalhando na fronteira entre analógico e digital para oferecer experiências visuais que importam.": "RegularSwitch is a multi-cultural design agency based in Brazil and France. Working on the edge between analog and digital to offer visual experiences that matter.",
}

export default function translate(text: string, language?: string): string {
    let word = text
    if (language == 'EN') {
        word = dictionaryEN?.[text] || text
    }
    return word
}

export function getCookie(name: string) {
    const value = `; ${document.cookie}`
    const parts = value.split(`; ${name}=`)
    if (parts.length === 2) return parts?.pop()?.split(';')?.shift()
}

export function setCookie(name: string, value: string) {
    document.cookie = `${name}=${value};path=/`
}