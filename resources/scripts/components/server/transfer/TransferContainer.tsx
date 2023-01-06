import React, { useEffect, useState } from 'react';
import FlashMessageRender from '@/components/FlashMessageRender';
import tw from 'twin.macro';
import ServerContentBlock from '@/components/elements/ServerContentBlock';
import { ServerContext, ServerStatus } from '@/state/server';
import TransferLocationRow from '@/components/server/transfer/TransferLocationRow';
import getTransferLocations, { TransferLocation } from '@/api/server/iceline/transfer/getTransferLocations';
import useFlash from '@/plugins/useFlash';
import { httpErrorToHuman } from '@/api/http';
import Button from '@/components/elements/Button';
import Sticky from 'react-sticky-el';
import initiateTransfer from '@/api/server/iceline/transfer/initiateTransfer';

export default () => {
    const uuid = ServerContext.useStoreState((state) => state.server.data!.uuid);
    const eggName = ServerContext.useStoreState((state) => state.server.data!.egg.name);
    const serverStatus = ServerContext.useStoreState((state) => state.status.value);
    const [delayedStatus, setDelayedStatus] = useState<ServerStatus | ''>('');

    const { addError, clearFlashes } = useFlash();
    const [loading, setLoading] = useState(true);
    const [_, setIsTransferring] = useState(false);

    const [availableLocations, setAvailableLocations] = useState([] as TransferLocation[]);
    const [currentLocation, setCurrentLocation] = useState({} as TransferLocation);
    const [selectedLocation, setSelectedLocation] = useState(null as TransferLocation | null);

    // Hack to prevent warning from briefly flashing up while server connection is loading
    useEffect(() => {
        if (delayedStatus === '') {
            setTimeout(() => {
                setDelayedStatus(serverStatus);
            }, 1000);
        } else {
            setDelayedStatus(serverStatus);
        }
    }, [serverStatus]);

    useEffect(() => {
        setLoading(true);
        clearFlashes('transfer');

        getTransferLocations(uuid)
            .then((res) => {
                setAvailableLocations(res.locations);
                setCurrentLocation(res.current);
            })
            .catch((error) => {
                console.error(error);
                addError({ key: 'transfer', message: httpErrorToHuman(error) });
            })
            .then(() => setLoading(false));
    }, [uuid]);

    const onLocationSelected = (location: TransferLocation) => {
        if (location.id !== currentLocation.id) {
            setSelectedLocation(location);
        }
    };

    const doTransfer = () => {
        if (selectedLocation === null) {
            return;
        }

        setLoading(true);
        clearFlashes('transfer');

        initiateTransfer(uuid, selectedLocation.id)
            .then(() => {
                setIsTransferring(true);
                window.location.reload();
            })
            .catch((error) => {
                console.error(error);
                addError({ key: 'transfer', message: httpErrorToHuman(error) });
            })
            .finally(() => {
                setLoading(false);
            });
    };

    const sortLocations = () => {
        const sorted = availableLocations.sort((a, b) => {
            return (b.ping !== undefined ? b.ping : 0) - (a.ping !== undefined ? a.ping : 0);
        });

        setAvailableLocations(sorted);
    };

    const onPing = (locationId: number) => {
        return (ping: number) => {
            const copy = JSON.parse(JSON.stringify(availableLocations));

            for (let i = 0; i < copy.length; i++) {
                console.log(`copy[i].id (${copy[i].id}) === locationId (${locationId}) = ${copy[i].id === locationId}`);
                if (copy[i].id === locationId) {
                    copy[i].ping = ping;
                }
            }

            setAvailableLocations(copy);
            sortLocations();
        };
    };

    return (
        <>
            <ServerContentBlock title={'Server Transfer'}>
                <FlashMessageRender byKey={'transfer'} css={tw`mb-4`} />
                <div>
                    <div css={tw`grid grid-cols-1 md:grid-cols-2 gap-4 mb-4`}>
                        <div>
                            <h1 css={tw`text-xl font-normal text-neutral-50 mb-4`}>Transfer your server to another location</h1>
                            <p css={tw`text-sm font-normal text-neutral-300`}>
                                Using this page you can transfer your game server to a different location. To get started, you can select any location from the list below to view
                                details about it. Then, if you&apos;re happy with that location, you can click on &quot;Start Transfer&quot; on the right to begin the server
                                transfer.
                            </p>
                            <div css={tw`mt-6`}>
                                <h1 css={tw`text-base font-normal mb-4 text-neutral-50`}>Available Locations</h1>
                                {availableLocations.map(
                                    (location) =>
                                        (!eggName.toLowerCase().includes('proxy') || location.short.toLowerCase().includes('proxy')) && (
                                            <TransferLocationRow
                                                key={location.short}
                                                name={`${location.id}`}
                                                short={location.short}
                                                long={location.long}
                                                current={location.id === currentLocation.id}
                                                selected={location.id === selectedLocation?.id}
                                                onClick={() => onLocationSelected(location)}
                                                address={location.addresses && location.addresses.length > 0 ? location.addresses[0] : undefined}
                                                onPing={onPing(location.id)}
                                                showPing
                                            />
                                        )
                                )}
                            </div>
                        </div>
                        <div>
                            <Sticky>
                                <div>
                                    <div css={tw`rounded p-4 bg-icelinebox-600`}>
                                        <h3 css={tw`text-base text-neutral-300 mb-4`}>Current Location</h3>
                                        <TransferLocationRow css={tw`bg-icelinebox-400`} name={`${currentLocation.id}`} long={currentLocation.long} short={currentLocation.short} />
                                        <h3 css={tw`text-base text-neutral-300 mb-4 mt-6`}>Target Location</h3>
                                        <TransferLocationRow
                                            css={tw`bg-icelinebox-400`}
                                            name={selectedLocation ? `${selectedLocation.id}` : ''}
                                            long={selectedLocation?.long || 'No Location Selected'}
                                            short={selectedLocation?.short || 'Select a location'}
                                        />
                                        <div css={tw`flex flex-row mt-4`}>
                                            {delayedStatus === 'starting' ||
                                            delayedStatus === 'offline' ||
                                            delayedStatus === 'running' ||
                                            delayedStatus === 'stopping' ||
                                            delayedStatus === '' ? (
                                                <>
                                                    <span css={tw`flex-grow`} />
                                                    <Button disabled={selectedLocation === null} onClick={doTransfer} isLoading={loading}>
                                                        Start Transfer
                                                    </Button>
                                                </>
                                            ) : (
                                                <>
                                                    <p css={tw`text-sm font-normal`}>Cannot transfer during outage.</p>
                                                    <span css={tw`flex-grow`} />
                                                    <Button disabled={true}>Start Transfer</Button>
                                                </>
                                            )}
                                        </div>
                                    </div>
                                </div>
                            </Sticky>
                        </div>
                    </div>
                </div>
            </ServerContentBlock>
        </>
    );
};
