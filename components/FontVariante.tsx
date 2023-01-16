import React, { useState, useEffect, useRef } from 'react'
function VariableFont({ text, text2 }: any) {
    const [cursor, setCursor] = useState({ x: 0, y: 0 })
    const containerRef = useRef<HTMLDivElement>(null)
    useEffect(() => {
        function handleMouseMove(e: any) {
            setCursor({ x: e.clientX, y: e.clientY })
        }
        function handleTouchMove(e: TouchEvent) {
            const { clientX, clientY } = e.touches[0]
            setCursor({ x: clientX, y: clientY })
        }

        if(containerRef.current) {
            containerRef.current?.addEventListener('mousemove', handleMouseMove)
            containerRef.current?.addEventListener('touchmove', handleTouchMove, { passive: false })
        }

        return () => {
            containerRef.current?.removeEventListener('mousemove', handleMouseMove)
            containerRef.current?.removeEventListener('touchmove', handleTouchMove)
        };
    }, []);

    function getDist(mouse: any, pos: any) {
        const dx = pos?.x - mouse?.x
        const dy = pos?.y - mouse?.y
        return Math.sqrt(dx * dx + dy * dy)
    }

    function getAttr(dist: number, min: number, max: number) {
        const maxDist = 500
        const wght = max - Math.abs((max * dist / maxDist))
        return Math.max(min, wght + min)
    }

    const KeysRef = [
        useRef<HTMLDivElement>(null),
        useRef<HTMLDivElement>(null),
        useRef<HTMLDivElement>(null),
        useRef<HTMLDivElement>(null),
        useRef<HTMLDivElement>(null),
        useRef<HTMLDivElement>(null),
        useRef<HTMLDivElement>(null),
        useRef<HTMLDivElement>(null),
        useRef<HTMLDivElement>(null),
        useRef<HTMLDivElement>(null),
        useRef<HTMLDivElement>(null),
        useRef<HTMLDivElement>(null),
        useRef<HTMLDivElement>(null),
        useRef<HTMLDivElement>(null),
        useRef<HTMLDivElement>(null),
        useRef<HTMLDivElement>(null),
        useRef<HTMLDivElement>(null),
        useRef<HTMLDivElement>(null),
    ]
    
    useEffect(() => {
        KeysRef.map( spanRef => {            
            const span = spanRef.current;
            if( !!span ) {
                const pos = span.getBoundingClientRect();
                const dist = getDist(cursor, {
                    x: pos.x + (pos.width / 1.75),
                    y: pos.y,
                })
                const wdth = ~~getAttr(dist, 5, 200)
                const wght = ~~getAttr(dist, 100, 800)
                const ital = getAttr(dist, 0, 1).toFixed(2)
                // span.style = `font-variation-settings: 'wght' ${wght}, 'wdth' ${wdth}, 'ital' ${ital};`
                span.setAttribute( 'style', `font-variation-settings: 'wght' ${wght}, 'wdth' ${wdth}, 'ital' ${ital};` )
            }
        })
    }, [cursor]);

    const chars = text.split('').map((char:any, index:any) => {
        const spanRef = KeysRef[index]
        return (
            <span
                ref={spanRef}
                key={index}
                data-char={char}
                style={{
                    textRendering: 'optimizeSpeed',
                }}
                className="pointer-events-none text-[150px] lg:text-[400px] leading-[0.8em]"
            >
                {char}
            </span>
        );
    });
        

    return (
        <div ref={containerRef}>
            <div className="text-[200px] text-white text-center uppercase font-home py-8" >
                {chars}
            </div>
        </div>
    );
}
export default VariableFont;