import React, { useEffect, useState } from 'react';

import tw, { theme as th } from 'twin.macro';
import styled from 'styled-components/macro';

import ReactTooltip from 'react-tooltip';

import { ServerContext } from '@/state/server';
import useEventListener from '@/plugins/useEventListener';
import SearchModal from '@/components/dashboard/search/SearchModal';
import CopyOnClick from '@/components/elements/CopyOnClick';
import { SubdomainResponse } from '@/components/server/subdomain/SubdomainContainer';
import getServerSubdomains from '@/api/server/subdomain/getServerSubdomains';
import Button from '@/components/elements/Button';
import PowerButtons from '@/components/server/console/PowerButtons';

const ServerHeaderNav = styled.nav`
    ${tw`flex flex-col w-full py-6`}

    & > div {
        ${tw`mx-auto w-full px-4`};
        max-width: 1200px;
    }
`;

export default () => {
    const [searchVisible, setSearchVisible] = useState(false);

    const status = ServerContext.useStoreState((state) => state.status.value);
    const name = ServerContext.useStoreState((state) => state.server.data?.name);

    const egg = ServerContext.useStoreState((state) => state.server.data?.egg);

    const [subdomains, setSubdomains] = useState(null as SubdomainResponse | null);

    useEventListener('keydown', (e: KeyboardEvent) => {
        if (['input', 'textarea'].indexOf(((e.target as HTMLElement).tagName || 'input').toLowerCase()) < 0) {
            if (!searchVisible && e.key.toLowerCase() === 'k') {
                setSearchVisible(true);
            }
        }
    });

    const primaryAllocation = ServerContext.useStoreState((state) => state.server.data!.allocations.find((alloc) => alloc.isDefault));

    const primaryAllocationName = ServerContext.useStoreState((state) =>
        state.server.data!.allocations.filter((alloc) => alloc.isDefault).map((allocation) => (allocation.alias || allocation.ip) + ':' + allocation.port)
    ).toString();

    const cfxUrl = ServerContext.useStoreState((state) => state.server.data!.cfxUrl);
    const eggName = ServerContext.useStoreState((state) => state.server.data!.egg.name);

    const [connectionAddress, setConnectionAddress] = useState(primaryAllocationName);

    const uuid = ServerContext.useStoreState((state) => state.server.data!.uuid);

    useEffect(() => {
        getServerSubdomains(uuid)
            .then((subdomains) => {
                setSubdomains(subdomains);
            })
            .catch((error) => {
                console.error(error);
            });
    }, [uuid]);

    useEffect(() => {
        if (cfxUrl) {
            setConnectionAddress(cfxUrl.replace('https://', '').replace('http://', ''));
            console.log('using cfx address');
            return;
        } else if (eggName.includes('FiveM') || eggName.includes('RedM')) {
            setConnectionAddress('Server offline');
            return;
        }
        console.log("eggName.includes('FiveM') || eggName.includes('RedM')", eggName.includes('FiveM'), eggName.includes('RedM'));

        if (!primaryAllocation) {
            setConnectionAddress(primaryAllocationName);
            return;
        }

        if (!subdomains) {
            setConnectionAddress(primaryAllocationName);
            return;
        }

        const primarySubdomain = subdomains.subdomains.find(
            (subdomain: {
                domain: string;
                subdomain: string;
                port: number;
                // eslint-disable-next-line camelcase
                record_type: string;
            }) => subdomain.port === primaryAllocation.port
        ) as {
            domain: string;
            subdomain: string;
            port: number;
            // eslint-disable-next-line camelcase
            record_type: string;
        };

        if (primarySubdomain) {
            if (primarySubdomain.record_type.toLowerCase() === 'srv') {
                setConnectionAddress(`${primarySubdomain.subdomain}.${primarySubdomain.domain}`);
            } else {
                setConnectionAddress(`${primarySubdomain.subdomain}.${primarySubdomain.domain}:${primarySubdomain.port}`);
            }
        }
    }, [primaryAllocation, subdomains]);

    const fivemConnect = () => {
        window.location.href = cfxUrl ? 'fivem://connect/' + cfxUrl.replace('https://', '').replace('http://', '') : `fivem://${primaryAllocation?.ip}:${primaryAllocation?.port}`;
    };

    const steamConnect = () => {
        window.location.href = `steam://connect/${primaryAllocation?.ip}:${primaryAllocation?.port}`;
    };

    return (
        <>
            {searchVisible && <SearchModal appear visible={searchVisible} onDismissed={() => setSearchVisible(false)} />}
            <ServerHeaderNav>
                <div css={tw`pt-10 sm:p-0`}>
                    <ReactTooltip place={'bottom'} effect={'solid'} textColor={th`colors.neutral.100`.toString()} backgroundColor={th`colors.icelinebox.500`.toString()} />
                    <div css={tw`flex flex-row justify-between items-center`}>
                        <div>
                            <h1 css={tw`text-2xl text-neutral-50 font-bold flex flex-row items-center`}>
                                <span css={tw`mr-3`}>{name}</span>
                                <span
                                    css={tw`w-4 h-4 rounded-full`}
                                    style={{
                                        backgroundColor:
                                            status === 'running'
                                                ? th`colors.green.600`.toString()
                                                : status === 'offline'
                                                ? th`colors.red.600`.toString()
                                                : th`colors.yellow.600`.toString(),
                                        marginTop: '2.5px',
                                    }}
                                />
                            </h1>
                            <CopyOnClick text={connectionAddress}>
                                <h2 css={tw`text-sm mb-4`} style={{ color: '#BEC0D7' }}>
                                    {connectionAddress}
                                </h2>
                            </CopyOnClick>
                        </div>
                        <div className='flex space-x-4 items-center'>
                            <PowerButtons className={'flex sm:justify-end space-x-2'} />
                            {egg?.name === 'FiveM' && (
                                <Button
                                    onClick={fivemConnect}
                                    data-tip={"Launch FiveM and connect to your server. This will not work if you don't have FiveM installed."}
                                    className='text-xs'
                                >
                                    Connect
                                </Button>
                            )}
                            {(egg?.name === 'Garrys Mod' || egg?.name === 'Ark Survival Evolved') && (
                                <Button
                                    onClick={steamConnect}
                                    data-tip={"Launch Steam and connect to your server. This will not work if you don't have Steam installed."}
                                    className='text-xs'
                                >
                                    Connect
                                </Button>
                            )}
                        </div>
                    </div>
                    {/*<a*/}
                    {/*    css={tw`flex flex-row items-center text-sm cursor-pointer`}*/}
                    {/*    style={{ color: '#9092a7' }}*/}
                    {/*    onClick={() => setSearchVisible(true)}*/}
                    {/*>*/}
                    {/*    <img css={tw`mr-2`} src={'/assets/iceline/servers/search.svg'} alt={'search'} />*/}
                    {/*    <span>Search</span>*/}
                    {/*</a>*/}
                </div>
            </ServerHeaderNav>
        </>
    );
};
