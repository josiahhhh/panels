import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import { faFileAlt, faFileArchive, faFileImport, faFolder } from '@fortawesome/free-solid-svg-icons';
import { cleanDirectoryPath } from '@/helpers';
import { bytesToString } from '@/lib/formatters';
import { differenceInHours, format, formatDistanceToNow } from 'date-fns';
import React, { memo } from 'react';
import { FileObject } from '@/api/server/files/loadDirectory';
import FileDropdownMenu from '@/components/server/files/FileDropdownMenu';
import { ServerContext } from '@/state/server';
import { NavLink, useHistory, useRouteMatch } from 'react-router-dom';
import tw from 'twin.macro';
import isEqual from 'react-fast-compare';
import styled from 'styled-components/macro';
import SelectFileCheckbox from '@/components/server/files/SelectFileCheckbox';
import { usePermissions } from '@/plugins/usePermissions';

const Row = styled.div`
    ${tw`flex text-sm hover:text-neutral-100 cursor-pointer items-center no-underline hover:bg-icelinebox-700`};
`;

const Clickable: React.FC<{ file: FileObject }> = memo(({ file, children }) => {
    const [canReadContents] = usePermissions(['file.read-content']);
    const directory = ServerContext.useStoreState((state) => state.files.directory);

    const history = useHistory();
    const match = useRouteMatch();

    const destination = cleanDirectoryPath(`${directory}/${file.name}`)
        .split('/')
        .map((v) => encodeURI(v))
        .join('/');

    const onRowClick = (e: React.MouseEvent<HTMLAnchorElement, MouseEvent>) => {
        // Don't rely on the onClick to work with the generated URL. Because of the way this
        // component re-renders you'll get redirected into a nested directory structure since
        // it'll cause the directory variable to update right away when you click.
        //
        // Just trust me future me, leave this be.
        if (!file.isFile) {
            e.preventDefault();
            history.push(`#${destination}`);
        }
    };

    return !canReadContents || (file.isFile && !file.isEditable()) ? (
        <div css={tw`flex flex-1 text-neutral-300 no-underline p-3 cursor-default overflow-hidden truncate`}>{children}</div>
    ) : (
        <NavLink
            to={`${match.url}/${file.isFile ? 'edit/' : ''}#${destination}`}
            css={tw`flex flex-1 text-neutral-300 no-underline p-3 overflow-hidden truncate`}
            onClick={onRowClick}
        >
            {children}
        </NavLink>
    );
}, isEqual);

const FileTableRow = ({ file }: { file: FileObject }) => (
    <Row
        key={file.name}
        onContextMenu={(e) => {
            e.preventDefault();
            window.dispatchEvent(new CustomEvent(`pterodactyl:files:ctx:${file.key}`, { detail: e.clientX }));
        }}
    >
        <SelectFileCheckbox name={file.name} />
        <Clickable file={file}>
            <div css={tw`flex-none self-center text-neutral-400 mr-4 text-lg pl-3 ml-6`}>
                {file.isFile ? <FontAwesomeIcon icon={file.isSymlink ? faFileImport : file.isArchiveType() ? faFileArchive : faFileAlt} /> : <FontAwesomeIcon icon={faFolder} />}
            </div>
            <div css={tw`flex-1 truncate pt-1`}>{file.name}</div>
            {file.isFile && <div css={tw`w-1/6 text-right mr-4 hidden sm:block pt-1`}>{bytesToString(file.size)}</div>}
            <div css={tw`w-1/5 text-right mr-4 hidden md:block pt-1`} title={file.modifiedAt.toString()}>
                {Math.abs(differenceInHours(file.modifiedAt, new Date())) > 48
                    ? format(file.modifiedAt, 'MMM do, yyyy h:mma')
                    : formatDistanceToNow(file.modifiedAt, { addSuffix: true })}
            </div>
        </Clickable>
        <FileDropdownMenu file={file} />
    </Row>
);

export default memo(FileTableRow, (prevProps, nextProps) => {
    /* eslint-disable @typescript-eslint/no-unused-vars */
    const { isArchiveType, isEditable, ...prevFile } = prevProps.file;
    const { isArchiveType: nextIsArchiveType, isEditable: nextIsEditable, ...nextFile } = nextProps.file;
    /* eslint-enable @typescript-eslint/no-unused-vars */

    return isEqual(prevFile, nextFile);
});
