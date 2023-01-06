import React from 'react';
import { ServerContext } from '@/state/server';
import { useRouteMatch } from 'react-router-dom';
import tw from 'twin.macro';
import styled from 'styled-components/macro';
import { FileActionCheckbox } from '@/components/iceline/server/files/SelectFileCheckbox';
import useFileManagerSwr from '@/plugins/useFileManagerSwr';
import { faEllipsisH } from '@fortawesome/free-solid-svg-icons';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';

const Row = styled.div`
    ${tw`flex text-sm cursor-pointer items-center no-underline`};
`;

const FileTableHeader = () => {
    const { params } = useRouteMatch<Record<string, string>>();

    const { data: files } = useFileManagerSwr();
    const setSelectedFiles = ServerContext.useStoreActions((actions) => actions.files.setSelectedFiles);
    const selectedFilesLength = ServerContext.useStoreState((state) => state.files.selectedFiles.length);

    const onSelectAllClick = (e: React.ChangeEvent<HTMLInputElement>) => {
        setSelectedFiles(e.currentTarget.checked ? files?.map((file) => file.name) || [] : []);
    };

    return (
        <Row>
            <label css={tw`flex-none p-4 absolute self-center z-30 cursor-pointer`}>
                {files && files.length > 0 && !params?.action ? (
                    <FileActionCheckbox type={'checkbox'} checked={selectedFilesLength === (files ? files.length : -1)} onChange={onSelectAllClick} />
                ) : (
                    <div css={tw`w-12`} />
                )}
            </label>
            <div css={tw`flex flex-1 text-neutral-300 no-underline p-3 uppercase overflow-hidden truncate`}>
                <div
                    css={tw`flex-none self-center text-neutral-400 mr-4 text-lg pl-3 ml-6`}
                    style={{
                        width: '28px',
                    }}
                />
                <div css={tw`flex-1 truncate`}>Name</div>
                <div css={tw`w-1/6 text-right mr-4 hidden sm:block`}>Size</div>
                <div css={tw`w-1/5 text-right mr-4 hidden md:block`} title={'Last Modified'}>
                    Last Modified
                </div>
            </div>
            <div css={tw`p-3`}>
                <FontAwesomeIcon icon={faEllipsisH} css={tw`opacity-0`} />
            </div>
        </Row>
    );
};

export default FileTableHeader;
