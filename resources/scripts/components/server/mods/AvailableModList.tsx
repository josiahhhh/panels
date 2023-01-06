import React, { useEffect } from 'react';

import useSWR from 'swr';

import tw from 'twin.macro';

import { ServerContext } from '@/state/server';
import useFlash from '@/plugins/useFlash';

import Spinner from '@/components/elements/Spinner';
import FlashMessageRender from '@/components/FlashMessageRender';
import MessageBox from '@/components/MessageBox';

import getAvailableModes from '@/api/server/mods/getAvailableMods';

import AvailableMod from '@/components/server/mods/AvailableMod';

export interface AvailableModsResponse {
    mods: any[];
}

interface Props {
    onInstalled: (mod: any) => void;
}

export default ({ onInstalled }: Props) => {
    const uuid = ServerContext.useStoreState((state) => state.server.data!.uuid);

    const { clearFlashes, clearAndAddHttpError } = useFlash();
    const { data, error, mutate } = useSWR<AvailableModsResponse>([uuid, '/mods/available'], (key) => getAvailableModes(key), {
        revalidateOnFocus: false,
    });

    useEffect(() => {
        if (!error) {
            clearFlashes('server:mods');
        } else {
            clearAndAddHttpError({ key: 'server:mods', error });
        }
    }, [error]);

    return (
        <div css={tw`w-full grid gap-4 grid-cols-1 lg:grid-cols-2`}>
            <FlashMessageRender byKey={'server:subdomain'} css={tw`mb-4`} />
            {!data ? (
                <div css={tw`w-full`}>
                    <Spinner size={'large'} centered />
                </div>
            ) : (
                <>
                    {data.mods.length > 0 ? (
                        data.mods.map((item, key) => (
                            <AvailableMod
                                key={key}
                                id={item.id}
                                name={item.name}
                                image={item.image}
                                description={item.description}
                                hasSQL={item.hasSQL}
                                willRestart={item.willRestart > 0}
                                onInstalled={() => {
                                    mutate();
                                    onInstalled(item);
                                }}
                            />
                        ))
                    ) : (
                        <MessageBox type='info' title='Info'>
                            There are no mods available for this server type.
                        </MessageBox>
                    )}
                </>
            )}
        </div>
    );
};
