import React, { useEffect, useState } from 'react';
// import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import { Link, NavLink } from 'react-router-dom';
import { Server } from '@/api/server/getServer';
import getServerResourceUsage, { ServerPowerState, ServerStats } from '@/api/server/getServerResourceUsage';
// import { bytesToString, mbToBytes } from '@/lib/formatters';
import tw from 'twin.macro';
import Spinner from '@/components/elements/Spinner';
import styled from 'styled-components/macro';
// import isEqual from 'react-fast-compare';
import ResourceBar from '@/components/iceline/dashboard/ResourceBar';
import getPlayerCount, { PlayerCount } from '@/api/server/iceline/players/getPlayerCount';
import { SubdomainResponse } from '@/components/server/subdomain/SubdomainContainer';
import getServerSubdomains from '@/api/server/subdomain/getServerSubdomains';
import { mbToBytes } from '@/lib/formatters';

// Determines if the current value is in an alarm threshold so we can show it in red rather
// than the more faded default style.
const isAlarmState = (current: number, limit: number): boolean => limit > 0 && current / (limit * 1024 * 1024) >= 0.9;

/* const Icon = memo(
    styled(FontAwesomeIcon)<{ $alarm: boolean }>`
        ${(props) => (props.$alarm ? tw`text-red-400` : tw`text-neutral-200`)};
    `,
    isEqual
); */

/* const IconDescription = styled.p<{ $alarm: boolean }>`
    ${tw`text-sm ml-2`};
    ${(props) => (props.$alarm ? tw`text-white` : tw`text-neutral-200`)};
`; */

const StatusIndicatorBox = styled.div<{ $status: ServerPowerState | undefined }>`
    ${tw`rounded flex uppercase font-bold text-lg`};

    ${({ $status }) => (!$status || $status === 'offline' ? tw`text-red-500` : $status === 'running' ? tw`text-green-500` : tw`text-yellow-500`)};
`;

const ServerActions = styled.div`
    ${tw`grid grid-cols-2 overflow-hidden`}

    & > a {
        ${tw`text-neutral-400`}
        transition: background-color .2x ease-in-out;

        &:hover {
            ${tw`text-neutral-50`}
            background-color: #242843;
        }
    }
`;

