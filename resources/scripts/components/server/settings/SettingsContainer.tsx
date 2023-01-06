import React, { useEffect, useState } from 'react';
import TitledGreyBox from '@/components/elements/TitledGreyBox';
import { ServerContext } from '@/state/server';
import { useStoreState } from 'easy-peasy';
import RenameServerBox from '@/components/server/settings/RenameServerBox';
import FlashMessageRender from '@/components/FlashMessageRender';
import Can from '@/components/elements/Can';
import ReinstallServerBox from '@/components/server/settings/ReinstallServerBox';
import tw from 'twin.macro';
import Input from '@/components/elements/Input';
import Label from '@/components/elements/Label';
import ServerContentBlock from '@/components/elements/ServerContentBlock';
import MinecraftVersionBox from '@/components/server/settings/MinecraftVersionBox';
import isEqual from 'react-fast-compare';
import UpdateServerBox from '@/components/server/settings/UpdateServerBox';
import RustWipeBox from '@/components/server/settings/RustWipeBox';
import MessageBox from '@/components/MessageBox';
import useSWR from 'swr';
import getEggs from '@/api/server/eggs/getEggs';
import { EggsResponse } from '@/components/server/eggs/EggsContainer';
import useFlash from '@/plugins/useFlash';
import Spinner from '@/components/elements/Spinner';
import ChangeEggButton from '@/components/server/eggs/ChangeEggButton';
import Select from '@/components/elements/Select';
import ImporterServerBox from '@/components/server/settings/ImporterServerBox';

