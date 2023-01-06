import React, { useEffect } from 'react';

import useSWR from 'swr';

import tw from 'twin.macro';

import ServerContentBlock from '@/components/elements/ServerContentBlock';
import { ServerContext } from '@/state/server';
import useFlash from '@/plugins/useFlash';

import Spinner from '@/components/elements/Spinner';
import FlashMessageRender from '@/components/FlashMessageRender';
import MessageBox from '@/components/MessageBox';

import getServerMods from '@/api/server/mods/getServerMods';

import InstalledMod from '@/components/server/mods/InstalledMod';

import AvailableModList from '@/components/server/mods/AvailableModList';
import getServerDatabases from '@/api/server/databases/getServerDatabases';

export interface ModsResponse {
    mods: any[];
}

export default () => {
    const uuid = ServerContext.useStoreState((state) => state.server.data!.uuid);

    const { clearFlashes, clearAndAddHttpError } = useFlash();
    const { data, error, mutate } = useSWR<ModsResponse>([uuid, '/mods'], (key) => getServerMods(key), {
        revalidateOnFocus: false,
    });

    useEffect(() => {
        if (!error) {
            clearFlashes('server:mods');
        } else {
            clearAndAddHttpError({ key: 'server:mods', error });
        }
    }, [error]);

    const setDatabases = ServerContext.useStoreActions((state) => state.databases.setDatabases);
    useEffect(() => {
        clearFlashes('databases');

        getServerDatabases(uuid)
            .then((databases) => setDatabases(databases))
            .catch((error) => {
                console.error(error);
            });
    }, []);

    return (
        <ServerContentBlock title={'Mods'} css={tw`flex flex-wrap`}>
            <FlashMessageRender byKey={'server:mods'} css={tw`mb-4`} />
            <h1 css={tw`w-full text-lg mb-4`}>Installed Mods</h1>
            {!data ? (
                <div css={tw`w-full`}>
                    <Spinner size={'large'} centered />
                </div>
            ) : (
                <div css={tw`w-full grid gap-4 grid-cols-1 lg:grid-cols-2`}>
                    {data.mods.length > 0 ? (
                        data.mods.map((item, key) => (
                            <InstalledMod
                                key={key}
                                id={item.id}
                                name={item.name}
                                description={item.description}
                                installing={item.installing}
                                installError={item.installError}
                                onDeleted={() => mutate()}
                                image={item.image}
                            />
                        ))
                    ) : (
                        <MessageBox type='info' title='Info'>
                            There are no mods installed on the current server.
                        </MessageBox>
                    )}
                </div>
            )}
            <h1 css={tw`w-full text-lg my-4`}>Available Mods</h1>
            <AvailableModList onInstalled={() => mutate()} />
        </ServerContentBlock>
    );
};
