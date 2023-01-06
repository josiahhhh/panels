import React, { useState } from 'react';
import tw from 'twin.macro';
import GreyRowBox from '@/components/elements/GreyRowBox';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import { faDownload } from '@fortawesome/free-solid-svg-icons';
import PluginInstallButton from '@/components/server/plugins/rust/PluginInstallButton';
import { httpErrorToHuman } from '@/api/http';
import { RustPlugin } from '@/api/server/iceline/plugins/rust/getRustPlugins';

interface Props {
    plugin: RustPlugin;
}

export default ({ plugin }: Props) => {
    const [error, setError] = useState(null as string | null);

    return (
        <>
            <GreyRowBox css={tw`flex-wrap md:flex-nowrap`} className={'plugin'}>
                <div css={tw`flex flex-col w-full h-full`}>
                    <div css={tw`flex flex-row justify-between mb-2`}>
                        <div css={tw`flex flex-row items-center`}>
                            {plugin.iconURL && <img src={plugin.iconURL} width={'24px'} height={'24px'} css={tw`mr-2`} />}
                            <p css={tw`text-base break-words`}>{plugin.name}</p>
                        </div>
                    </div>
                    <p css={tw`text-xs break-words flex-grow text-neutral-400 mb-4`}>{plugin.description}</p>
                    <span css={tw`flex-grow`} />
                    <div css={tw`flex flex-row items-center justify-end`}>{error && <span css={tw`text-sm text-red-500 font-medium break-all`}>{error}</span>}</div>
                    <div css={tw`flex flex-row justify-between items-center mt-4`}>
                        <a css={tw`flex flex-row items-center`} href={`https://umod.org/plugins/${plugin.slug}/`} target='_blank' rel='noopener noreferrer'>
                            <img src={'https://assets.umod.org/images/umod-gray-nomargin.png'} width={'32px'} css={tw`mr-2`} />
                            <span css={tw`text-sm font-normal text-neutral-200`}>More Info</span>
                        </a>
                        <div css={tw`flex flex-row items-center`}>
                            <div css={tw`flex flex-row items-center text-sm text-neutral-500 mr-4`}>
                                <FontAwesomeIcon icon={faDownload} css={tw`mr-2`} />
                                <span>{plugin.downloadsShortened}</span>
                            </div>
                            <PluginInstallButton plugin={plugin} onError={(error) => setError(httpErrorToHuman(error))} />
                        </div>
                    </div>
                </div>
            </GreyRowBox>
        </>
    );
};
