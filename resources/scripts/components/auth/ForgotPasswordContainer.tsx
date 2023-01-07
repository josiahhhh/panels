import * as React from "react";
import { useEffect, useRef, useState } from "react";
import { Link } from "react-router-dom";
import requestPasswordResetEmail from "@/api/auth/requestPasswordResetEmail";
import { httpErrorToHuman } from "@/api/http";
import LoginFormContainer from "@/components/auth/LoginFormContainer";
import { useStoreState } from "easy-peasy";
import Field from "@/components/elements/Field";
import { Formik, FormikHelpers } from "formik";
import { object, string } from "yup";
import tw from "twin.macro";
import Button from "@/components/elements/Button";
import Reaptcha from "reaptcha";
import useFlash from "@/plugins/useFlash";

interface Values {
    email: string;
}

export default () => {
    const ref = useRef<Reaptcha>(null);
    const [token, setToken] = useState("");

    const { clearFlashes, addFlash } = useFlash();
    const { enabled: recaptchaEnabled, siteKey } = useStoreState(
        (state) => state.settings.data!.recaptcha
    );

    useEffect(() => {
        clearFlashes();
    }, []);

    const handleSubmission = (
        { email }: Values,
        { setSubmitting, resetForm }: FormikHelpers<Values>
    ) => {
        clearFlashes();

        // If there is no token in the state yet, request the token and then abort this submit request
        // since it will be re-submitted when the recaptcha data is returned by the component.
        if (recaptchaEnabled && !token) {
            ref.current!.execute().catch((error) => {
                console.error(error);

                setSubmitting(false);
                addFlash({
                    type: "error",
                    title: "Error",
                    message: httpErrorToHuman(error),
                });
            });

            return;
        }

        requestPasswordResetEmail(email, token)
            .then((response) => {
                resetForm();
                addFlash({
                    type: "success",
                    title: "Success",
                    message: response,
                });
            })
            .catch((error) => {
                console.error(error);
                addFlash({
                    type: "error",
                    title: "Error",
                    message: httpErrorToHuman(error),
                });
            })
            .then(() => {
                setToken("");
                if (ref.current) ref.current.reset();

                setSubmitting(false);
            });
    };

    return (
        <div
            css={tw`relative min-h-screen w-screen flex`}
            style={{ backgroundImage: `url ("/assets/iceline/login.png")` }}
        >
            <div
                css={tw`flex flex-col sm:flex-row items-center md:items-start sm:justify-center md:justify-start flex-auto min-w-0`}
            >
                <div
                    css={tw`sm:w-1/2 lg:w-11/12 h-full hidden md:flex flex-auto items-center justify-center overflow-hidden text-white bg-cover relative`}
                    style={{
                        backgroundImage: `url("/assets/iceline/login.png")`,
                    }}
                />
                <Formik
                    onSubmit={handleSubmission}
                    initialValues={{ email: "" }}
                    validationSchema={object().shape({
                        email: string()
                            .email(
                                "A valid email address must be provided to continue."
                            )
                            .required(
                                "A valid email address must be provided to continue."
                            ),
                    })}
                >
                    {({ isSubmitting, setSubmitting, submitForm }) => (
                        <LoginFormContainer>
                            <div
                                css={tw`flex flex-col w-full py-40 container max-w-4xl`}
                            >
                                <div css={tw`flex flex-row items-center`}>
                                    <img
                                        src="/assets/iceline/logo.png"
                                        css={tw`w-20 h-20  mr-4 `}
                                    />
                                    <h1>
                                        <span
                                            css={tw`text-2xl text-white `}
                                            style={{
                                                lineHeight: "21px",
                                            }}
                                        >
                                            Game Panel
                                        </span>
                                    </h1>
                                </div>
                                <div css={tw`flex flex-col w-full`}>
                                    <h1 css={tw`text-5xl mt-10 text-white`}>
                                        Forgot password
                                    </h1>
                                    <p css={tw`text-white mt-2`}>
                                        Enter your email and we’ll send you a
                                        link to access your account.
                                    </p>
                                </div>
                                <div css={tw`flex flex-col w-full`}>
                                    <div css={tw`pt-5`} />
                                    <Field
                                        label={"Email"}
                                        placeholder="Enter your email"
                                        name={"email"}
                                        type={"email"}
                                        css={tw`w-full bg-transparent border transition transition-colors hover:bg-icelineBtnPrimary border-gray-800 py-2 text-white text-lg font-medium hover:opacity-75 transition duration-150 ease-in-out`}
                                    />
                                </div>
                                <div css={tw`flex flex-col w-full mt-10`}>
                                    <Button
                                        type={"submit"}
                                        isLoading={isSubmitting}
                                        disabled={isSubmitting}
                                        css={tw`w-full bg-icelineBtnPrimary border transition transition-colors hover:bg-icelineBtnPrimary border-gray-800 py-2 text-white text-lg font-medium rounded-md hover:opacity-75 transition duration-150 ease-in-out`}
                                    >
                                        Send Email
                                    </Button>
                                    {recaptchaEnabled && (
                                        <Reaptcha
                                            ref={ref}
                                            size={"invisible"}
                                            sitekey={siteKey || "_invalid_key"}
                                            onVerify={(response) => {
                                                setToken(response);
                                                submitForm();
                                            }}
                                            onExpire={() => {
                                                setSubmitting(false);
                                                setToken("");
                                            }}
                                        />
                                    )}
                                </div>
                            </div>
                        </LoginFormContainer>
                    )}
                </Formik>
            </div>
        </div>
    );
};
