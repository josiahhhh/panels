import React, { useEffect, useState } from 'react';
import getPlayerCount, { Player } from '@/api/server/iceline/players/getPlayerCount';
import { ServerContext } from '@/state/server';
import tw from 'twin.macro';
import styled from 'styled-components/macro';
import FlashMessageRender from '@/components/FlashMessageRender';
import PlayerManagerAskModal from '@/components/iceline/server/PlayerManagerAskModal';
import useFlash from '@/plugins/useFlash';

// eslint-disable-next-line @typescript-eslint/no-empty-interface
interface Props {}

const PlayerTable = styled.table<Props>`
    ${tw`rounded bg-icelinebox-500 max-h-48 overflow-y-auto`}

    td {
        ${tw`py-2 px-4`}
    }

    & > thead {
        & > tr {
            & > td {
                ${tw`text-neutral-50 text-sm`}
            }
        }
    }

    & > tbody {
        & > tr {
            & > td {
                ${tw`text-neutral-200`}
            }
        }
    }
`;

export default () => {
    const [players, setPlayers] = useState<Player[]>([]);

    const { clearFlashes } = useFlash();

    const getPlayers = (uid: string) =>
        getPlayerCount(uid)
            .then((data) => setPlayers(data?.players || []))
            .catch((error) => console.error(error))
            .finally(() => clearFlashes('server:players'));

    const uuid = ServerContext.useStoreState((state) => state.server.data?.uuid);
    const eggName = ServerContext.useStoreState((state) => state.server.data?.egg) ?? { name: '' };

    useEffect(() => {
        if (!uuid) {
            return;
        }

        const interval = setInterval(() => getPlayers(uuid), 20000);
        getPlayers(uuid);

        return () => clearInterval(interval);
    }, [uuid]);

    return (
        <>
            <FlashMessageRender key={'server:players'} css={tw`pb-0`} />
            <div css={tw`rounded bg-icelinebox-500 w-full max-h-[1024px] overflow-y-auto`}>
                <PlayerTable>
                    <thead>
                        <tr>
                            {/*{players.length > 0 && players[0].id !== undefined && <td>ID</td>}*/}
                            {eggName?.name.includes('Minecraft') && <td>Head</td>}
                            <td>Name</td>
                            {players.length > 0 && players[0].metadata && Object.keys(players[0].metadata).map((keyName) => <td key={keyName}>{keyName}</td>)}
                            {(eggName?.name.includes('Minecraft') || eggName?.name.includes('FiveM') || eggName?.name.includes('RedM')) && <td>Actions</td>}
                        </tr>
                    </thead>
                    <tbody>
                        {players.map((player) => (
                            <tr key={player.id}>
                                {/*{player.id !== undefined && <td css={tw`truncate`}>{player.id}</td>}*/}
                                {eggName?.name.includes('Minecraft') && (
                                    <td>
                                        <img src={`//crafatar.com/avatars/${player.id}`} alt={'Player Skin'} width={48} />
                                    </td>
                                )}
                                <td>{player.name}</td>
                                {player.metadata && Object.keys(player.metadata).map((keyName) => <td key={keyName}>{player.metadata[keyName]}</td>)}
                                {eggName?.name.includes('Minecraft') && (
                                    <td>
                                        <PlayerManagerAskModal
                                            buttonColor={player.isOp ? 'red' : 'green'}
                                            buttonText={player.isOp ? 'DEOP' : 'OP'}
                                            title={`${player.isOp ? 'DEOP' : 'OP'} Player`}
                                            message={`Are you sure that you want to ${player.isOp ? 'deop' : 'op'} <b>${player.name}</b>?`}
                                            command={`${player.isOp ? 'deop' : 'op'} ${player.name}`}
                                            onPerformed={(uuid) => getPlayers(uuid)}
                                        />
                                        <PlayerManagerAskModal
                                            buttonColor={player.inWhitelist ? 'grey' : 'primary'}
                                            buttonText={player.inWhitelist ? 'Remove from Whitelist' : 'Add to Whitelist'}
                                            title={player.inWhitelist ? 'Remove Player from Whitelist' : 'Add Player to Whitelist'}
                                            message={`Are you sure that you want to ${player.inWhitelist ? 'remove' : 'add'} <b>${player.name}</b> ${
                                                player.inWhitelist ? 'from' : 'to'
                                            } the whitelist?`}
                                            command={`whitelist ${player.inWhitelist ? 'remove' : 'add'} ${player.name}`}
                                            onPerformed={(uuid) => getPlayers(uuid)}
                                        />
                                        <PlayerManagerAskModal
                                            buttonColor={'red'}
                                            buttonSecondary
                                            buttonText={'Kick'}
                                            title={'Kick Player'}
                                            message={`Are you sure that you want to kick <b>${player.name}</b>?`}
                                            command={`kick ${player.name}`}
                                            onPerformed={(uuid) => getPlayers(uuid)}
                                        />
                                        <PlayerManagerAskModal
                                            buttonColor={'red'}
                                            buttonText={'Ban'}
                                            title={'Ban Player'}
                                            message={`Are you sure that you want to ban <b>${player.name}</b>?`}
                                            command={`ban ${player.name}`}
                                            onPerformed={(uuid) => getPlayers(uuid)}
                                        />
                                        <PlayerManagerAskModal
                                            buttonColor={'red'}
                                            buttonSecondary
                                            buttonText={'Ban IP'}
                                            title={'Ban IP'}
                                            message={`Are you sure that you want to ip ban <b>${player.name}</b>?`}
                                            command={`ban-ip ${player.name}`}
                                            onPerformed={(uuid) => getPlayers(uuid)}
                                        />
                                    </td>
                                )}
                                {(eggName?.name.includes('FiveM') || eggName?.name.includes('RedM')) && (
                                    <td>
                                        <PlayerManagerAskModal
                                            buttonColor={'red'}
                                            buttonText={'Add to admins'}
                                            title={'Promote user to admin role'}
                                            message={`Are you sure that you want to make <b>${player.name}</b> to admin?`}
                                            command={`add_principal identifier.${player.metadata.identifier} group.admin`}
                                            onPerformed={(uuid) => getPlayers(uuid)}
                                        />
                                        <PlayerManagerAskModal
                                            buttonColor={'primary'}
                                            buttonText={'Remove from admin'}
                                            title={'Remove from admin role'}
                                            message={`Are you sure that you want to remove <b>${player.name}</b> from admins?`}
                                            command={`remove_principal identifier.${player.metadata.identifier} group.admin`}
                                            onPerformed={(uuid) => getPlayers(uuid)}
                                        />
                                        <PlayerManagerAskModal
                                            buttonColor={'red'}
                                            buttonSecondary
                                            buttonText={'Kick'}
                                            title={'Kick Player'}
                                            message={`Are you sure that you want to kick <b>${player.name}</b>?`}
                                            command={`clientkick ${player.id} Kicked from console.`}
                                            onPerformed={(uuid) => getPlayers(uuid)}
                                        />
                                    </td>
                                )}
                            </tr>
                        ))}
                    </tbody>
                </PlayerTable>
                {players.length <= 0 && (
                    <div css={tw`py-8 text-center px-6`}>
                        <h1 css={tw`text-lg text-neutral-200 text-center`}>No players detected.</h1>
                        <h3 css={tw`text-sm text-neutral-400 mt-2 text-center`}>This may be incorrect if the panel cannot query your server for players.</h3>
                    </div>
                )}
            </div>
        </>
    );
};
