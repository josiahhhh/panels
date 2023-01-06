import React, { useEffect } from 'react';
import { ServerContext } from '@/state/server';
import PageContentBlock from '@/components/elements/PageContentBlock';
import useFlash from '@/plugins/useFlash';
import tw from 'twin.macro';
import useSWR from 'swr';
import Spinner from '@/components/elements/Spinner';
import GreyRowBox from '@/components/elements/GreyRowBox';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import { faFingerprint } from '@fortawesome/free-solid-svg-icons';
import FlashMessageRender from '@/components/FlashMessageRender';
import MessageBox from '@/components/MessageBox';
import getLogs from '@/api/server/logs/getLogs';
import DeleteButton from '@/components/server/logs/DeleteButton';
import ClearButton from '@/components/server/logs/ClearButton';
import Can from '@/components/elements/Can';

export interface LogsResponse {
    logs: any[];
}

export default () => {
    const uuid = ServerContext.useStoreState((state) => state.server.data!.uuid);

    const { clearFlashes, clearAndAddHttpError } = useFlash();

    const { data, error, mutate } = useSWR<LogsResponse>([uuid, '/logs'], (uuid) => getLogs(uuid), {
        revalidateOnFocus: false,
    });

    useEffect(() => {
        if (!error) {
            clearFlashes('logs');
        } else {
            clearAndAddHttpError({ key: 'logs', error });
        }
    });

    return (
        <PageContentBlock title={'Logs'} css={tw`flex flex-wrap`}>
            <div css={tw`mb-4 flex flex-row items-center`}>
                <Can action={'logs.delete'}>
                    <ClearButton onCleared={() => mutate()} />
                </Can>
            </div>
            <div css={tw`w-full`}>
                <FlashMessageRender byKey={'logs'} css={tw`mb-4`} />
            </div>
            {!data ? (
                <div css={tw`w-full`}>
                    <Spinner size={'large'} centered />
                </div>
            ) : (
                <>
                    <div css={tw`w-full`}>
                        {data.logs.length < 1 ? (
                            <MessageBox type='info' title='Info'>
                                There are no logs.
                            </MessageBox>
                        ) : (
                            data.logs.map((item, key) => (
                                <GreyRowBox css={tw`mb-2`} key={key}>
                                    <div css={tw`hidden md:block`}>
                                        <FontAwesomeIcon icon={faFingerprint} fixedWidth />
                                    </div>
                                    <div css={tw`flex-initial ml-4 text-center`}>
                                        {item.status === 'Success' ? (
                                            <span css={tw`bg-green-500 py-1 px-2 rounded text-white text-xs md:w-full`}>Success</span>
                                        ) : item.status === 'Warning' ? (
                                            <span css={tw`bg-yellow-500 py-1 px-2 rounded text-white text-xs md:w-full`}>Warning</span>
                                        ) : item.status === 'Danger' ? (
                                            <span css={tw`bg-red-500 py-1 px-2 rounded text-white text-xs md:w-full`}>Danger</span>
                                        ) : null}
                                        <p css={tw`mt-1 text-2xs text-neutral-300 uppercase select-none md:w-full`}>Status</p>
                                    </div>
                                    <div css={tw`flex-initial ml-16 text-center`}>
                                        <p css={tw`text-sm`}>{item.module}</p>
                                        <p css={tw`mt-1 text-2xs text-neutral-300 uppercase select-none`}>Module</p>
                                    </div>
                                    <div css={tw`flex-initial ml-16 text-center`}>
                                        <p css={tw`text-sm`}>{item.type}</p>
                                        <p css={tw`mt-1 text-2xs text-neutral-300 uppercase select-none`}>Type</p>
                                    </div>
                                    <div css={tw`flex-1 ml-16 text-center hidden md:block`}>
                                        <p css={tw`text-sm`}>{item.description}</p>
                                        <p css={tw`mt-1 text-2xs text-neutral-300 uppercase select-none`}>Description</p>
                                    </div>
                                    <div css={tw`flex-initial ml-16 text-center hidden md:block`}>
                                        <p css={tw`text-sm`}>{item.created_at}</p>
                                        <p css={tw`mt-1 text-2xs text-neutral-300 uppercase select-none`}>Created</p>
                                    </div>
                                    <div css={tw`flex-initial ml-16 text-center`}>
                                        <DeleteButton logId={item.id} onDeleted={() => mutate()}></DeleteButton>
                                        <p css={tw`mt-1 text-2xs text-neutral-300 uppercase select-none`}>Action</p>
                                    </div>
                                </GreyRowBox>
                            ))
                        )}
                    </div>
                </>
            )}
        </PageContentBlock>
    );
};
