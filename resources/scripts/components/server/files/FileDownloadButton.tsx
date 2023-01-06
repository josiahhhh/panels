import React, { useEffect, useState } from 'react';
import Modal from '@/components/elements/Modal';
import { ServerContext } from '@/state/server';
import { Form, Formik, FormikHelpers } from 'formik';
import Field from '@/components/elements/Field';
import { join } from 'path';
import { object, string } from 'yup';
import tw from 'twin.macro';
import Button from '@/components/elements/Button';
import useFlash from '@/plugins/useFlash';
import useFileManagerSwr from '@/plugins/useFileManagerSwr';
import { WithClassname } from '@/components/types';
import FlashMessageRender from '@/components/FlashMessageRender';
import fileDownload from '@/api/server/files/fileDownload';

interface Values {
    downloadUrl: string;
}

const schema = object().shape({
    downloadUrl: string().url().required(),
});

export default ({ className }: WithClassname) => {
    const uuid = ServerContext.useStoreState((state) => state.server.data!.uuid);
    const { clearFlashes, clearAndAddHttpError } = useFlash();
    const [visible, setVisible] = useState(false);

    const { mutate } = useFileManagerSwr();
    const directory = ServerContext.useStoreState((state) => state.files.directory);

    useEffect(() => {
        if (!visible) return;

        return () => {
            clearFlashes('files:directory-modal');
        };
    }, [visible]);

    const submit = ({ downloadUrl }: Values, { setSubmitting }: FormikHelpers<Values>) => {
        fileDownload(uuid, directory, downloadUrl)
            .then(() => mutate())
            .then(() => setVisible(false))
            .catch((error) => {
                setSubmitting(false);
                clearAndAddHttpError({ key: 'files:directory-modal', error });
            });
    };

    return (
        <>
            <Formik onSubmit={submit} validationSchema={schema} initialValues={{ downloadUrl: '' }}>
                {({ resetForm, isSubmitting }) => (
                    <Modal
                        visible={visible}
                        dismissable={!isSubmitting}
                        showSpinnerOverlay={isSubmitting}
                        onDismissed={() => {
                            setVisible(false);
                            resetForm();
                        }}
                    >
                        <FlashMessageRender key={'files:directory-modal'} />
                        <Form css={tw`m-0`}>
                            <Field autoFocus id={'downloadUrl'} name={'downloadUrl'} label={'Download URL'} />
                            <p css={tw`text-xs mt-2 text-neutral-400 break-all`}>
                                <span css={tw`text-neutral-200`}>The file will be downloaded to</span>
                                &nbsp;/home/container/
                                <span css={tw`text-cyan-200`}>{join(directory).replace(/^(\.\.\/|\/)+/, '')}</span>
                            </p>
                            <div css={tw`flex justify-end`}>
                                <Button css={tw`mt-8`}>Download File</Button>
                            </div>
                        </Form>
                    </Modal>
                )}
            </Formik>
            <Button color={'green'} isSecondary onClick={() => setVisible(true)} className={className}>
                Download File
            </Button>
        </>
    );
};
