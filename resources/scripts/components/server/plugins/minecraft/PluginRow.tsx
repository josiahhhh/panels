import React, { useState } from 'react';
import tw from 'twin.macro';
import GreyRowBox from '@/components/elements/GreyRowBox';
import { faDownload, faStar } from '@fortawesome/free-solid-svg-icons';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import { httpErrorToHuman } from '@/api/http';
import PluginInstallButton from '@/components/server/plugins/minecraft/PluginInstallButton';

export interface SpigetResource {
    file: {
        type: string;
        size: number;
        sizeUnit: string;
        url: string;
    };
    likes: number;
    testedVersions: string[];
    links: { [key: string]: string };
    name: string;
    tag: string;
    version: {
        id: string;
    };
    author: {
        id: string;
    };
    category: {
        id: string;
    };
    rating: {
        count: number;
        average: number;
    };
    icon?: {
        url: string;
        data: string;
    };
    releaseDate: number;
    updateDate: number;
    downloads: number;
    premium: boolean;
    existenceStatus: number;
    sourceCodeLink: string;
    id: number;
}

interface Props {
    resource: SpigetResource;
}

export default ({ resource }: Props) => {
    const [error, setError] = useState(null as string | null);

    return (
        <GreyRowBox css={tw`flex-wrap md:flex-nowrap`} className={'plugin'}>
            <div css={tw`flex flex-col w-full h-full`}>
                <div css={tw`flex flex-row justify-between mb-2`}>
                    <div css={tw`flex flex-row items-center`}>
                        {resource.icon?.url && <img src={'https://www.spigotmc.org/' + resource.icon!.url} width={'24px'} height={'24px'} css={tw`mr-2`} />}
                        <p css={tw`text-base break-words`}>{resource.name}</p>
                    </div>
                    <div css={tw`flex flex-row items-center text-sm text-neutral-500`}>
                        <FontAwesomeIcon icon={faStar} css={tw`mr-2`} />
                        <span>{resource.rating.average.toFixed(1)}</span>
                    </div>
                </div>
                <p css={tw`text-xs break-words flex-grow text-neutral-400 mb-4`}>{resource.tag}</p>
                <span css={tw`flex-grow`} />
                <h3 css={tw`text-sm text-neutral-500 font-medium mb-2`}>Tested Versions</h3>
                <div css={tw`flex flex-row`}>
                    {resource.testedVersions.map((version) => (
                        <span key={version} css={tw`py-1 px-2 text-xs font-normal text-neutral-50 bg-icelinebrandcolour-600 mr-2 rounded-lg`}>
                            {version}
                        </span>
                    ))}
                </div>
                <div css={tw`flex flex-row items-center justify-end`}>{error && <span css={tw`text-sm text-red-500 font-medium mb-2 break-all`}>{error}</span>}</div>
                <div css={tw`flex flex-row justify-between items-center mt-4`}>
                    <a css={tw`flex flex-row items-center`} href={`https://www.spigotmc.org/resources/${resource.id}/`} target='_blank' rel='noopener noreferrer'>
                        <img src={'https://static.spigotmc.org/img/spigot-og.png'} width={'32px'} css={tw`mr-2`} />
                        <span css={tw`text-sm font-normal text-neutral-200`}>More Info</span>
                    </a>
                    <div css={tw`flex flex-row items-center`}>
                        <div css={tw`flex flex-row items-center text-sm text-neutral-500 mr-4`}>
                            <FontAwesomeIcon icon={faDownload} css={tw`mr-2`} />
                            <span>{resource.downloads}</span>
                        </div>
                        <PluginInstallButton id={resource.id} name={resource.name} file={resource.file} onError={(error) => setError(httpErrorToHuman(error))} />
                    </div>
                </div>
            </div>
        </GreyRowBox>
    );
};
