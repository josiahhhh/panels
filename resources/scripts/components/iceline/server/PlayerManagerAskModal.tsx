import React, { useState } from 'react';
import { Actions, useStoreActions } from 'easy-peasy';
import { ApplicationStore } from '@/state';
import Button from '@/components/elements/Button';
import ConfirmationModal from '@/components/elements/ConfirmationModal';
import tw from 'twin.macro';
import { ServerContext } from '@/state/server';
import { httpErrorToHuman } from '@/api/http';
import runCommand from '@/api/server/iceline/players/runCommand';

interface Props {
    buttonText: string;
    buttonColor?: 'green' | 'red' | 'primary' | 'grey';
    buttonSecondary?: boolean;
    title: string;
    message: string;
    command: string;
    onPerformed: (uuid: string) => void;
}

export default ({ buttonText, buttonColor, buttonSecondary, title, message, command, onPerformed }: Props) => {
    const uuid = ServerContext.useStoreState((state) => state.server.data!.uuid);

    const [visible, setVisible] = useState(false);
    const [isLoading, setIsLoading] = useState(false);
    const { clearFlashes, addError, addFlash } = useStoreActions((actions: Actions<ApplicationStore>) => actions.flashes);

    const onAllow = () => {
        setIsLoading(true);
        clearFlashes('server:players');

        runCommand(uuid, command)
            .then(() => {
                setIsLoading(false);
                onPerformed(uuid);
                addFlash({ key: 'server:players', message: "You've successfully performed the action.", type: 'success', title: 'Success' });
                setVisible(false);
            })
            .catch((error) => {
                addError({ key: 'server:players', message: httpErrorToHuman(error) });
                setIsLoading(false);
                setVisible(false);
            });
    };

    return (
        <>
            <ConfirmationModal visible={visible} title={title} buttonText={'Yes'} onConfirmed={onAllow} showSpinnerOverlay={isLoading} onModalDismissed={() => setVisible(false)}>
                <p dangerouslySetInnerHTML={{ __html: message }} />
            </ConfirmationModal>
            <Button color={buttonColor ?? 'red'} isSecondary={buttonSecondary} size={'xsmall'} css={tw`ml-1 mr-1`} onClick={() => setVisible(true)}>
                {buttonText}
            </Button>
        </>
    );
};
