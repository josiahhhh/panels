import React from 'react';

import tw from 'twin.macro';

interface Props {
    title: string;
    subtitle?: string;
}

const ActionPrompt: React.FC<Props> = ({ title, subtitle, children }) => {
    return (
        <div>
            <h1 css={tw`text-lg mb-2`}>{title}</h1>
            {subtitle && (
                <h2 css={tw`text-sm text-neutral-400 mb-4`} style={{ maxWidth: '390px' }}>
                    {subtitle}
                </h2>
            )}
            {children}
        </div>
    );
};

export default ActionPrompt;
