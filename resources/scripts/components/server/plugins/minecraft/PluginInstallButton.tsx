import React, { useState } from 'react';
import tw from 'twin.macro';
import { ServerContext } from '@/state/server';
import Spinner from '@/components/elements/Spinner';
import { Button } from '@/components/elements/button/index';
import Modal, { RequiredModalProps } from '@/components/elements/Modal';
import installMinecraftPlugin from '@/api/server/iceline/minecraft/installMinecraftPlugin';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import { faCheckCircle } from '@fortawesome/free-solid-svg-icons';
import MessageBox from '@/components/MessageBox';

interface Props {
    id: number;
    name: string;
    file: {
        type: string;
        size: number;
        sizeUnit: string;
        url: string;
    };
    onError: (error: any) => void;
}

interface ModalProps {
    resource: Props;
    loading?: boolean;
    onConfirmed: () => void;
}

const ExternalWarningModalContent = ({ resource, onConfirmed, ...props }: ModalProps & RequiredModalProps) => {
    const confirmModal = () => {
        onConfirmed();
    };

    return (
        <Modal {...props}>
            <h2 css={tw`text-2xl mb-2`}>Install {resource.name}</h2>
            <p css={tw`text-sm font-normal text-neutral-300`}>
                The selected plugin provides a non-jar installer or links to an external resource. We cannot guarantee that the plugin will automatically work after installing, you
                may need to manually move the downloaded file somewhere else, or un-zip it in the File Manager.
            </p>
            <div css={tw`flex justify-end mt-10`}>
                <Button type={'submit'} onClick={confirmModal}>
                    Install Plugin
                </Button>
            </div>
        </Modal>
    );
};

const InstallModalContent = ({ loading, resource, onConfirmed, ...props }: ModalProps & RequiredModalProps) => {
    const confirmModal = () => {
        onConfirmed();
    };

    return (
        <Modal {...props}>
            <h2 css={tw`text-2xl mb-2`}>Install {resource.name}</h2>
            <p css={tw`text-sm font-normal text-neutral-300 mb-4`}>
                Are you sure you want to install this plugin? This may override existing plugin data or break your server if you install plugin incompatible that is incompatible
                with your server version.
            </p>
            <MessageBox type='info' title='Info'>
                Ensure your server is using Paper, Spigot or CraftBukkit via the Startup Parameters or the Version Selector on the Settings page - otherwise plugins will not appear
                on your server (Vanilla servers cannot load plugins!.
            </MessageBox>
            <div css={tw`flex justify-end mt-6`}>
                <Button variant={Button.Variants.Secondary} size={Button.Sizes.Small} onClick={props.onDismissed} css={tw`mr-4`}>
                    <span>Cancel</span>
                </Button>
                <Button type={'submit'} onClick={confirmModal} disabled={loading}>
                    {loading ? <Spinner size={'small'} /> : <span>Install Plugin</span>}
                </Button>
            </div>
        </Modal>
    );
};

export default (props: Props) => {
    const uuid = ServerContext.useStoreState((state) => state.server.data!.uuid);
    const [loading, setLoading] = useState(false);
    const [showDetailsModal, setShowDetailsModal] = useState(false);
    const [showInstallModal, setShowInstallModal] = useState(false);
    const [installed, setInstalled] = useState(false);

    const actuallyInstallPlugin = () => {
        setShowDetailsModal(false);
        setLoading(true);

        installMinecraftPlugin(uuid, props.id)
            .then(() => {
                setInstalled(true);
            })
            .catch((error) => {
                props.onError(error);
            })
            .finally(() => {
                setLoading(false);
                setShowInstallModal(false);
            });
    };

    const doInstallPlugin = () => {
        setShowDetailsModal(false);
        setShowInstallModal(true);
    };

    const installPlugin = () => {
        // Display an info modal warning about non-jar plugins
        if (props.file.type !== '.jar') {
            setShowDetailsModal(true);
        } else {
            doInstallPlugin();
        }
    };

    return (
        <>
            <ExternalWarningModalContent appear visible={showDetailsModal} onDismissed={() => setShowDetailsModal(false)} onConfirmed={doInstallPlugin} resource={props} />
            <InstallModalContent
                appear
                visible={showInstallModal}
                onDismissed={() => setShowInstallModal(false)}
                onConfirmed={actuallyInstallPlugin}
                resource={props}
                loading={loading}
            />
            <Button color={installed ? 'green' : 'primary'} size={Button.Sizes.Small} disabled={loading} onClick={installPlugin}>
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
        </>
    );
};
