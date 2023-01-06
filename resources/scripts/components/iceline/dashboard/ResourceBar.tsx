import React from 'react';
import tw from 'twin.macro';
import styled from 'styled-components/macro';

interface Props {
    label: string;
    value: number;
}

const Container = styled.div`
    &:not(:last-of-type) {
        ${tw`mb-4`}
    }
`;

export default ({ label, value }: Props) => {
    return (
        <Container>
            <h3 css={tw`text-neutral-400 text-sm mb-1`}>{label}</h3>
            <div
                css={tw`h-3 rounded-full`}
                style={{
                    backgroundColor: '#121529',
                }}
            >
                <div
                    css={tw`h-full rounded-full max-w-full`}
                    style={{
                        background: 'linear-gradient(to right, #29BDDD 0%, #923DDD 100%)',
                        width: `${value}%`,
                    }}
                />
            </div>
        </Container>
    );
};
