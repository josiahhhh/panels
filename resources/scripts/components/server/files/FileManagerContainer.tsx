import React, { useEffect } from 'react';
import { httpErrorToHuman } from '@/api/http';
import FileManagerBreadcrumbs from '@/components/iceline/server/files/FileManagerBreadcrumbs';
import NewDirectoryButton from '@/components/iceline/server/files/NewDirectoryButton';
import { NavLink, useLocation } from 'react-router-dom';
import Can from '@/components/elements/Can';
import { ServerError } from '@/components/elements/ScreenBlock';
import tw from 'twin.macro';
import { Button } from '@/components/elements/button/index';
import { ServerContext } from '@/state/server';
import useFileManagerSwr from '@/plugins/useFileManagerSwr';
import MassActionsBar from '@/components/server/files/MassActionsBar';
import UploadButton from '@/components/iceline/server/files/UploadButton';
import ServerContentBlock from '@/components/elements/ServerContentBlock';
import FileManagerStatus from '@/components/server/files/FileManagerStatus';
import { useStoreActions } from '@/state/hooks';
import ErrorBoundary from '@/components/elements/ErrorBoundary';
import ActionPrompt from '@/components/iceline/server/ActionPrompt';
import { useHistory } from 'react-router';
import FileListTable from '@/components/iceline/server/files/FileListTable';
import { hashToPath } from '@/helpers';
import { FileActionCheckbox } from '@/components/iceline/server/files/SelectFileCheckbox';
import FileDownloadButton from '@/components/server/files/FileDownloadButton';

export default () => {
    const history = useHistory();

    const id = ServerContext.useStoreState((state) => state.server.data!.id);
    const uuid = ServerContext.useStoreState((state) => state.server.data!.uuid);
    const { hash } = useLocation();
    const { data: files, error, mutate } = useFileManagerSwr();
    const directory = ServerContext.useStoreState((state) => state.files.directory);
    const clearFlashes = useStoreActions((actions) => actions.flashes.clearFlashes);
    const setDirectory = ServerContext.useStoreActions((actions) => actions.files.setDirectory);

    const setSelectedFiles = ServerContext.useStoreActions((actions) => actions.files.setSelectedFiles);
    const selectedFilesLength = ServerContext.useStoreState((state) => state.files.selectedFiles.length);

    useEffect(() => {
        clearFlashes('files');
        setSelectedFiles([]);
        setDirectory(hashToPath(hash));
    }, [hash]);

    useEffect(() => {
        mutate();
    }, [directory]);

    const onSelectAllClick = (e: React.ChangeEvent<HTMLInputElement>) => {
        setSelectedFiles(e.currentTarget.checked ? files?.map((file) => file.name) || [] : []);
    };

    if (error) {
        return <ServerError message={httpErrorToHuman(error)} onRetry={() => mutate()} />;
    }

    return (
        <ServerContentBlock title={'File Manager'} showFlashKey={'files'}>
            <ErrorBoundary>
                <div css={tw`flex flex-col md:flex-row justify-between mb-4`}>
                    <FileManagerBreadcrumbs
                        renderLeft={
                            <FileActionCheckbox
                                type={'checkbox'}
                                css={tw`mx-4`}
                                checked={selectedFilesLength === (files?.length === 0 ? -1 : files?.length)}
                                onChange={onSelectAllClick}
                            />
                        }
                    />
                    <Can action={'file.create'}>
                        <div css={tw`flex flex-wrap-reverse justify-end`}>
                            <FileManagerStatus />
                            <NewDirectoryButton css={tw`w-full flex-none mt-4 sm:mt-0 sm:w-auto sm:mr-4`} />
                            <FileDownloadButton css={tw`w-full flex-none mt-4 sm:mt-0 sm:w-auto sm:mr-4`} />
                            <UploadButton css={tw`flex-1 mr-4 sm:flex-none sm:mt-0`} />
                            <NavLink to={`/server/${id}/files/new${window.location.hash}`} css={tw`flex-1 sm:flex-none sm:mt-0`}>
                                <Button size={Button.Sizes.Small} variant={Button.Variants.Secondary} css={tw`w-full`}>
                                    New File
                                </Button>
                            </NavLink>
                        </div>
                    </Can>
                </div>
            </ErrorBoundary>
            <FileListTable />
            <MassActionsBar />
            <div css={tw`grid grid-cols-1 xl:grid-cols-2 gap-x-6 gap-y-6 w-full mt-4`}>
                <ActionPrompt title={'Create Backup'} subtitle={'Create a backup of all your server files that you can download or restore at any time.'}>
                    <Button
                        size={Button.Sizes.Small}
                        css={tw`mr-2`}
                        onClick={(e) => {
                            e.preventDefault();

                            history.push('/server/' + uuid + '/backups');
                        }}
                    >
                        Create Backup
                    </Button>
                </ActionPrompt>
                <ActionPrompt title={'SFTP Details'} subtitle={'View the SFTP connection details on the settings page for managing files on your server.'}>
                    <Button
                        size={Button.Sizes.Small}
                        css={tw`mr-2`}
                        onClick={(e) => {
                            e.preventDefault();

                            history.push('/server/' + uuid + '/settings');
                        }}
                    >
                        SFTP Details
                    </Button>
                </ActionPrompt>
            </div>
        </ServerContentBlock>
    );
};
