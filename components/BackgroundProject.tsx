import React, { useEffect, forwardRef } from 'react';
import Image from 'next/image';
import { FastAverageColor } from 'fast-average-color';

const BackgroundProject = forwardRef<
    HTMLDivElement,
    { bg: string; video?: string; visible: boolean; onColorExtract: (color: string) => void }
>(({ bg, video, visible, onColorExtract }, ref) => {

    useEffect(() => {
        const fac = new FastAverageColor();
        const isVideo = bg?.endsWith('.mp4');
        if (!isVideo) {
            const fac = new FastAverageColor();
            fac.getColorAsync(bg).then((color) => {
                const luminance = (0.299 * color.value[0] + 0.587 * color.value[1] + 0.114 * color.value[2]) / 255;
                onColorExtract(luminance > 0.52 ? 'black' : 'white');
            }).catch((error) => console.error('FastAverageColor Error:', error));
        }

        return () => fac.destroy();
    }, [bg, video, onColorExtract]);

    return (
        <div ref={ref} className={`transition-opacity duration-300 fixed top-0 left-0 w-full z-[-1] ${visible ? 'opacity-100' : 'opacity-0'} bg-container`}>
            <div
                className={`relative w-full overflow-hidden h-56 md:h-auto lg:h-auto`}
            >
                {!video && (
                    <Image
                        alt="Background"
                        src={bg}
                        width={1920}
                        height={1080}
                        layout="responsive"
                        objectFit="contain"
                        priority
                    />
                )}
                {video && (
                    <video
                        src={video}
                        muted
                        autoPlay
                        loop
                        className="w-full h-auto object-contain"
                    />
                )}
            </div>
        </div>
    );
});

BackgroundProject.displayName = "BackgroundProject";

export default BackgroundProject;
