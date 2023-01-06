import React, { useEffect, useRef, useState } from 'react';
import { Link, RouteComponentProps } from 'react-router-dom';
import login from '@/api/auth/login';
import LoginFormContainer from '@/components/auth/LoginFormContainer';
import { useStoreState } from 'easy-peasy';
import { Formik, FormikHelpers } from 'formik';
import { object, string } from 'yup';
import tw from 'twin.macro';
import Button from '@/components/elements/Button';
import Reaptcha from 'reaptcha';
import useFlash from '@/plugins/useFlash';
import http from '@/api/http';
import LoginField from '@/components/iceline/LoginField';

interface Values {
    username: string;
    password: string;
}

const LoginContainer = ({ history }: RouteComponentProps) => {
    const ref = useRef<Reaptcha>(null);
    const [token, setToken] = useState('');

    const { clearFlashes, clearAndAddHttpError, addFlash } = useFlash();
    const { enabled: recaptchaEnabled, siteKey } = useStoreState((state) => state.settings.data!.recaptcha);

    useEffect(() => {
        clearFlashes();
    }, []);

    useEffect(() => {
        const search = window.location.search;
        const params = new URLSearchParams(search);
        const queryError = params.get('error');
        if (queryError) {
            addFlash({
                type: 'error',
                message: queryError,
            });
        }
    }, [window.location.search]);

    const useWHMCS = () => {
        http.get('/auth/oauth/whmcs').then((response) => {
            window.location = response.data.redirect;
        });
    };

    const onSubmit = (values: Values, { setSubmitting }: FormikHelpers<Values>) => {
        clearFlashes();

        // If there is no token in the state yet, request the token and then abort this submit request
        // since it will be re-submitted when the recaptcha data is returned by the component.
        if (recaptchaEnabled && !token) {
            ref.current!.execute().catch((error) => {
                console.error(error);

                setSubmitting(false);
                clearAndAddHttpError({ error });
            });

            return;
        }

        login({ ...values, recaptchaData: token })
            .then((response) => {
                if (response.complete) {
                    // @ts-expect-error this is valid
                    window.location = response.intended || '/';
                    return;
                }

                history.replace('/auth/login/checkpoint', { token: response.confirmationToken });
            })
            .catch((error) => {
                console.error(error);

                setToken('');
                if (ref.current) ref.current.reset();

                setSubmitting(false);
                clearAndAddHttpError({ error });
            });
    };

    return (
        <div
            css={tw`grid grid-cols-2 h-screen`}
            style={{
                backgroundImage: 'url("/assets/iceline/login.png")',
                backgroundPosition: 'left',
                backgroundSize: 'auto 100%',
                backgroundRepeat: 'no-repeat',
                marginLeft: '-1rem',
            }}
        >
            <div
                style={{
                    background: 'linear-gradient(to right, rgba(14, 16, 31, 0) 50%, rgba(14, 16, 31, 1) 100%)',
                }}
            />
            <Formik
                onSubmit={onSubmit}
                initialValues={{ username: '', password: '' }}
                validationSchema={object().shape({
                    username: string().required('A username or email must be provided.'),
                    password: string().required('Please enter your account password.'),
                })}
            >
                {({ isSubmitting, setSubmitting, submitForm }) => (
                    <LoginFormContainer title={'Welcome back, traveller.'} css={tw`w-full flex`}>
                        <div>
                            <Button type={'button'} size={'xlarge'} isLoading={isSubmitting} disabled={isSubmitting} onClick={useWHMCS}>
                                Login With Your Billing Account
                            </Button>
                        </div>
                        <h1 css={tw`text-base text-center font-normal text-neutral-400 mt-8 mb-4`}>
                            {/* Or sign in with your credentials */}
                            Sign in with your credentials
                        </h1>
                        <LoginField light type={'text'} label={'Username or Email'} name={'username'} disabled={isSubmitting} />
                        <div css={tw`mt-6`}>
                            <LoginField light type={'password'} label={'Password'} name={'password'} disabled={isSubmitting} />
                        </div>
                        <div css={tw`mt-6`}>
                            <Button type={'submit'} size={'xlarge'} isLoading={isSubmitting} disabled={isSubmitting}>
                                Login
                            </Button>
                        </div>
                        {recaptchaEnabled && (
                            <Reaptcha
                                ref={ref}
                                size={'invisible'}
                                sitekey={siteKey || '_invalid_key'}
                                onVerify={(response) => {
                                    setToken(response);
                                    submitForm();
                                }}
                                onExpire={() => {
                                    setSubmitting(false);
                                    setToken('');
                                }}
                            />
                        )}
                        <div css={tw`mt-6 text-center`}>
                            <Link to={'/auth/password'} css={tw`text-xs text-neutral-500 tracking-wide no-underline uppercase hover:text-neutral-600`}>
                                Forgot password?
                            </Link>
                        </div>
                    </LoginFormContainer>
                )}
            </Formik>
        </div>
    );
};

export default LoginContainer;
