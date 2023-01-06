import React, { useState } from 'react';
import { ServerContext } from '@/state/server';
import { Actions, useStoreActions } from 'easy-peasy';
import { ApplicationStore } from '@/state';
import { httpErrorToHuman } from '@/api/http';
import Button from '@/components/elements/Button';
import ConfirmationModal from '@/components/elements/ConfirmationModal';
import syncServerSubdomain from '@/api/server/subdomain/syncServerSubdomain';

interface Props {
    subdomainId: number;
    onSynced: () => void;
}

export default ({ subdomainId, onSynced }: Props) => {
    const [visible, setVisible] = useState(false);
    const [isLoading, setIsLoading] = useState(false);
    const uuid = ServerContext.useStoreState((state) => state.server.data!.uuid);
    const { addError, clearFlashes } = useStoreActions((actions: Actions<ApplicationStore>) => actions.flashes);

    const onSync = () => {
        setIsLoading(true);
        clearFlashes('server:subdomain');

        syncServerSubdomain(uuid, subdomainId)
            .then(() => {
                setIsLoading(false);
                setVisible(false);
                onSynced();
            })
            .catch((error) => {
                console.error(error);

                addError({ key: 'server:subdomain', message: httpErrorToHuman(error) });

                setIsLoading(false);
                setVisible(false);
            });
    };

    return (
        <>
            <ConfirmationModal
                visible={visible}
                title={'Sync subdomain?'}
                buttonText={'Yes, sync subdomain'}
                onConfirmed={onSync}
                showSpinnerOverlay={isLoading}
                onModalDismissed={() => setVisible(false)}
            >
                This will delete and re-create your subdomain to ensure it is up-to-date and pointing to the correct allocation. This can fix your connection if you are
                experiencing issues after a server transfer, IP change, or allocation change.
            </ConfirmationModal>
            <Button size={'xsmall'} isSecondary onClick={() => setVisible(true)}>
                Sync
            </Button>
        </>
    );
};
