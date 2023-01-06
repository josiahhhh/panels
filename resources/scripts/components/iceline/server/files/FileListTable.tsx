import { FileObject } from '@/api/server/files/loadDirectory';
import tw from 'twin.macro';
import React from 'react';
import useFileManagerSwr from '@/plugins/useFileManagerSwr';
import Spinner from '@/components/elements/Spinner';
import { CSSTransition } from 'react-transition-group';
import FileTableHeader from '@/components/iceline/server/files/FileTableHeader';
import FileTableRow from '@/components/iceline/server/files/FileTableRow';

const sortFiles = (files: FileObject[]): FileObject[] => {
    return files.sort((a, b) => a.name.localeCompare(b.name)).sort((a, b) => (a.isFile === b.isFile ? 0 : a.isFile ? 1 : -1));
};

export default () => {
    const { data: files } = useFileManagerSwr();

    return (
        <div
            style={{
                backgroundColor: '#171A33',
            }}
            css={tw`rounded`}
        >
            {!files ? (
                <Spinner size={'large'} centered />
            ) : (
                <>
                    {!files.length ? (
                        <p css={tw`text-sm text-neutral-400 text-center`}>This directory seems to be empty.</p>
                    ) : (
                        <CSSTransition classNames={'fade'} timeout={150} appear in>
                            <div>
                                {files.length > 250 && (
                                    <div css={tw`rounded bg-yellow-400 mb-px p-3`}>
                                        <p css={tw`text-yellow-900 text-sm text-center`}>
                                            This directory is too large to display in the browser, limiting the output to the first 250 files.
                                        </p>
                                    </div>
                                )}
                                <FileTableHeader />
                                {sortFiles(files.slice(0, 250)).map((file) => (
                                    <FileTableRow key={file.name} file={file} />
                                ))}
                            </div>
                        </CSSTransition>
                    )}
                </>
            )}
        </div>
    );
};
