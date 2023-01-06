import React, { useEffect, useState } from 'react';
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
import getMinecraftFlavors, { MinecraftFlavor } from '@/api/server/iceline/minecraft/getMinecraftFlavors';
import getMinecraftFlavorVersions, { MinecraftVersion } from '@/api/server/iceline/minecraft/getMinecraftFlavorVersions';
import changeMinecraftVersion from '@/api/server/iceline/minecraft/changeMinecraftVersion';

const MinecraftVersionBox = () => {
    const { addError, clearFlashes } = useFlash();
    const [loading, setLoading] = useState(true);
    const [flavors, setFlavors] = useState([] as MinecraftFlavor[]);
    const [flavorVersions, setFlavorVersions] = useState([] as MinecraftVersion[]);

    const [selectedFlavor, setSelectedFlavor] = useState('');
    const [selectedFlavorVersion, setSelectedFlavorVersion] = useState('');

    const uuid = ServerContext.useStoreState((state) => state.server.data!.uuid);

    useEffect(() => {
        setLoading(true);
        clearFlashes('minecraft:version');

        getMinecraftFlavors(uuid)
            .then((res) => {
                setFlavors(res);
                setSelectedFlavor(res[0].id);
            })
            .catch((error) => {
                console.error(error);
                addError({ key: 'minecraft:version', message: httpErrorToHuman(error) });
            })
            .then(() => setLoading(false));
    }, [uuid]);

    useEffect(() => {
        if (!selectedFlavor) {
            return;
        }

        setLoading(true);
        clearFlashes('minecraft:version');

        getMinecraftFlavorVersions(uuid, selectedFlavor)
            .then((res) => {
                setFlavorVersions(res);
                setSelectedFlavorVersion(res[0].id);
            })
            .catch((error) => {
                console.error(error);
                addError({ key: 'minecraft:version', message: httpErrorToHuman(error) });
            })
            .then(() => setLoading(false));
    }, [selectedFlavor]);

    const doChange = () => {
        if (!selectedFlavor || !selectedFlavorVersion) {
            return;
        }

        setLoading(true);
        clearFlashes('minecraft:version');

        changeMinecraftVersion(uuid, selectedFlavor, selectedFlavorVersion)
            .catch((error) => {
                console.error(error);
                addError({ key: 'minecraft:version', message: httpErrorToHuman(error) });
            })
            .then(() => setLoading(false));
    };

    return (
        <TitledGreyBox title={'Change Minecraft Version'} css={tw`relative`}>
            <SpinnerOverlay visible={loading} />
            <FlashMessageRender key={'minecraft:version'} />
            <div>
                <Label htmlFor={'flavor'}>Minecraft Flavour</Label>
                <Select name={'flavor'} value={selectedFlavor} onChange={(e) => setSelectedFlavor(e.currentTarget.value)}>
                    {flavors.map((flavor) => (
                        <option key={flavor.id} value={flavor.id}>
                            {flavor.name}
                        </option>
                    ))}
                </Select>
            </div>
            <div css={tw`mt-4`}>
                <Label htmlFor={'version'}>Flavour Version</Label>
                <Select name={'version'} value={selectedFlavorVersion} onChange={(e) => setSelectedFlavorVersion(e.currentTarget.value)}>
                    {flavorVersions.map((version) => (
                        <option key={version.id} value={version.id}>
                            {version.name}
                        </option>
                    ))}
                </Select>
            </div>
            <div css={tw`mt-6 text-right`}>
                <Button type={'submit'} disabled={!selectedFlavorVersion} onClick={doChange}>
                    Change Version
                </Button>
            </div>
        </TitledGreyBox>
    );
};

export default () => {
    return <MinecraftVersionBox />;
};
