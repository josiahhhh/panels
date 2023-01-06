import React, { memo } from 'react';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import { IconProp } from '@fortawesome/fontawesome-svg-core';
import tw from 'twin.macro';
import isEqual from 'react-fast-compare';

interface Props {
    icon?: IconProp;
    title: string | React.ReactNode;
    className?: string;
    children: React.ReactNode;
}

const TitledGreyBox = ({ icon, title, children, className }: Props) => (
    <div className={className}>
        <div css={tw`mb-4`}>
            {typeof title === 'string' ? (
                <p css={tw`text-base`}>
                    {icon && <FontAwesomeIcon icon={icon} css={tw`mr-2 text-neutral-200`} />}
                    {title}
                </p>
            ) : (
                title
            )}
        </div>
        <div css={tw`rounded shadow-md bg-icelinebox-500 p-3`}>{children}</div>
    </div>
);

export default memo(TitledGreyBox, isEqual);
