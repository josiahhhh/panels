import React, { useState } from 'react';
import { ServerContext } from '@/state/server';
import TitledGreyBox from '@/components/elements/TitledGreyBox';
import SpinnerOverlay from '@/components/elements/SpinnerOverlay';
import { httpErrorToHuman } from '@/api/http';
import Button from '@/components/elements/Button';
import tw from 'twin.macro';
import FlashMessageRender from '@/components/FlashMessageRender';
import useFlash from '@/plugins/useFlash';
import Select from '@/components/elements/Select';
import Label from '@/components/elements/Label';
import wipeRustServer, { WipeType } from '@/api/server/iceline/games/rust/wipeRustServer';

const RustWipeBox = () => {
    const { addError, clearFlashes } = useFlash();
    const [loading, setLoading] = useState(false);

    const [selectedWipe, setSelectedWipe] = useState('blueprint');

    const uuid = ServerContext.useStoreState((state) => state.server.data!.uuid);

    const doWipe = () => {
        if (!selectedWipe) {
            return;
        }

        setLoading(true);
        clearFlashes('rust:wipe');

        wipeRustServer(uuid, selectedWipe as WipeType)
            .catch((error) => {
                console.error(error);
                addError({ key: 'rust:wipe', message: httpErrorToHuman(error) });
            })
            .then(() => setLoading(false));
    };

    return (
        <TitledGreyBox title={'Wipe Rust Server'} css={tw`relative`}>
            <SpinnerOverlay visible={loading} />
            <FlashMessageRender key={'rust:wipe'} css={tw`pb-4`} />
            <div>
                <Label htmlFor={'flavor'}>Wipe Type</Label>
                <Select name={'flavor'} value={selectedWipe} onChange={(e) => setSelectedWipe(e.currentTarget.value)}>
                    <option value={'blueprint'}>Blueprints</option>
                    <option value={'map'}>Maps</option>
                    <option value={'full'}>Full</option>
                </Select>
            </div>
            <div css={tw`mt-2`}>
                <p css={tw`text-sm text-neutral-600`}>
                    {selectedWipe === 'blueprint' && <span>This will wipe all the blueprint.*.db file from the /server/rust directory.</span>}
                    {selectedWipe === 'map' && <span>This will wipe all the .map, .sav, and .sav.* files from the /server/rust directory.</span>}
                    {selectedWipe === 'full' && <span>This will wipe all the files from the /server/rust directory.</span>}
                </p>
            </div>
            <div css={tw`mt-6 flex items-center`}>
                <div css={tw`flex-1`}>
                    <div css={tw`border-l-4 border-cyan-500 p-3`}>
                        <p css={tw`text-xs text-neutral-200`}>This action is irreversible, we highly recommend making a backup first.</p>
                    </div>
                </div>
                <div css={tw`ml-4`}>
                    <Button type={'submit'} disabled={!selectedWipe} onClick={doWipe}>
                        Wipe Server
                    </Button>
                </div>
            </div>
        </TitledGreyBox>
    );
};

export default () => {
    return <RustWipeBox />;
};
