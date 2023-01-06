import React, { useState } from 'react';
import { RustPlugin } from '@/api/server/iceline/plugins/rust/getRustPlugins';
import Spinner from '@/components/elements/Spinner';
import Button from '@/components/elements/Button';
import { ServerContext } from '@/state/server';
import installRustPlugin from '@/api/server/iceline/plugins/rust/installRustPlugin';
import { faCheckCircle } from '@fortawesome/free-solid-svg-icons';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import tw from 'twin.macro';
import Input from '@/components/elements/Input';
import Modal from '@/components/elements/Modal';
import getRustStatus from '@/api/server/iceline/plugins/rust/getRustStatus';
import { httpErrorToHuman } from '@/api/http';
import useFlash from '@/plugins/useFlash';
import MessageBox from '@/components/MessageBox';

interface Props {
    plugin: RustPlugin;
    onError: (error: any) => void;
}

export default ({ plugin, onError }: Props) => {
    const { addError } = useFlash();

    const uuid = ServerContext.useStoreState((state) => state.server.data!.uuid);
    const [loading, setLoading] = useState(false);
    const [installed, setInstalled] = useState(false);

    const [confirmed, setConfirmed] = useState(false);
    const [showUModModal, setShowUModModal] = useState(false);

    const installPlugin = () => {
        setShowUModModal(false);
        setLoading(true);

        installRustPlugin(uuid, plugin.slug)
            .then(() => {
                setInstalled(true);
            })
            .catch((error) => {
                onError(error);
            })
            .finally(() => {
                setLoading(false);
            });
    };

    const shouldInstallPlugin = () => {
        setLoading(true);

        getRustStatus(uuid)
            .then((status) => {
                if (!status.foundOxide) {
                    setShowUModModal(true);
                } else {
                    installPlugin();
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
            <Modal visible={showUModModal} onDismissed={() => setShowUModModal(false)}>
                <div>
                    <h2 css={tw`text-2xl mb-4`}>Oxide/uMod not detected</h2>
                    <MessageBox type='warning' title='Warning'>
                        Before installing a plugin, ensure you go to the mod manager and install uMod - otherwise the plugins will not appear on your server.
                    </MessageBox>
                    <p css={tw`text-sm font-normal text-neutral-300 mb-2 mt-6`}>If you are very certain you want to install it without uMod, type the plugin name below.</p>

                    <Input placeholder={`Type the plugin name "${plugin.slug}" to confirm`} onChange={(e) => setConfirmed(e.target.value === plugin.slug)} />
                    <div css={tw`flex justify-end mt-4`}>
                        <Button isSecondary onClick={() => setShowUModModal(false)} css={tw`mr-4`}>
                            <span>Cancel</span>
                        </Button>
                        <Button onClick={installPlugin} disabled={!confirmed}>
                            <span>Install Plugin</span>
                        </Button>
                    </div>
                </div>
            </Modal>
            <Button color={installed ? 'green' : 'primary'} size={'xsmall'} onClick={shouldInstallPlugin}>
                {loading ? (
                    <Spinner size={'small'} />
                ) : installed ? (
                    <span>
                        Installed <FontAwesomeIcon icon={faCheckCircle} css={tw`ml-2`} />
                    </span>
                ) : (
                    <span>Install Plugin</span>
                )}
            </Button>
        </div>
    );
};
