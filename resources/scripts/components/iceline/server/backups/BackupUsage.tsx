import React from 'react';
import tw, { theme as th } from 'twin.macro';
import ReactTooltip from 'react-tooltip';

interface Props {
    fileUsed: number;
    databaseUsed: number;
    limitGB: number;
}

export default ({ fileUsed, databaseUsed, limitGB }: Props) => {
    return (
        <div css={tw`mb-6`}>
            <ReactTooltip place={'bottom'} effect={'solid'} textColor={th`colors.neutral.100`.toString()} backgroundColor={th`colors.icelinebox.500`.toString()} />
            <div css={tw`flex flex-row justify-between mb-2`}>
                <h1 css={tw`text-sm text-neutral-300`}>Backup Usage</h1>
                <h3 css={tw`text-sm text-neutral-400`}>
                    {(limitGB * ((fileUsed + databaseUsed) / 100)).toFixed(2)}/{limitGB}GB
                </h3>
            </div>
            <div css={tw`flex flex-row rounded-lg overflow-hidden w-full bg-icelinebox-800 h-5`}>
                <div
                    style={{
                        backgroundColor: '#2986dd',
                        width: fileUsed.toFixed(2) + '%',
                    }}
                    data-tip={`File Backups Usage: ${(limitGB * (fileUsed / 100)).toFixed(2)}GB`}
                />
                <div
                    style={{
                        backgroundColor: '#794edd',
                        width: databaseUsed.toFixed(2) + '%',
                    }}
                    data-tip={`Database Backups Usage: ${(limitGB * (databaseUsed / 100)).toFixed(2)}GB`}
                />
            </div>
        </div>
    );
};
