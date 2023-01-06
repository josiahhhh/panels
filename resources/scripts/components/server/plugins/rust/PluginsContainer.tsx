import React, { useEffect, useState } from 'react';
import tw from 'twin.macro';
import { httpErrorToHuman } from '@/api/http';
import useFlash from '@/plugins/useFlash';
import { ServerContext } from '@/state/server';
import Spinner from '@/components/elements/Spinner';
import FlashMessageRender from '@/components/FlashMessageRender';
import ServerContentBlock from '@/components/elements/ServerContentBlock';
import PluginRow from '@/components/server/plugins/rust/PluginRow';
import Button from '@/components/elements/Button';
import getRustPlugins, { RustPlugin } from '@/api/server/iceline/plugins/rust/getRustPlugins';
import SpinnerOverlay from '@/components/elements/SpinnerOverlay';
import Input from '@/components/elements/Input';
import MessageBox from '@/components/MessageBox';
import getRustStatus, { RustStatus } from '@/api/server/iceline/plugins/rust/getRustStatus';
import getInstalledRustPlugins, { InstalledRustPlugin } from '@/api/server/iceline/plugins/rust/getInstalledRustPlugins';
import InstalledPluginRow from '@/components/server/plugins/rust/InstalledPluginRow';

export default () => {
    const { addError, clearFlashes } = useFlash();

    const [rustStatus, setRustStatus] = useState(null as RustStatus | null);

    const [loadingInstalledPlugins, setLoadingInstalledPlugin] = useState(true);
    const [installedPlugins, setInstalledPlugins] = useState([] as InstalledRustPlugin[]);

    const [plugins, setPlugins] = useState<RustPlugin[]>([]);
    const [page, setPage] = useState(1);
    const [loading, setLoading] = useState(true);
    const [updating, setUpdating] = useState(false);

    const [nextSearchQuery, setNextSearchQuery] = useState('');
    const [searchQuery, setSearchQuery] = useState('');

    const uuid = ServerContext.useStoreState((state) => state.server.data!.uuid);

    useEffect(() => {
        getRustStatus(uuid)
            .then((status) => {
                setRustStatus(status);
            })
            .catch((error) => {
                console.error(error);
                addError({ key: 'server:plugins', message: httpErrorToHuman(error) });
            })
            .then(() => {
                setLoading(false);
                setUpdating(false);
            });
    }, [uuid]);

    useEffect(() => {
        setUpdating(true);
        clearFlashes('server:plugins');

        if (!searchQuery || searchQuery === '') {
            getRustPlugins(uuid, {
                page: page,
            })
                .then((plugins) => {
                    setPlugins(plugins);
                })
                .catch((error) => {
                    console.error(error);
                    addError({ key: 'server:plugins', message: httpErrorToHuman(error) });
                })
                .then(() => {
                    setLoading(false);
                    setUpdating(false);
                });
        } else {
            getRustPlugins(uuid, {
                query: searchQuery,
                page: page,
            })
                .then((plugins) => {
                    setPlugins(plugins);
                })
                .catch((error) => {
                    console.error(error);
                    addError({ key: 'server:plugins', message: httpErrorToHuman(error) });
                })
                .then(() => {
                    setLoading(false);
                    setUpdating(false);
                });
        }
    }, [page, searchQuery]);

    let searchTimer: NodeJS.Timeout | null = null;

    const searchKeyDown = () => {
        if (searchTimer) {
            clearTimeout(searchTimer);
        }
    };

    const searchKeyUp = () => {
        searchTimer = setTimeout(() => {
            setSearchQuery(nextSearchQuery);
        }, 500);
    };

    useEffect(() => {
        setLoadingInstalledPlugin(true);
        getInstalledRustPlugins(uuid)
            .then((plugins) => {
                setInstalledPlugins(plugins.plugins);
            })
            .catch((error) => {
                console.error(error);
                addError({ key: 'server:plugins', message: httpErrorToHuman(error) });
            })
            .then(() => {
                setLoadingInstalledPlugin(false);
            });
    }, [uuid]);

    return (
        <ServerContentBlock title={'Plugins'} css={tw`flex flex-wrap`}>
            <div css={tw`w-full`}>
                <>
                    {loadingInstalledPlugins ? (
                        <div css={tw`my-10 flex flex-col items-center justify-center w-full`}>
                            <Spinner size={'large'} />
                        </div>
                    ) : (
                        <div css={tw`w-full`}>
                            {installedPlugins.length > 0 && (
                                <div css={tw`grid grid-cols-1 lg:grid-cols-2 gap-4 w-full mb-6`}>
                                    {installedPlugins.map((plugin) => (
                                        <InstalledPluginRow key={plugin.filename} filename={plugin.filename} name={plugin.name} plugin={plugin.manifest} />
                                    ))}
                                </div>
                            )}
                        </div>
                    )}
                </>
            </div>
            {!rustStatus?.foundOxide && (
                <div css={tw`mb-6`}>
                    <MessageBox type='info' title='Info'>
                        Before installing a plugin, ensure you go to the mod manager and install uMod - otherwise the plugins will not appear on your server.
                    </MessageBox>
                </div>
            )}
            <div css={tw`flex flex-row justify-between items-center mb-4 w-full`}>
                <h1 css={tw`w-full text-lg`}>Available Plugins</h1>
                <Input placeholder={'Search plugins'} onKeyDown={searchKeyDown} onKeyUp={searchKeyUp} onChange={(e) => setNextSearchQuery(e.target.value)} />
            </div>
            <FlashMessageRender byKey={'server:plugins'} css={tw`mb-4`} />
            <>
                {loading ? (
                    <div css={tw`my-10 flex flex-col items-center justify-center w-full`}>
                        <Spinner size={'large'} />
                    </div>
                ) : (
                    <div css={tw`w-full`}>
                        <div css={tw`relative w-full`}>
                            <SpinnerOverlay visible={updating} />
                            <div css={tw`grid grid-cols-1 lg:grid-cols-2 gap-4 w-full`}>
                                {plugins.map((plugin) => (
                                    <PluginRow key={plugin.slug} plugin={plugin} />
                                ))}
                            </div>
                        </div>
                        <div css={tw`flex flex-row items-center mt-6`}>
                            <Button isSecondary={true} size={'xsmall'} onClick={() => page > 1 && setPage(page - 1)}>
                                Previous Page
                            </Button>
                            <span css={tw`px-4`}>Page {page}</span>
                            <Button size={'xsmall'} onClick={() => setPage(page + 1)}>
                                Next Page
                            </Button>
                        </div>
                    </div>
                )}
            </>
        </ServerContentBlock>
    );
};
