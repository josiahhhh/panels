import React, { useState } from 'react';
import { Actions, useStoreActions } from 'easy-peasy';
import tw from 'twin.macro';

import { ServerContext } from '@/state/server';
import { ApplicationStore } from '@/state';

import { httpErrorToHuman } from '@/api/http';

import Button from '@/components/elements/Button';
import GreyRowBox from '@/components/elements/GreyRowBox';

import installServerMod from '@/api/server/mods/installServerMod';
import Modal from '@/components/elements/Modal';
import MessageBox from '@/components/MessageBox';
import { useDeepMemoize } from '@/plugins/useDeepMemoize';

interface Props {
    id: number;
    name: string;
    image: string;
    description: string;
    hasSQL: boolean;
    willRestart: boolean;
    onInstalled: () => void;
}

export default ({ id, name, image, description, hasSQL, willRestart, onInstalled }: Props) => {
    const [visible, setVisible] = useState(false);
    const [isLoading, setIsLoading] = useState(false);
    const uuid = ServerContext.useStoreState((state) => state.server.data!.uuid);
    const { addError, clearFlashes } = useStoreActions((actions: Actions<ApplicationStore>) => actions.flashes);

    const databases = useDeepMemoize(ServerContext.useStoreState((state) => state.databases.data));

    const onInstall = () => {
        setIsLoading(true);
        clearFlashes('server:mods');

        const refreshTimeout = setTimeout(() => {
            window.location.reload();
        }, 5000);

        installServerMod(uuid, id)
            .then(() => {
                clearTimeout(refreshTimeout);

                setIsLoading(false);
                setVisible(false);
                onInstalled();
            })
            .catch((error) => {
                clearTimeout(refreshTimeout);

                console.error(error);

                addError({ key: 'server:mods', message: httpErrorToHuman(error) });

                setIsLoading(false);
                setVisible(false);
            });
    };

    return (
        <>
            <Modal visible={visible} showSpinnerOverlay={isLoading} onDismissed={() => setVisible(false)}>
                <div>
                    <h2 css={tw`text-2xl mb-2`}>Install {name}</h2>
                    <p css={tw`text-sm font-normal text-neutral-300 mb-6`}>
                        Installing this mod may overwrite/delete files and database tables, please make a backup before proceeding. Are you sure you want to continue?
                    </p>
                    {hasSQL && databases.length <= 0 && (
                        <div css={tw`mb-4`}>
                            <MessageBox type='info' title='Info'>
                                This mod contains a bundled SQL file - please ensure you have a database created under the &quot;Databases&quot; tab, otherwise the mod SQL will not
                                be installed for you.
                            </MessageBox>
                        </div>
                    )}
                    <div css={tw`flex flex-row justify-between items-center`}>
                        <div>
                            {willRestart && (
                                <span css={tw`text-base font-medium text-red-500`}>Installing this mod will automatically cause the server to restart on completion.</span>
                            )}
                        </div>
                        <div css={tw`flex justify-end mt-4`}>
                            <Button isSecondary onClick={() => setVisible(false)} css={tw`mr-4`}>
                                <span>Cancel</span>
                            </Button>
                            <Button onClick={onInstall}>
                                <span>Install Mod</span>
                            </Button>
                        </div>
                    </div>
                </div>
            </Modal>
            <GreyRowBox css={tw`flex-wrap md:flex-nowrap items-center`} className={'installed-mod'}>
                <div css={tw`flex flex-col flex-grow`}>
                    <div css={tw`flex flex-row items-center mb-2`}>
                        {image !== '' && <img src={image} width={'24px'} css={tw`mr-4`} />}
                        <p css={tw`text-lg break-words mr-2`}>{name}</p>
                    </div>
                    <p css={tw`text-sm text-neutral-200 break-words flex-grow`}>{description}</p>
                </div>
                <Button color={'primary'} size={'xsmall'} isSecondary onClick={() => setVisible(true)}>
                    Install
                </Button>
            </GreyRowBox>
        </>
    );
};
