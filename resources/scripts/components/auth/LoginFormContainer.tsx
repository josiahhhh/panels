import React, { forwardRef } from 'react';
import { Form } from 'formik';
// import styled from 'styled-components/macro';
// import { breakpoint } from '@/theme';
import FlashMessageRender from '@/components/FlashMessageRender';
import tw from 'twin.macro';

type Props = React.DetailedHTMLProps<React.FormHTMLAttributes<HTMLFormElement>, HTMLFormElement> & {
    title?: string;
};

/* const Container = styled.div`
    ${breakpoint('sm')`
        ${tw`w-4/5 mx-auto`}
    `};

    ${breakpoint('md')`
        ${tw`p-10`}
    `};

    ${breakpoint('lg')`
        ${tw`w-3/5`}
    `};

    ${breakpoint('xl')`
        ${tw`w-full`}
        max-width: 700px;
    `};
`; */

export default forwardRef<HTMLFormElement, Props>(({ title, ...props }, ref) => (
    <div
        css={tw`h-full flex flex-col justify-evenly`}
        style={{
            background: 'rgba(14, 16, 31, 1)',
            maxWidth: '450px',
        }}
    >
        {title && <h2 css={tw`text-3xl text-center text-neutral-100 font-medium py-4`}>{title}</h2>}
        <div>
            <FlashMessageRender css={tw`mb-2 px-1`} />
            <Form {...props} ref={ref}>
                <div css={tw`w-full px-4`}>{props.children}</div>
            </Form>
        </div>
        <p css={tw`text-center text-neutral-500 text-xs mt-4`}>
            &copy; 2015 - {new Date().getFullYear()}&nbsp;
            <a rel={'noopener nofollow noreferrer'} href={'https://pterodactyl.io'} target={'_blank'} css={tw`no-underline text-neutral-500 hover:text-neutral-300`}>
                Iceline Hosting
            </a>
        </p>
    </div>
));
