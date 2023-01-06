import React, { useEffect, useState } from 'react';
import Modal from '@/components/elements/Modal';
import FlashMessageRender from '@/components/FlashMessageRender';
import { Formik, Form, FormikHelpers } from 'formik';
import { object, string } from 'yup';
import tw from 'twin.macro';
import Button from '@/components/elements/Button';
import { ServerContext } from '@/state/server';
import useFlash from '@/plugins/useFlash';
import { SocketEvent, SocketRequest } from '@/components/server/events';
import Field from '@/components/elements/Field';
import updateStartupVariable from '@/api/server/updateStartupVariable';

interface Values {
    artifact: string;
    version: string;
    license: string;
}

const FiveMSetupGuideFeature = () => {
    const [visible, setVisible] = useState(false);

    const uuid = ServerContext.useStoreState((state) => state.server.data!.uuid);
    const status = ServerContext.useStoreState((state) => state.status.value);

    const { clearFlashes, clearAndAddHttpError } = useFlash();
    const { connected, instance } = ServerContext.useStoreState((state) => state.socket);

    useEffect(() => {
        if (!connected || !instance || status === 'running') return;

        const errors = ['Could not authenticate server license key.', 'Invalid key specified.'];

        const listener = (line: string) => {
            if (errors.some((p) => line.toLowerCase().includes(p.toLowerCase()))) {
                setVisible(true);
            }
        };

        instance.addListener(SocketEvent.CONSOLE_OUTPUT, listener);

        return () => {
            instance.removeListener(SocketEvent.CONSOLE_OUTPUT, listener);
        };
    }, [connected, instance, status]);

    useEffect(() => {
        clearFlashes('feature:fivem');
    }, []);

    const setupFiveMServer = ({ artifact, version, license }: Values, { setSubmitting }: FormikHelpers<Values>) => {
        let setupPerformed = true;

        updateStartupVariable(uuid, 'FIVEM_VERSION', artifact)
            .then(() => {
                setSubmitting(false);
            })
            .catch((error) => {
                clearAndAddHttpError({ key: 'feature:fivem', error });
                setSubmitting(false);
                setupPerformed = false;
            });

        updateStartupVariable(uuid, 'GAMEBUILD', version)
            .then(() => {
                setSubmitting(false);
            })
            .catch((error) => {
                clearAndAddHttpError({ key: 'feature:fivem', error });
                setSubmitting(false);
                setupPerformed = false;
            });

        updateStartupVariable(uuid, 'FIVEM_LICENSE', license)
            .then(() => {
                setSubmitting(false);
            })
            .catch((error) => {
                clearAndAddHttpError({ key: 'feature:fivem', error });
                setSubmitting(false);
                setupPerformed = false;
            });

        if (setupPerformed) {
            setVisible(false);

            if (instance) {
                instance.send(SocketRequest.SET_STATE, 'restart');
            }
        }
    };

    return (
        <Formik
            onSubmit={setupFiveMServer}
            initialValues={{
                artifact: '',
                version: '',
                license: '',
            }}
            validationSchema={object().shape({
                artifact: string().required(),
                version: string().required(),
                license: string().required(),
            })}
        >
            {({ isSubmitting }) => (
                <Modal visible={visible} onDismissed={() => setVisible(false)} showSpinnerOverlay={isSubmitting}>
                    <FlashMessageRender key={'feature:fivem'} css={tw`pb-4`} />
                    <Form>
                        <h2 css={tw`text-2xl mb-4 text-neutral-100`}>Setup your FiveM server!</h2>
                        <p css={`mt-4`}>Server not setup, please follow these steps:</p>

                        <div css={tw`w-full mt-4`}>
                            <Field name={'artifact'} label={'Artifact Version'} placeholder={'Enter the artifact version...'} autoFocus />
                        </div>
                        <div css={tw`w-full mt-4`}>
                            <Field name={'version'} label={'Game Build Version'} placeholder={'Enter the server game version...'} />
                        </div>
                        <div css={tw`w-full mt-4`}>
                            <Field name={'license'} label={'License Key'} placeholder={'Enter your server license key...'} />
                        </div>

                        <div css={tw`mt-8 sm:flex items-center justify-end`}>
                            <Button type={'submit'} css={tw`mt-4 sm:mt-0 sm:ml-4 w-full sm:w-auto`} isLoading={isSubmitting} disabled={isSubmitting}>
                                Setup
                            </Button>
                        </div>
                    </Form>
                </Modal>
            )}
        </Formik>
    );
};

export default FiveMSetupGuideFeature;
