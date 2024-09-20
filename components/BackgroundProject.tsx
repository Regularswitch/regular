import React, { useState, useEffect } from 'react';
import { FastAverageColor } from 'fast-average-color';
import Image from 'next/image';

const BackgroundProject: React.FC<{ bg: string, video?: string, visible: boolean, onChangeTextColor: (color: string) => void }> = ({ bg, video, visible, onChangeTextColor }) => {
    useEffect(() => {
        const fac = new FastAverageColor();
        fac.getColorAsync(bg, { crossOrigin: 'Anonymous' })
          .then((color) => {
            const luminance = (0.299 * color.value[0] + 0.587 * color.value[1] + 0.114 * color.value[2]) / 255;
    console.log(luminance);
            if (luminance > 0.5) {
              onChangeTextColor('black');
            } else {
              onChangeTextColor('white');
            }
          })
          .catch((e) => {
            console.error('Error getting average color', e);
          });
    
        return () => fac.destroy();
      }, [bg, onChangeTextColor]);

    return (
        <div className={`transition-opacity duration-300 fixed top-0 left-0 w-full z-[-1] ${visible ? 'opacity-100' : 'opacity-0'} bg-container`}>
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
    );
};

export default BackgroundProject;