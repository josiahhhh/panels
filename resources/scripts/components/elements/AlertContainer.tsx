import React from 'react';
import MessageBox from '@/components/MessageBox';
import tw from 'twin.macro';
import { CSSTransition } from 'react-transition-group';
import ContentContainer from '@/components/elements/ContentContainer';
import { ServerContext } from '@/state/server';

export default () => {
    const alerts = ServerContext.useStoreState((state) => state.server.data!.alerts);

    return (
        <>
            {alerts.length > 0 && (
                <CSSTransition timeout={150} classNames={'fade'} appear in>
                    <ContentContainer css={tw`my-4 sm:my-10`}>
                        {alerts.map((item, key) => (
                            <React.Fragment key={key}>
                                {key > 0 && <div css={tw`mt-2`} />}
                                <MessageBox type={item.type} title={'INFO'}>
                                    {item.message}
                                </MessageBox>
                            </React.Fragment>
                        ))}
                    </ContentContainer>
                </CSSTransition>
            )}
        </>
    );
};