export default () => {
    // ====================
    // Standard Settings

    const username = useStoreState((state) => state.user.data!.username);
    const id = ServerContext.useStoreState((state) => state.server.data!.id);
    const uuid = ServerContext.useStoreState((state) => state.server.data!.uuid);
    const sftp = ServerContext.useStoreState((state) => state.server.data!.sftpDetails, isEqual);

    const egg = ServerContext.useStoreState((state) => state.server.data!.egg.name);

    // ====================
    // Egg Changer

    const { clearFlashes, clearAndAddHttpError } = useFlash();

    const {
        data: eggsData,
        error: eggsError,
        mutate: mutateEggs,
    } = useSWR<EggsResponse>([uuid, '/eggs'], (uuid) => getEggs(uuid), {
        revalidateOnFocus: false,
    });

    const [selectedEgg, setSelectedEgg] = useState<undefined | number>(undefined);

    useEffect(() => {
        if (!eggsError) {
            clearFlashes('server:eggs');
        } else {
            clearAndAddHttpError({ key: 'server:eggs', error: eggsError });
        }
    }, [eggsError]);

    useEffect(() => {
        if (eggsData) {
            const firstAvailableEgg = eggsData.eggs.filter((egg) => egg.id !== eggsData.currentEggId)[0];
            if (firstAvailableEgg) {
                setSelectedEgg(firstAvailableEgg.id);
            }
        }
    }, [eggsData]);

    return (
        <ServerContentBlock title={'Settings'}>
            <FlashMessageRender byKey={'settings'} css={tw`mb-4`} />
            <div css={tw`md:flex`}>
                <div css={tw`w-full md:flex-1 md:mr-10`}>
                    <div css={tw`mb-6 md:mb-10`}>
                        <TitledGreyBox title={'Change Game'} css={tw`relative`}>
                            <FlashMessageRender byKey={'server:eggs'} css={tw`mb-4`} />
                            {!eggsData ? (
                                <div css={tw`w-full`}>
                                    <Spinner size={'large'} centered />
                                </div>
                            ) : (
                                <>
                                    {eggsData.eggs.length < 1 ? (
                                        <p css={tw`text-center text-sm text-neutral-400 pt-4 pb-4`}>There are no changeable eggs.</p>
                                    ) : (
                                        <>
                                            <div>
                                                <Label htmlFor={'flavor'}>Choose Game</Label>
                                                <Select name={'flavor'} value={selectedEgg} onChange={(e) => setSelectedEgg(parseInt(e.currentTarget.value))}>
                                                    {eggsData.eggs.map((egg, key) => (
                                                        <option key={key} value={egg.id} disabled={eggsData.currentEggId === egg.id}>
                                                            {egg.name}
                                                        </option>
                                                    ))}
                                                </Select>
                                                <ChangeEggButton
                                                    disabled={!selectedEgg && selectedEgg !== eggsData.currentEggId}
                                                    eggId={selectedEgg || 0}
                                                    onChange={() => mutateEggs()}
                                                />
                                            </div>
                                        </>
                                    )}
                                </>
                            )}
                        </TitledGreyBox>
                    </div>
                    {(egg.includes('Minecraft') || egg.includes('Paper') || egg.includes('Spigot') || egg.includes('Waterfall') || egg.includes('BungeeCord')) && (
                        <div css={tw`mb-6 md:mb-10`}>
                            <MinecraftVersionBox />
                        </div>
                    )}
                    {egg === 'Rust' && (
                        <div css={tw`mb-6 md:mb-10`}>
                            <RustWipeBox />
                        </div>
                    )}
                    {!egg.toLowerCase().includes('proxy') && (
                        <Can action={'file.sftp'}>
                            <TitledGreyBox title={'SFTP Details'} css={tw`mb-6 md:mb-10`}>
                                <div css={tw`mb-4`}>
                                    <MessageBox type='info' title='Info'>
                                        For account security, if this is your first time accessing SFTP you will need to set your SFTP password by logging out of the game panel and
                                        pressing &quot;Forgot Password?&quot;.
                                    </MessageBox>
                                </div>
                                <div css={tw`grid grid-cols-4 gap-4`}>
                                    <div css={tw`col-span-3`}>
                                        <Label>Host</Label>
                                        <Input type={'text'} value={`sftp://${sftp.ip}`} readOnly />
                                    </div>
                                    <div>
                                        <Label>Port</Label>
                                        <Input type={'text'} value={`${sftp.port}`} readOnly />
                                    </div>
                                </div>
                                <div css={tw`mt-4`}>
                                    <Label>Username</Label>
                                    <Input type={'text'} value={`${username}.${id}`} readOnly />
                                </div>
                                <div css={tw`mt-6 flex items-center`}>
                                    <div css={tw`flex-1`}>
                                        <div css={tw`border-l-4 border-cyan-500 p-3`}>
                                            <p css={tw`text-xs text-neutral-200`}>Your SFTP password is the same as the password you use to access this panel.</p>
                                        </div>
                                    </div>
                                    {/* <div css={tw`ml-4`}> */}
                                    {/*     <LinkButton */}
                                    {/*         isSecondary */}
                                    {/*         href={`sftp://${username}.${id}@${sftp.ip}:${sftp.port}`} */}
                                    {/*     > */}
                                    {/*         Launch SFTP */}
                                    {/*     </LinkButton> */}
                                    {/* </div> */}
                                </div>
                            </TitledGreyBox>
                        </Can>
                    )}
                    {/* <TitledGreyBox title={'Debug Information'} css={tw`mb-6 md:mb-10`}> */}
                    {/*    <div css={tw`flex items-center justify-between text-sm`}> */}
                    {/*        <p>Node</p> */}
                    {/*        <code css={tw`font-mono bg-neutral-900 rounded py-1 px-2`}>{node}</code> */}
                    {/*    </div> */}
                    {/*    <CopyOnClick text={uuid}> */}
                    {/*        <div css={tw`flex items-center justify-between mt-2 text-sm`}> */}
                    {/*            <p>Server ID</p> */}
                    {/*            <code css={tw`font-mono bg-neutral-900 rounded py-1 px-2`}>{uuid}</code> */}
                    {/*        </div> */}
                    {/*    </CopyOnClick> */}
                    {/* </TitledGreyBox> */}
                </div>
                <div css={tw`w-full mt-6 md:flex-1 md:mt-0`}>
                    <Can action={'settings.rename'}>
                        <div css={tw`mb-6 md:mb-10`}>
                            <RenameServerBox />
                        </div>
                    </Can>
                    <Can action={'settings.update'}>
                        <div css={tw`mb-6 md:mb-10`}>
                            <UpdateServerBox />
                        </div>
                    </Can>
                    <Can action={'serverimporter.*'}>
                        <div css={tw`mb-6 md:mb-10`}>
                            <ImporterServerBox />
                        </div>
                    </Can>
                    <Can action={'settings.reinstall'}>
                        <ReinstallServerBox />
                    </Can>
                </div>
            </div>
        </ServerContentBlock>
    );
};
