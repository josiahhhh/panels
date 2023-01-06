import React, { useEffect, useState } from 'react';

import { faClock, faWifi, faInfoCircle, faHeadset, faChartLine, faUser } from '@fortawesome/free-solid-svg-icons';

import { ServerContext } from '@/state/server';

import useWebsocketEvent from '@/plugins/useWebsocketEvent';

import { SocketEvent, SocketRequest } from '@/components/server/events';
import UptimeDuration from '@/components/server/UptimeDuration';
import StatBlock from '@/components/server/console/StatBlock';
import { SubdomainResponse } from '@/components/server/subdomain/SubdomainContainer';
import getServerSubdomains from '@/api/server/subdomain/getServerSubdomains';

import getPlayerCount, { PlayerCount } from '@/api/server/iceline/players/getPlayerCount';

type Stats = Record<'uptime', number>;

export default () => {
    const [stats, setStats] = useState<Stats>({ uptime: 0 });

    const uuid = ServerContext.useStoreState((state) => state.server.data!.uuid);
    const connected = ServerContext.useStoreState((state) => state.socket.connected);
    const instance = ServerContext.useStoreState((state) => state.socket.instance);

    /* const allocation = ServerContext.useStoreState((state) => {
        const match = state.server.data!.allocations.find((allocation) => allocation.isDefault);

        return !match ? 'n/a' : `${match.alias || ip(match.ip)}:${match.port}`;
    });*/

    const [subdomains, setSubdomains] = useState(null as SubdomainResponse | null);

    const primaryAllocation = ServerContext.useStoreState((state) => state.server.data!.allocations.find((alloc) => alloc.isDefault));

    const primaryAllocationName = ServerContext.useStoreState((state) =>
        state.server.data!.allocations.filter((alloc) => alloc.isDefault).map((allocation) => (allocation.alias || allocation.ip) + ':' + allocation.port)
    ).toString();

    const cfxUrl = ServerContext.useStoreState((state) => state.server.data!.cfxUrl);

    const eggName = ServerContext.useStoreState((state) => state.server.data!.egg.name);

    const [connectionAddress, setConnectionAddress] = useState(primaryAllocationName);

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

    useEffect(() => {
        if (!connected || !instance) {
            return;
        }

        instance.send(SocketRequest.SEND_STATS);
    }, [instance, connected]);

    useWebsocketEvent(SocketEvent.STATS, (data) => {
        let stats: any = {};
        try {
            stats = JSON.parse(data);
        } catch (e) {
            return;
        }

        setStats({
            uptime: stats.uptime || 0,
        });
    });

    const [playerCount, setPlayerCount] = useState<PlayerCount | null>(null);

    const getPlayersCount = (uid: string) =>
        getPlayerCount(uid)
            .then((data) => {
                setPlayerCount(data);
            })
            .catch((error) => console.error(error));

    useEffect(() => {
        if (!connected || !instance || !uuid) {
            return;
        }

        const interval = setInterval(() => getPlayersCount(uuid), 20000);

        getPlayersCount(uuid);

        return () => clearInterval(interval);
    }, [instance, connected, uuid]);

    return (
        <div className={'flex flex-col gap-2 md:gap-4'}>
            <StatBlock icon={faClock} title={'Uptime'}>
                {stats.uptime > 0 ? <UptimeDuration uptime={stats.uptime / 1000} /> : 'Offline'}
            </StatBlock>
            <StatBlock icon={faUser} title={'Player Count'}>
                {playerCount ? `${playerCount.current}/${playerCount.max}` : 'Offline'}
            </StatBlock>
            <StatBlock icon={faWifi} title={'Address'} copyOnClick={connectionAddress}>
                {connectionAddress}
            </StatBlock>
            <StatBlock icon={faInfoCircle} title={'Knowledge Base'}>
                <a href={'https://iceline-hosting.com/en/knowledgebase/'} target={'_blank'} rel={'noreferrer noopener'}>
                    Click here
                </a>
            </StatBlock>
            <StatBlock icon={faHeadset} title={'Support'}>
                <a href={'https://iceline-hosting.com/billing/submitticket.php?step=2&deptid=2'} target={'_blank'} rel={'noreferrer noopener'}>
                    Click here
                </a>
            </StatBlock>
            <StatBlock icon={faChartLine} title={'Service Status'}>
                <a href={'https://status.iceline.host/'} target={'_blank'} rel={'noreferrer noopener'}>
                    Click here
                </a>
            </StatBlock>
        </div>
    );
};
