import React, { useState } from 'react';
import Spinner from '@/components/elements/Spinner';
import Button from '@/components/elements/Button';
import { ServerContext } from '@/state/server';
import { faCheckCircle } from '@fortawesome/free-solid-svg-icons';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import tw from 'twin.macro';
import Input from '@/components/elements/Input';
import Modal from '@/components/elements/Modal';
import getRustStatus from '@/api/server/iceline/plugins/rust/getRustStatus';
import { httpErrorToHuman } from '@/api/http';
import useFlash from '@/plugins/useFlash';
import uninstallRustPlugin from '@/api/server/iceline/plugins/rust/uninstallRustPlugin';

interface Props {
    name: string;
    filename: string;
    onError: (error: any) => void;
}

export default ({ name, filename, onError }: Props) => {
    const { addError } = useFlash();

    const uuid = ServerContext.useStoreState((state) => state.server.data!.uuid);
    const [loading, setLoading] = useState(false);
    const [uninstalled, setUninstalled] = useState(false);

    const [confirmed, setConfirmed] = useState(false);
    const [showConfirmModal, setShowConfirmModal] = useState(false);

    const uninstallPlugin = () => {
        setShowConfirmModal(false);
        setLoading(true);

        uninstallRustPlugin(uuid, filename)
            .then(() => {
                setUninstalled(true);
            })
            .catch((error) => {
                onError(error);
            })
            .finally(() => {
                setLoading(false);
            });
    };

    const shouldUninstallPlugin = () => {
        setLoading(true);

        getRustStatus(uuid)
            .then((status) => {
                if (!status.foundOxide) {
                    setShowConfirmModal(true);
                } else {
                    uninstallPlugin();
                }
            })
            .catch((error) => {
                console.error(error);
                addError({ key: 'server:plugins', message: httpErrorToHuman(error) });
            })
            .then(() => {
                setLoading(false);
            });
    };

    return (
        <div>
            <Modal visible={showConfirmModal} onDismissed={() => setShowConfirmModal(false)}>
                <div>
                    <h2 css={tw`text-2xl mb-4`}>Uninstall {name}?</h2>
                    <p css={tw`text-sm font-normal text-neutral-300 mb-2`}>
                        This will remove the {filename} file in your plugins folder. If you are sure then type &quot;uninstall&quot; in the box below.
                    </p>
                    <Input placeholder={'Type the word "uninstall" to confirm'} onChange={(e) => setConfirmed(e.target.value === filename)} />
                    <div css={tw`flex justify-end mt-4`}>
                        <Button isSecondary onClick={() => setShowConfirmModal(false)} css={tw`mr-4`}>
                            <span>Cancel</span>
                        </Button>
                        <Button onClick={uninstallPlugin} disabled={!confirmed}>
                            <span>Uninstall Plugin</span>
                        </Button>
                    </div>
                </div>
            </Modal>
            <Button color={uninstalled ? 'green' : 'primary'} size={'xsmall'} onClick={shouldUninstallPlugin}>
                {loading ? (
                    <Spinner size={'small'} />
                ) : uninstalled ? (
                    <span>
                        Uninstalled <FontAwesomeIcon icon={faCheckCircle} css={tw`ml-2`} />
                    </span>
                ) : (
                    <span>Uninstall Plugin</span>
                )}
            </Button>
        </div>
    );
};
