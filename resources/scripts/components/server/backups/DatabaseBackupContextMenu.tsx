import React, { useState } from 'react';
import { faCloudDownloadAlt, faEllipsisH, faTrashAlt, faRedo } from '@fortawesome/free-solid-svg-icons';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import DropdownMenu, { DropdownButtonRow } from '@/components/elements/DropdownMenu';
import useFlash from '@/plugins/useFlash';
import SpinnerOverlay from '@/components/elements/SpinnerOverlay';
import ConfirmationModal from '@/components/elements/ConfirmationModal';
import Can from '@/components/elements/Can';
import tw from 'twin.macro';
import { ServerContext } from '@/state/server';
import getServerDatabaseBackups, { DatabaseBackup } from '@/api/server/iceline/backups/getServerDatabaseBackups';
import deleteServerDatabaseBackup from '@/api/server/iceline/backups/deleteServerDatabaseBackup';
import getServerDatabaseBackupURL from '@/api/server/iceline/backups/getServerDatabaseBackupURL';
import restoreServerDatabaseBackup from '@/api/server/iceline/backups/restoreServerDatabaseBackup';

interface Props {
    backup: DatabaseBackup;
    didRestore: () => void;
}

/* interface RestoreModalProps {
    onConfirmed: () => void;
} */

/* const RestoreModalContent = ({ onConfirmed, ...props }: RestoreModalProps & RequiredModalProps) => {
    const [confirmed, setConfirmed] = useState(false);

    return (
        <Modal {...props}>
            <h2 css={tw`text-2xl mb-2`}>Restore Backup</h2>
            <p css={tw`text-sm font-normal text-neutral-300 mb-6`}>
                Restoring a database backup may alter or overwrite tables in your database. If you&apos;re certain you want to continue, type &quot;restore&quot; below to confirm.
            </p>
            <Input placeholder={`Type "restore" to confirm`} onChange={(e) => setConfirmed(e.target.value === 'restore')} />
            <div css={tw`flex justify-end mt-4`}>
                <Button type={'submit'} disabled={!confirmed} onClick={onConfirmed}>
                    Restore Backup
                </Button>
            </div>
        </Modal>
    );
}; */

export default ({ backup, didRestore }: Props) => {
    const uuid = ServerContext.useStoreState((state) => state.server.data!.uuid);
    const [loading, setLoading] = useState(false);
    const [deleteVisible, setDeleteVisible] = useState(false);
    const { clearFlashes, clearAndAddHttpError } = useFlash();
    const { mutate } = getServerDatabaseBackups();

    const doDownload = () => {
        setLoading(true);
        clearFlashes('backups');
        getServerDatabaseBackupURL(uuid, backup.uuid)
            .then((url) => {
                const win: Window = window;
                win.location = url;
            })
            .catch((error) => {
                console.error(error);
                clearAndAddHttpError({ key: 'backups', error });
            })
            .then(() => setLoading(false));
    };

    const doDeletion = () => {
        setLoading(true);
        clearFlashes('backups');
        deleteServerDatabaseBackup(uuid, backup.uuid)
            .then(() => {
                mutate(
                    (data) => ({
                        ...data,
                        items: data.items.filter((b) => b.uuid !== backup.uuid),
                    }),
                    false
                );
            })
            .catch((error) => {
                console.error(error);
                clearAndAddHttpError({ key: 'backups', error });
                setLoading(false);
                setDeleteVisible(false);
            });
    };

    const doRestore = () => {
        setLoading(true);
        clearFlashes('backups');
        restoreServerDatabaseBackup(uuid, backup.uuid)
            .catch((error) => {
                console.error(error);
                clearAndAddHttpError({ key: 'backups', error });
                setLoading(false);
            })
            .then(() => {
                didRestore();
                setLoading(false);
            });
    };

    return (
        <>
            <ConfirmationModal
                visible={deleteVisible}
                title={'Delete this database backup?'}
                buttonText={'Yes, delete database backup'}
                onConfirmed={() => doDeletion()}
                onModalDismissed={() => setDeleteVisible(false)}
            >
                Are you sure you wish to delete this database backup? This is a permanent operation and the backup cannot be recovered once deleted.
            </ConfirmationModal>
            <SpinnerOverlay visible={loading} fixed />
            {backup.isSuccessful ? (
                <DropdownMenu
                    renderToggle={(onClick) => (
                        <button onClick={onClick} css={tw`text-neutral-200 transition-colors duration-150 hover:text-neutral-100 p-2`}>
                            <FontAwesomeIcon icon={faEllipsisH} />
                        </button>
                    )}
                >
                    <div css={tw`text-sm`}>
                        <Can action={'database_backup.download'}>
                            <DropdownButtonRow onClick={() => doDownload()}>
                                <FontAwesomeIcon fixedWidth icon={faCloudDownloadAlt} css={tw`text-xs`} />
                                <span css={tw`ml-2`}>Download</span>
                            </DropdownButtonRow>
                        </Can>
                        <Can action={'database_backup.restore'}>
                            <DropdownButtonRow onClick={() => doRestore()}>
                                <FontAwesomeIcon fixedWidth icon={faRedo} css={tw`text-xs`} />
                                <span css={tw`ml-2`}>Restore</span>
                            </DropdownButtonRow>
                        </Can>
                        <Can action={'database_backup.delete'}>
                            <DropdownButtonRow danger onClick={() => setDeleteVisible(true)}>
                                <FontAwesomeIcon fixedWidth icon={faTrashAlt} css={tw`text-xs`} />
                                <span css={tw`ml-2`}>Delete</span>
                            </DropdownButtonRow>
                        </Can>
                    </div>
                </DropdownMenu>
            ) : (
                <button onClick={() => setDeleteVisible(true)} css={tw`text-neutral-200 transition-colors duration-150 hover:text-neutral-100 p-2`}>
                    <FontAwesomeIcon icon={faTrashAlt} />
                </button>
            )}
        </>
    );
};
