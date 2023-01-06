import React, { useState } from 'react';
import { Actions, useStoreActions } from 'easy-peasy';
import tw from 'twin.macro';
import { ServerContext } from '@/state/server';
import { ApplicationStore } from '@/state';
import { httpErrorToHuman } from '@/api/http';
import Button from '@/components/elements/Button';
import GreyRowBox from '@/components/elements/GreyRowBox';
import uninstallServerMod from '@/api/server/mods/uninstallServerMod';
import Spinner from '@/components/elements/Spinner';
import MessageBox from '@/components/MessageBox';
import Input from '@/components/elements/Input';
import Modal from '@/components/elements/Modal';

interface Props {
    id: number;
    name: string;
    image?: string;
    description: string;
    installing: boolean;
    installError: string;
    onDeleted: () => void;
}

export default ({ id, name, image, description, installing, installError, onDeleted }: Props) => {
    const [visible, setVisible] = useState(false);
    const [isLoading, setIsLoading] = useState(false);
    const [confirmed, setConfirmed] = useState(false);
    const uuid = ServerContext.useStoreState((state) => state.server.data!.uuid);
    const { addError, clearFlashes } = useStoreActions((actions: Actions<ApplicationStore>) => actions.flashes);

    const onDelete = () => {
        setIsLoading(true);
        clearFlashes('server:mods');

        uninstallServerMod(uuid, id)
            .then(() => {
                setIsLoading(false);
                setVisible(false);
                onDeleted();
            })
            .catch((error) => {
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
                    <h2 css={tw`text-2xl mb-2`}>Uninstall {name}</h2>
                    <p css={tw`text-sm font-normal text-neutral-300 mb-4`}>
                        Are you sure you want to delete this mod? This will remove the mod&apos;s files from your server and any mod-related tables from your database.
                    </p>

                    <Input placeholder={`Type the mod name "${name}" to confirm`} onChange={(e) => setConfirmed(e.target.value === name)} />
                    <div css={tw`flex justify-end mt-4`}>
                        <Button isSecondary onClick={() => setVisible(false)} css={tw`mr-4`}>
                            <span>Cancel</span>
                        </Button>
                        <Button onClick={onDelete} disabled={isLoading || !confirmed}>
                            <span>Uninstall Mod</span>
                        </Button>
                    </div>
                </div>
            </Modal>
            <GreyRowBox css={tw`w-full flex-wrap md:flex-nowrap items-center`} className={'installed-mod'}>
                <div css={tw`flex flex-col flex-grow mr-4`}>
                    <div css={tw`flex flex-row items-center mb-1`}>
                        {image !== '' && <img src={image} width={'24px'} css={tw`mr-4`} />}
                        <p css={tw`text-lg break-words mr-2`}>{name}</p>
                    </div>
                    <p css={tw`text-sm text-neutral-200 break-words flex-grow mb-4`}>{description}</p>
                    {installError && (
                        <MessageBox type='error' title='Installation Error'>
                            {installError}
                        </MessageBox>
                    )}
                </div>
                <div>
                    {installing ? (
                        <Spinner size={'small'} />
                    ) : (
                        <Button color={'red'} size={'xsmall'} isSecondary onClick={() => setVisible(true)}>
                            Uninstall
                        </Button>
                    )}
                </div>
            </GreyRowBox>
        </>
    );
};