export default ({ server }: { server: Server; className?: string }) => {
    const [isSuspended, setIsSuspended] = useState<boolean>(server.status === 'suspended');
    const [stats, setStats] = useState<ServerStats | null>(null);
    const [playerCount, setPlayerCount] = useState<PlayerCount | null>(null);

    const getStats = () =>
        getServerResourceUsage(server.uuid)
            .then((data) => setStats(data))
            .catch((error) => console.error(error));

    const getPlayersCount = () =>
        getPlayerCount(server.uuid)
            .then((data) => setPlayerCount(data))
            .catch((error) => console.error(error));

    useEffect(() => {
        setIsSuspended(stats?.isSuspended || server.status === 'suspended');
    }, [stats?.isSuspended, server.status]);

    useEffect(() => {
        // Don't waste a HTTP request if there is nothing important to show to the user because
        // the server is suspended.
        if (isSuspended) return;

        getStats();

        const interval = setInterval(() => getStats(), 20000);

        return () => clearInterval(interval);
    }, [isSuspended]);

    useEffect(() => {
        // Don't waste a HTTP request if there is nothing important to show to the user because
        // the server is suspended.
        if (isSuspended) return;

        const interval = setInterval(() => getPlayersCount(), 20000);

        getPlayersCount();

        return () => clearInterval(interval);
    }, [isSuspended]);

    const alarms = { cpu: false, memory: false, disk: false };
    if (stats) {
        alarms.cpu = server.limits.cpu === 0 ? false : stats.cpuUsagePercent >= server.limits.cpu * 0.9;
        alarms.memory = isAlarmState(stats.memoryUsageInBytes, server.limits.memory);
        alarms.disk = server.limits.disk === 0 ? false : isAlarmState(stats.diskUsageInBytes, server.limits.disk);
    }

    // const disklimit = server.limits.disk !== 0 ? bytesToString(mbToBytes(server.limits.disk)) : 'Unlimited';
    // const memorylimit = server.limits.memory !== 0 ? bytesToString(mbToBytes(server.limits.memory)) : 'Unlimited';

    const uuid = server.uuid;

    const primaryAllocation = server.allocations.find((alloc) => alloc.isDefault);
    const primaryAllocationName = server.allocations
        .filter((alloc) => alloc.isDefault)
        .map((allocation) => (allocation.alias || allocation.ip) + ':' + allocation.port)
        .toString();

    const cfxURL = server.cfxUrl;

    const [connectionAddress, setConnectionAddress] = useState(primaryAllocationName);
    const [subdomains, setSubdomains] = useState(null as SubdomainResponse | null);

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
        if (cfxURL) {
            setConnectionAddress(cfxURL.replace('https://', '').replace('http://', ''));
            return;
        } else if (server.egg.name.includes('FiveM') || server.egg.name.includes('RedM')) {
            setConnectionAddress('Server offline');
            return;
        }

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

    const cpuLimit = server?.limits.cpu;

    return (
        <div
            css={tw`flex flex-col rounded-lg overflow-hidden`}
            style={{
                backgroundColor: '#171a33',
            }}
        >
            <NavLink to={`/server/${server.id}`}>
                <header
                    css={tw`p-4 flex flex-row justify-between items-center`}
                    style={{
                        backgroundImage: `url(https://cdn.iceline.host/img/headers/game-panel/${server.egg.name.toLowerCase().replace(/\s/g, '_')}.jpg)`,
                        backgroundSize: 'cover',
                        backgroundRepeat: 'no-repeat',
                    }}
                >
                    <div css={tw`flex flex-col`}>
                        <h1 css={tw`text-base font-medium text-neutral-50`}>{server.name}</h1>
                        <h2 css={tw`text-sm font-normal text-neutral-200`}>{connectionAddress}</h2>
                    </div>
                    <StatusIndicatorBox $status={stats?.status}>{stats?.status}</StatusIndicatorBox>
                </header>
            </NavLink>
            <div
                css={tw`p-4 py-5 flex-grow`}
                style={{
                    backgroundColor: '#171a33',
                }}
            >
                {!stats || isSuspended ? (
                    isSuspended ? (
                        <div css={tw`flex-1 text-center`}>
                            <span css={tw`bg-red-500 rounded px-2 py-1 text-red-100 text-xs`}>{isSuspended ? 'Suspended' : 'Connection Error'}</span>
                        </div>
                    ) : server.status === 'installing' ? (
                        <div css={tw`flex-1 text-center`}>
                            <span css={tw`bg-neutral-500 rounded px-2 py-1 text-neutral-200 text-xs`}>Installing</span>
                        </div>
                    ) : (
                        <Spinner size={'small'} />
                    )
                ) : (
                    <React.Fragment>
                        <div css={tw`grid grid-cols-2 gap-4`}>
                            <div css={tw`flex flex-col justify-between`}>
                                <div>
                                    <h3 css={tw`text-neutral-400 text-sm`}>Players</h3>
                                    <h1 css={tw`text-neutral-50 text-base`}>
                                        {stats?.status === 'running' ? (
                                            <>
                                                {playerCount ? playerCount?.current : '?'}/{playerCount ? playerCount?.max : '?'}
                                            </>
                                        ) : (
                                            <>None</>
                                        )}
                                    </h1>
                                </div>
                                <div>
                                    <h3 css={tw`text-neutral-400 text-sm`}>Game Type</h3>
                                    <h1 css={tw`text-neutral-50 text-base`}>{server.egg.name}</h1>
                                </div>
                                <div>
                                    <h3 css={tw`text-neutral-400 text-sm`}>Node ID</h3>
                                    <h1 css={tw`text-neutral-50 text-base`}>{server.node}</h1>
                                </div>
                            </div>
                            <div>
                                <ResourceBar label={'CPU'} value={cpuLimit && cpuLimit <= 0 ? stats.cpuUsagePercent : stats.cpuUsagePercent * (100 / (cpuLimit || 100))} />
                                <ResourceBar
                                    label={'RAM'}
                                    value={
                                        (stats.memoryUsageInBytes / mbToBytes(server.limits.memory)) * 100 > 100
                                            ? 100
                                            : (stats.memoryUsageInBytes / mbToBytes(server.limits.memory)) * 100
                                    }
                                />
                                <ResourceBar
                                    label={'DISK'}
                                    value={
                                        (stats.diskUsageInBytes / mbToBytes(server.limits.disk)) * 100 > 100 ? 100 : (stats.diskUsageInBytes / mbToBytes(server.limits.disk)) * 100
                                    }
                                />
                            </div>
                        </div>
                    </React.Fragment>
                )}
            </div>
            <ServerActions css={tw`grid grid-cols-2`}>
                <Link css={tw`p-4 flex flex-row items-center justify-center text-base text-neutral-200 cursor-pointer`} to={`/server/${server.id}`}>
                    Console
                </Link>
                <Link css={tw`p-4 flex flex-row items-center justify-center text-base text-neutral-200 cursor-pointer`} to={`/server/${server.id}/files`}>
                    Files
                </Link>
            </ServerActions>
        </div>
    );
};
