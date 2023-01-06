import React, { useEffect, useState } from 'react';
import Modal, { RequiredModalProps } from '@/components/elements/Modal';
import { Field as FormikField, Form, Formik, FormikHelpers, useFormikContext } from 'formik';
import { object, string } from 'yup';
import Field from '@/components/elements/Field';
import FormikFieldWrapper from '@/components/elements/FormikFieldWrapper';
import useFlash from '@/plugins/useFlash';
import FlashMessageRender from '@/components/FlashMessageRender';
import Button from '@/components/elements/Button';
import tw from 'twin.macro';
import { ServerContext } from '@/state/server';
import createServerDatabaseBackup from '@/api/server/iceline/backups/createServerDatabaseBackup';
import getServerDatabases from '@/api/server/databases/getServerDatabases';
import { useDeepMemoize } from '@/plugins/useDeepMemoize';
import { httpErrorToHuman } from '@/api/http';
import Spinner from '@/components/elements/Spinner';
import Fade from '@/components/elements/Fade';

import Select from '@/components/elements/Select';
import getServerDatabaseBackups from '@/api/server/iceline/backups/getServerDatabaseBackups';

interface Values {
    database: string;
    name: string;
}

const ModalContent = ({ ...props }: RequiredModalProps) => {
    const { setFieldValue, setFieldTouched, isSubmitting } = useFormikContext<Values>();

    const uuid = ServerContext.useStoreState((state) => state.server.data!.uuid);

    const { addError, clearFlashes } = useFlash();
    const [loading, setLoading] = useState(true);

    const databases = useDeepMemoize(ServerContext.useStoreState((state) => state.databases.data));
    const setDatabases = ServerContext.useStoreActions((state) => state.databases.setDatabases);

    useEffect(() => {
        setLoading(!databases.length);
        clearFlashes('databases');

        getServerDatabases(uuid)
            .then((databases) => {
                setDatabases(databases);
                if (databases.length > 0) {
                    setFieldValue('database', databases[0].id);
                    setFieldTouched('database', false);
                }
            })
            .catch((error) => {
                console.error(error);
                addError({ key: 'databases', message: httpErrorToHuman(error) });
            })
            .then(() => setLoading(false));
    }, []);

    return (
        <Modal {...props} showSpinnerOverlay={isSubmitting}>
            <Form>
                <FlashMessageRender byKey={'backups:create'} css={tw`mb-4`} />
                <h2 css={tw`text-2xl mb-6`}>Create database backup</h2>
                <div css={tw`mb-6`}>
                    <Field name={'name'} label={'Backup name'} description={'If provided, the name that should be used to reference this backup.'} />
                </div>
                <div css={tw`mb-6`}>
                    {loading ? (
                        <Spinner size={'large'} centered />
                    ) : (
                        <Fade timeout={150}>
                            <>
                                {databases.length > 0 ? (
                                    <>
                                        <FormikFieldWrapper name={'database'}>
                                            <FormikField as={Select} name={'database'}>
                                                {databases.map((database) => (
                                                    <option key={database.id} value={database.id}>
                                                        {database.name}
                                                    </option>
                                                ))}
                                            </FormikField>
                                        </FormikFieldWrapper>
                                        <div css={tw`flex justify-end mt-6`}>
                                            <Button type={'submit'} disabled={isSubmitting}>
                                                Start backup
                                            </Button>
                                        </div>
                                    </>
                                ) : (
                                    <p css={tw`text-center text-sm text-neutral-400 mt-6`}>This server has no databases</p>
                                )}
                            </>
                        </Fade>
                    )}
                </div>
            </Form>
        </Modal>
    );
};

export default () => {
    const uuid = ServerContext.useStoreState((state) => state.server.data!.uuid);
    const { clearFlashes, clearAndAddHttpError } = useFlash();
    const [visible, setVisible] = useState(false);
    const { mutate } = getServerDatabaseBackups();

    useEffect(() => {
        clearFlashes('backups:create');
    }, [visible]);

    const submit = ({ database, name }: Values, { setSubmitting }: FormikHelpers<Values>) => {
        clearFlashes('backups:create');
        createServerDatabaseBackup(uuid, database, name)
            .then((backup) => {
                mutate((data) => ({ ...data, items: data.items.concat(backup) }), false);
                setVisible(false);
            })
            .catch((error) => {
                clearAndAddHttpError({ key: 'backups:create', error });
                setSubmitting(false);
            });
    };

    return (
        <>
            {visible && (
                <Formik
                    onSubmit={submit}
                    initialValues={{ database: '', name: '' }}
                    validationSchema={object().shape({
                        database: string().defined().min(1),
                        name: string().max(191),
                    })}
                >
                    <ModalContent appear visible={visible} onDismissed={() => setVisible(false)} />
                </Formik>
            )}
            <Button css={tw`w-full sm:w-auto`} onClick={() => setVisible(true)}>
                Create database backup
            </Button>
        </>
    );
};
