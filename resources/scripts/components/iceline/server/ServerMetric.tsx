import React from 'react';
import tw from 'twin.macro';

interface Props {
    title: string;
    metric: string;
    limit?: string;
}

const ServerMetric: React.FC<Props> = ({ title, metric, limit, children }) => {
    return (
        <div css={tw`flex flex-row items-center overflow-hidden`}>
            <div css={tw`flex flex-col flex-none mr-4 w-20`}>
                <h1 css={tw`text-xs text-neutral-400`}>{title}</h1>
                <h2 css={tw`text-lg text-neutral-50`}>{metric}</h2>
                {limit && <h2 css={tw`text-sm text-neutral-200`}>{limit}</h2>}
            </div>
            <div css={tw`flex-1 overflow-hidden`}>{children}</div>
        </div>
    );
};

export default ServerMetric;
