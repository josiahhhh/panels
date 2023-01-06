import React, { useState } from 'react';
import { ServerContext } from '@/state/server';
import { Actions, useStoreActions } from 'easy-peasy';
import { ApplicationStore } from '@/state';
import { httpErrorToHuman } from '@/api/http';
import Button from '@/components/elements/Button';
import ConfirmationModal from '@/components/elements/ConfirmationModal';
import clearServerLogs from '@/api/server/logs/clearServerLogs';

interface Props {
    onCleared?: () => void;
}

export default ({ onCleared }: Props) => {
    const [visible, setVisible] = useState(false);
    const [isLoading, setIsLoading] = useState(false);
    const uuid = ServerContext.useStoreState((state) => state.server.data!.uuid);
    const { addError, clearFlashes } = useStoreActions((actions: Actions<ApplicationStore>) => actions.flashes);

    const onDelete = () => {
        setIsLoading(true);
        clearFlashes('server:logs');

        clearServerLogs(uuid)
            .then(() => {
                setIsLoading(false);
                setVisible(false);
                if (onCleared) onCleared();
            })
            .catch((error) => {
                addError({ key: 'server:logs', message: httpErrorToHuman(error) });
                setIsLoading(false);
                setVisible(false);
            });
    };

    return (
        <>
            <ConfirmationModal
                visible={visible}
                title={'Clear Server Logs?'}
                buttonText={'Yes, clear them'}
                onConfirmed={onDelete}
                showSpinnerOverlay={isLoading}
                onModalDismissed={() => setVisible(false)}
            >
                Are you sure you want to clear the logs? This will delete all the activity logs for your server.
            </ConfirmationModal>
            <Button color={'red'} size={'small'} onClick={() => setVisible(true)}>
                Clear Logs
            </Button>
        </>
    );
};
