import React from 'react';
import TitledGreyBox from '@/components/elements/TitledGreyBox';
import tw from 'twin.macro';
import ServerContentBlock from '@/components/elements/ServerContentBlock';
import getServerStartup from '@/api/swr/getServerStartup';
import Spinner from '@/components/elements/Spinner';
import { ServerError } from '@/components/elements/ScreenBlock';
import { httpErrorToHuman } from '@/api/http';
import { ServerContext } from '@/state/server';
import isEqual from 'react-fast-compare';
import Button from '@/components/elements/Button';
import { useHistory } from 'react-router';

const SetupContainer = () => {
    const history = useHistory();

    const uuid = ServerContext.useStoreState((state) => state.server.data!.uuid);
    const variables = ServerContext.useStoreState(
        ({ server }) => ({
            variables: server.data!.variables,
            invocation: server.data!.invocation,
            dockerImage: server.data!.dockerImage,
        }),
        isEqual
    );

    const { data, error, isValidating, mutate } = getServerStartup(uuid, {
        ...variables,
        dockerImages: { [variables.dockerImage]: variables.dockerImage },
    });

    const primaryAllocation = ServerContext.useStoreState((state) => state.server.data!.allocations.find((alloc) => alloc.isDefault));

    return !data ? (
        !error || (error && isValidating) ? (
            <Spinner centered size={Spinner.Size.LARGE} />
        ) : (
            <ServerError title={'Oops!'} message={httpErrorToHuman(error)} onRetry={() => mutate()} />
        )
    ) : (
        <ServerContentBlock title={'Proxy Setup'} showFlashKey={'startup:image'}>
            <div css={tw`flex flex-col space-y-8`}>
                <div>
                    <TitledGreyBox title={"1. Add the following to your FiveM server's server.cfg"} css={tw`flex-1`}>
                        <div css={tw`px-1 py-2`}>
                            <div css={tw`font-mono bg-icelinebox-700 rounded py-2 px-4`}>
                                <span css={tw`block`}>set sv_forceIndirectListing true</span>
                                <span css={tw`block`}>
                                    set sv_proxyIPRanges &quot;{primaryAllocation ? primaryAllocation.ip : '???'}
                                    /32&quot;
                                </span>
                                <span css={tw`block`}>
                                    set sv_listingIpOverride &quot;{primaryAllocation ? primaryAllocation.ip : '???'}:{primaryAllocation ? primaryAllocation.port : '?????'}&quot;
                                </span>
                            </div>
                        </div>
                    </TitledGreyBox>
                </div>
                <div>
                    <h1 css={tw`mb-4`}>2. Enter the IP address and port of the FiveM server you&apos;re protecting on the startup tab</h1>
                    <Button
                        size={'small'}
                        css={tw`mr-2`}
                        onClick={(e) => {
                            e.preventDefault();

                            history.push('/server/' + uuid + '/startup');
                        }}
                    >
                        Go to Startup Tab
                    </Button>
                </div>
                <h1>3. Restart the proxy and then your FiveM server</h1>
                <div>
                    <TitledGreyBox title={'4. Use the following IP to connect to your FiveM server'} css={tw`flex-1`}>
                        <div css={tw`px-1 py-2`}>
                            <p css={tw`font-mono bg-icelinebox-700 rounded py-2 px-4`}>
                                {primaryAllocation ? primaryAllocation.ip : '???'}:{primaryAllocation ? primaryAllocation.port : '?????'}
                            </p>
                        </div>
                    </TitledGreyBox>
                </div>
            </div>
        </ServerContentBlock>
    );
};

export default SetupContainer;
