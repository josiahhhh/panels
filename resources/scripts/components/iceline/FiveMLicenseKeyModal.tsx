import React from 'react';
import tw from 'twin.macro';
import Button from '@/components/elements/Button';
import Modal from '@/components/elements/Modal';
import { ServerContext } from '@/state/server';

interface Props {
    visible: boolean;
    onDismissed: () => void;
}

export default ({ visible, onDismissed }: Props) => {
    const id = ServerContext.useStoreState((state) => state.server.data!.id);
    const name = ServerContext.useStoreState((state) => state.server.data!.name);

    const primaryAllocation = ServerContext.useStoreState((state) => state.server.data!.allocations.find((alloc) => alloc.isDefault));
    const nodeIP = ServerContext.useStoreState((state) => state.server.data!.nodeIP);

    return (
        <Modal visible={visible} dismissable onDismissed={onDismissed}>
            <h1 css={tw`text-lg font-medium mb-4 text-neutral-50`}>FiveM License Key</h1>
            <div>
                <p css={tw`text-base font-normal mb-4 text-neutral-100`}>
                    Please go to the <a href='https://keymaster.fivem.net/'>FiveM Keymaster</a> to register a new key.
                </p>
                <p css={tw`text-base font-normal mb-2 text-neutral-300`}>Label: {name}</p>
                <p css={tw`text-base font-normal mb-2 text-neutral-300`}>IP Address: {nodeIP || primaryAllocation?.ip}</p>
                <p css={tw`text-base font-normal mb-2 text-neutral-300`}>Server Type: Other</p>
                <p css={tw`text-base font-normal mb-4 text-neutral-300`}>Provider: {id}</p>
                <p css={tw`text-base font-normal mb-4 text-neutral-200`}>
                    Do not enter Iceline as your provider or it will not work and you will need to open a support ticket to resolve it.
                </p>
            </div>
            <div css={tw`flex justify-end`}>
                <Button css={tw`mt-8`} onClick={onDismissed}>
                    Okay
                </Button>
            </div>
        </Modal>
    );
};
