import { useEffect, useRef, useState } from "react";

export default function FontMagic() {
    const [cursorX, setCursorX] = useState(0)
    const [largura, setLargura] = useState(0)
    const contentText = useRef<HTMLDivElement>(null)
    const refsLetras = useRef([])
    function mouseMove($e: MouseEvent) {
        setCursorX($e?.clientX)
    }
    // function TouchMove($e: TouchEvent) {
    //     setCursorX($e?.changedTouches[0].clientX)
    // }
    function escala(name: any) {
        let x = 0
        let distancia = 0
        if (typeof document !== "undefined") {

            let $elemento = document.getElementById(name)
            if ($elemento) {
                x = $elemento?.offsetLeft || 0
                let letra = name.substr(6, 1)
                distancia = cursorX - x
            }
        }
        let meio = largura / 2
        if (distancia > meio) {
            return distancia * -1 + 300
        }
        return distancia + 300
    }
    const optionTouch = {
        passive: false
    }
    useEffect(() => {
        setLargura(contentText.current?.clientWidth || 0)
        contentText.current?.addEventListener("mousemove", mouseMove);
        // contentText.current?.addEventListener("touchmove", TouchMove, optionTouch);
    }, [])

    return (
        <div
            className="text-[200px] text-white text-center uppercase font-home py-8"
            ref={contentText}
        >

            <div className="flex justify-center pointer-events-none">
                {'regular'.split('').map((letra, i) =>
                    <span className="pointer-events-none text-[150px] lg:text-[400px] leading-[0.8em]"
                        key={`t_1_${i}_${letra}`}
                        id={`t_1_${i}_${letra}`}
                        style={{
                            textRendering: 'optimizeSpeed',
                            fontVariationSettings: `"wght" ${escala('t_1_' + i + '_' + letra)}, "wdth" ${escala('t_1_' + i + '_' + letra) / 20}, "ital" 0`
                        }}
                    >
                        {letra}
                    </span>
                )}
            </div>
            <div className="flex justify-center pointer-events-none">
                {'switch'.split('').map((letra, i) =>
                    <span className="pointer-events-none text-[150px] lg:text-[400px] leading-[0.8em]"
                        key={`t_2_${i}_${letra}`}
                        id={`t_2_${i}_${letra}`}
                        style={{
                            textRendering: 'optimizeSpeed',
                            fontVariationSettings: `"wght" ${escala('t_2_' + i + '_' + letra)}, "wdth" ${escala('t_2_' + i + '_' + letra) / 20}, "ital" 0`
                        }}
                    >
                        {letra}
                    </span>
                )}
            </div>

        </div>
    )

}



