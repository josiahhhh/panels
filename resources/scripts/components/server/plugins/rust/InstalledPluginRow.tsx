import React, { useState } from 'react';
import tw from 'twin.macro';
import GreyRowBox from '@/components/elements/GreyRowBox';
import { httpErrorToHuman } from '@/api/http';
import { RustPlugin } from '@/api/server/iceline/plugins/rust/getRustPlugins';
import PluginUninstallButton from '@/components/server/plugins/rust/PluginUninstallButton';

interface Props {
    filename: string;
    name: string;
    // version: string
    plugin?: RustPlugin;
}

/* function versionCompare(v1: string, v2: string, options: any | undefined) {
    const lexicographical = options && options.lexicographical;
    const zeroExtend = options && options.zeroExtend;
    let v1parts = v1.split('.') as any;
    let v2parts = v2.split('.') as any;

    function isValidPart(x: string) {
        return (lexicographical ? /^\d+[A-Za-z]*$/ : /^\d+$/).test(x);
    }

    if (!v1parts.every(isValidPart) || !v2parts.every(isValidPart)) {
        return NaN;
    }

    if (zeroExtend) {
        while (v1parts.length < v2parts.length) v1parts.push('0');
        while (v2parts.length < v1parts.length) v2parts.push('0');
    }

    if (!lexicographical) {
        v1parts = v1parts.map(Number);
        v2parts = v2parts.map(Number);
    }

    for (let i = 0; i < v1parts.length; ++i) {
        if (v2parts.length === i) {
            return 1;
        }

        if (v1parts[i] === v2parts[i]) {
            continue;
        } else if (v1parts[i] > v2parts[i]) {
            return 1;
        } else {
            return -1;
        }
    }

    if (v1parts.length !== v2parts.length) {
        return -1;
    }

    return 0;
} */

export default ({ filename, name, plugin }: Props) => {
    const [error, setError] = useState(null as string | null);

    return (
        <>
            <GreyRowBox css={tw`flex-wrap md:flex-nowrap`} className={'plugin'}>
                <div css={tw`flex flex-col w-full h-full`}>
                    <div css={tw`flex flex-row justify-between mb-2`}>
                        <div css={tw`flex flex-row items-center`}>
                            {plugin?.iconURL && <img src={plugin!.iconURL} width={'24px'} height={'24px'} css={tw`mr-2`} />}
                            <p css={tw`text-base break-words`}>{plugin?.name ? plugin.name : name}</p>
                        </div>
                    </div>
                    {plugin?.description && <p css={tw`text-xs break-words flex-grow text-neutral-400 mb-4`}>{plugin.description}</p>}
                    <span css={tw`flex-grow`} />
                    {/*{plugin && plugin.latestReleaseVersion && versionCompare(plugin.latestReleaseVersion, version, undefined) > 0 &&*/}
                    {/*    <div css={tw`mb-4`}>*/}
                    {/*        <MessageBox type="warning" title={plugin?.latestReleaseVersion}>*/}
                    {/*            There is a new version of this plugin available for installation.*/}
                    {/*        </MessageBox>*/}
                    {/*    </div>*/}
                    {/*}*/}
                    <div css={tw`flex flex-row items-center justify-end`}>{error && <span css={tw`text-sm text-red-500 font-medium break-all`}>{error}</span>}</div>
                    <div css={tw`flex flex-row justify-between items-center mt-4`}>
                        <a css={tw`flex flex-row items-center`} href={`https://umod.org/plugins/${plugin?.slug ? plugin.slug : name}/`} target='_blank' rel='noopener noreferrer'>
                            <img src={'https://assets.umod.org/images/umod-gray-nomargin.png'} width={'32px'} css={tw`mr-2`} />
                            <span css={tw`text-sm font-normal text-neutral-200`}>More Info</span>
                        </a>
                        <div css={tw`flex flex-row items-center`}>
                            {/*<div css={tw`flex flex-row items-center text-sm text-neutral-500 mr-4`}>*/}
                            {/*    <span>v{version}</span>*/}
                            {/*</div>*/}
                            <PluginUninstallButton name={name} filename={filename} onError={(error) => setError(httpErrorToHuman(error))} />
                        </div>
                    </div>
                </div>
            </GreyRowBox>
        </>
    );
};
