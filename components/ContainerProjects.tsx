'use client'

import { HomeProps, Project } from '../types';
import Link from 'next/link';
import Image from 'next/image';
import { useMemo } from 'react';

export default function ContainerProjects({ projects, cats, allMetas }: HomeProps) {
    const getName = (id: number) => cats.find((c: any) => c.id === id)?.title || '';
    const getImageSecondaryBySlug = (slug: string) => allMetas.find((p: any) => slug === p.slug)?.img_secondary?.url || '';
    const sortedProjects = useMemo(() => projects.sort((a, b) => new Date(b.created_at).getTime() - new Date(a.created_at).getTime()), [projects]);

    const createColumnWiseLayout = (items, numColumns) => {
        const columns = Array.from({ length: numColumns }, () => []);
        items.forEach((item, index) => {
            columns[index % numColumns].push(item);
        });
        return columns;
    };

    const columns = createColumnWiseLayout(sortedProjects, 3);

    return (
        <>
            {columns.map((column, columnIndex) => (
                <div key={columnIndex} className="break-inside-avoid pb-4">
                    {column.filter((f: Project) => f.category.includes(17)).map((p) => (
                        <div className="break-inside-avoid pb-4" key={p.id}>
                            <Link href={`/project/${p.slug}`} passHref>
                                <div className="font-hk">
                                    <div className="relative w-full overflow-hidden">
                                        {getImageSecondaryBySlug(p.slug) && (
                                            <Image
                                                alt={p.title}
                                                src={getImageSecondaryBySlug(p.slug)}
                                                width={500}
                                                height={500}
                                                objectFit="cover"
                                                sizes="(max-width: 768px) 100vw, (max-width: 1200px) 50vw, 33vw"
                                                className="absolute top-0 left-0 w-full transition-all duration-300 hover:scale-[1.05] opacity-0 hover:opacity-100"
                                            />
                                        )}
                                        <Image
                                            alt={p.title}
                                            src={p.image_full}
                                            width={500}
                                            height={500}
                                            objectFit="cover"
                                            sizes="(max-width: 768px) 100vw, (max-width: 1200px) 50vw, 33vw"
                                            className="w-full transition-all duration-300 hover:scale-[1.05]"
                                        />
                                    </div>
                                    <div>
                                        <strong className="text-black inline-block mt-4">{p.title}</strong>
                                        <div className="inline-block w-[40px] h-[1px] mb-[6px] mx-[6px] bg-[#FFF]" />
                                        <div dangerouslySetInnerHTML={{ __html: p.more }} />
                                        {p.category &&
                                            p.category.map((id) => (
                                                <span key={id} className="mr-2 text-[#FFF6]">
                                                    #{getName(id)}
                                                </span>
                                            ))}
                                    </div>
                                </div>
                            </Link>
                        </div>
                    ))}
                </div>
            ))}
        </>
    );
}
