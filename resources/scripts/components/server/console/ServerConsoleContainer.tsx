import React, { memo } from 'react';
import { ServerContext } from '@/state/server';
import ServerContentBlock from '@/components/elements/ServerContentBlock';
import isEqual from 'react-fast-compare';
import Spinner from '@/components/elements/Spinner';
import Features from '@feature/Features';
import Console from '@/components/server/console/Console';
import { Alert } from '@/components/elements/alert';
import ServerMetrics from '@/components/iceline/server/ServerMetrics';
import ConsoleSidebar from '@/components/iceline/server/ConsoleSidebar';
import PlayerList from '@/components/iceline/server/PlayerList';

export type PowerAction = 'start' | 'stop' | 'restart' | 'kill';

const ServerConsoleContainer = () => {
    const isInstalling = ServerContext.useStoreState((state) => state.server.isInstalling);
    const isTransferring = ServerContext.useStoreState((state) => state.server.data!.isTransferring);
    const eggFeatures = ServerContext.useStoreState((state) => state.server.data!.eggFeatures, isEqual);

    return (
        <ServerContentBlock title={'Console'} className={'flex flex-col gap-2 sm:gap-4'}>
            {(isInstalling || isTransferring) && (
                <Alert type={'warning'}>
                    {isInstalling
                        ? 'This server is currently running its installation process and most actions are unavailable.'
                        : 'This server is currently being transferred to another node and all actions are unavailable.'}
                </Alert>
            )}
            <ServerMetrics />
            {/*<div className={'grid grid-cols-4 gap-4'}>*/}
            {/*    <div className={'hidden sm:block sm:col-span-2 lg:col-span-3 pr-4'}>*/}
            {/*        <h1 className={'font-header text-2xl text-gray-50 leading-relaxed line-clamp-1'}>{name}</h1>*/}
            {/*        <p className={'text-sm line-clamp-2'}>{description}</p>*/}
            {/*    </div>*/}
            {/*    <div className={'col-span-4 sm:col-span-2 lg:col-span-1 self-end'}>*/}
            {/*        <Can action={['control.start', 'control.stop', 'control.restart']} matchAny>*/}
            {/*            <PowerButtons className={'flex sm:justify-end space-x-2'} />*/}
            {/*        </Can>*/}
            {/*    </div>*/}
            {/*</div>*/}
            <div className={'grid grid-cols-4 gap-2 sm:gap-4'}>
                <div className={'col-span-4 lg:col-span-3'}>
                    <Spinner.Suspense>
                        <Console />
                    </Spinner.Suspense>
                </div>
                <div className={'col-span-4 lg:col-span-1 order-last lg:order-none'}>
                    <ConsoleSidebar />
                </div>
                {/*<ServerDetailsBlock className={'col-span-4 lg:col-span-1 order-last lg:order-none'} />*/}
            </div>
            {/*<div className={'grid grid-cols-1 md:grid-cols-3 gap-2 sm:gap-4'}>*/}
            {/*    <Spinner.Suspense>*/}
            {/*        <StatGraphs />*/}
            {/*    </Spinner.Suspense>*/}
            {/*</div>*/}
            <PlayerList />
            <Features enabled={eggFeatures} />
        </ServerContentBlock>
    );
};

export default memo(ServerConsoleContainer, isEqual);
