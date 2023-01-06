import React, { useEffect, useState } from 'react';
import { ServerContext } from '@/state/server';
import TitledGreyBox from '@/components/elements/TitledGreyBox';
import reinstallServer from '@/api/server/reinstallServer';
import { Actions, useStoreActions } from 'easy-peasy';
import { ApplicationStore } from '@/state';
import { httpErrorToHuman } from '@/api/http';
import tw from 'twin.macro';
import Button from '@/components/elements/Button';
import Modal, { RequiredModalProps } from '@/components/elements/Modal';
import Input from '@/components/elements/Input';

interface ModalProps {
    onConfirmed: () => void;
}

const ConfirmModalContent = ({ onConfirmed, ...props }: ModalProps & RequiredModalProps) => {
    const [confirmed, setConfirmed] = useState(false);

    const confirmModal = () => {
        onConfirmed();
    };

    return (
        <Modal {...props}>
            <h2 css={tw`text-2xl mb-2`}>Update Server?</h2>
            <p css={tw`text-sm font-normal text-neutral-300 mb-6`}>
                Your server will be stopped and the game files will be reinstalled. You should make a backup before running this in case if changes any of your game files, are you
                sure you wish to continue?
            </p>
            <Input placeholder={`Type update to confirm`} onChange={(e) => setConfirmed(e.target.value === 'update')} />
            <div css={tw`flex justify-end mt-4`}>
                <Button isSecondary type={'submit'} onClick={props.onDismissed} css={tw`mr-4`}>
                    <span>Cancel</span>
                </Button>
                <Button type={'submit'} onClick={confirmModal} disabled={!confirmed}>
                    <span>Update Server</span>
                </Button>
            </div>
        </Modal>
    );
};

export default () => {
    const uuid = ServerContext.useStoreState((state) => state.server.data!.uuid);
    const [_, setIsSubmitting] = useState(false);
    const [modalVisible, setModalVisible] = useState(false);
    const { addFlash, clearFlashes } = useStoreActions((actions: Actions<ApplicationStore>) => actions.flashes);

    const reinstall = () => {
        clearFlashes('settings');
        setIsSubmitting(true);
        reinstallServer(uuid)
            .then(() => {
                addFlash({
                    key: 'settings',
                    type: 'success',
                    message: 'Your server has begun the update process.',
                });
            })
            .catch((error) => {
                console.error(error);

                addFlash({ key: 'settings', type: 'error', message: httpErrorToHuman(error) });
            })
            .then(() => {
                setIsSubmitting(false);
                setModalVisible(false);
            });
    };

    useEffect(() => {
        clearFlashes();
    }, []);

    return (
        <TitledGreyBox title={'Update Server'} css={tw`relative`}>
            <ConfirmModalContent appear visible={modalVisible} onDismissed={() => setModalVisible(false)} onConfirmed={reinstall} />
            <p css={tw`text-sm`}>
                Updating your server will stop it, and then re-run the installation script that initially sets up the server.&nbsp;
                <strong css={tw`font-medium`}>Some files may be deleted or modified during this process, please back up your data before continuing.</strong>
            </p>
            <div css={tw`mt-6 text-right`}>
                <Button type={'button'} color={'red'} isSecondary onClick={() => setModalVisible(true)}>
                    Update Server
                </Button>
            </div>
        </TitledGreyBox>
    );
};
