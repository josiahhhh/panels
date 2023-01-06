import React, { useEffect, useState } from 'react';
import tw from 'twin.macro';
import { httpErrorToHuman } from '@/api/http';
import useFlash from '@/plugins/useFlash';
import Spinner from '@/components/elements/Spinner';
import FlashMessageRender from '@/components/FlashMessageRender';
import ServerContentBlock from '@/components/elements/ServerContentBlock';
import PluginRow, { SpigetResource } from '@/components/server/plugins/minecraft/PluginRow';
import Button from '@/components/elements/Button';

// Build custom axios handler because the default
// one in @/api/http injects extra headers.
import axios, { AxiosInstance } from 'axios';
import SpinnerOverlay from '@/components/elements/SpinnerOverlay';
import Input from '@/components/elements/Input';
const http: AxiosInstance = axios.create({
    timeout: 20000,
    headers: {
        'X-Requested-With': 'XMLHttpRequest',
        Accept: 'application/json',
        'Content-Type': 'application/json',
    },
});

export default () => {
    const { addError, clearFlashes } = useFlash();

    const [plugins, setPlugins] = useState<SpigetResource[]>([]);
    const [page, setPage] = useState(1);
    const [loading, setLoading] = useState(true);
    const [updating, setUpdating] = useState(false);

    const [nextSearchQuery, setNextSearchQuery] = useState('');
    const [searchQuery, setSearchQuery] = useState('');

    const pluginListLimit = 25;

    useEffect(() => {
        setUpdating(true);
        clearFlashes('server:plugins');

        if (!searchQuery || searchQuery === '') {
            http.get('https://api.spiget.org/v2/resources/free', {
                params: {
                    size: pluginListLimit,
                    page,
                    sort: '-downloads',
                },
            })
                .then(({ data }) => {
                    setPlugins(data);
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
            http.get('https://api.spiget.org/v2/search/resources/' + encodeURI(searchQuery), {
                params: {
                    size: pluginListLimit,
                    page,
                    sort: '-downloads',
                },
            })
                .then(({ data }) => {
                    setPlugins(data);
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

    return (
        <ServerContentBlock title={'Plugins'} css={tw`flex flex-wrap`}>
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
                    <div>
                        <div css={tw`relative`}>
                            <SpinnerOverlay visible={updating} />
                            <div css={tw`grid grid-cols-1 lg:grid-cols-2 gap-4`}>
                                {plugins.map((plugin) => (
                                    <PluginRow key={plugin.id} resource={plugin} />
                                ))}
                            </div>
                        </div>
                        <div css={tw`flex flex-row items-center mt-6`}>
                            <Button isSecondary={true} size={'xsmall'} onClick={() => page > 1 && setPage(page - 1)} disabled={page <= 0}>
                                Previous Page
                            </Button>
                            <span css={tw`px-4`}>Page {page}</span>
                            <Button size={'xsmall'} onClick={() => setPage(page + 1)} disabled={plugins.length < pluginListLimit}>
                                Next Page
                            </Button>
                        </div>
                    </div>
                )}
            </>
        </ServerContentBlock>
    );
};
