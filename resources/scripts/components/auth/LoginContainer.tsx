import React, { useEffect, useRef, useState } from "react";
import { Link, RouteComponentProps } from "react-router-dom";
import login from "@/api/auth/login";
import LoginFormContainer from "@/components/auth/LoginFormContainer";
import { useStoreState } from "easy-peasy";
import { Formik, FormikHelpers } from "formik";
import { object, string } from "yup";
import tw from "twin.macro";
import Button from "@/components/elements/Button";
import Reaptcha from "reaptcha";
import useFlash from "@/plugins/useFlash";
import http from "@/api/http";
import LoginField from "@/components/iceline/LoginField";

interface Values {
    username: string;
    password: string;
}

const LoginContainer = ({ history }: RouteComponentProps) => {
    const ref = useRef<Reaptcha>(null);
    const [token, setToken] = useState("");

    const { clearFlashes, clearAndAddHttpError, addFlash } = useFlash();
    const { enabled: recaptchaEnabled, siteKey } = useStoreState(
        (state) => state.settings.data!.recaptcha
    );

    useEffect(() => {
        clearFlashes();
    }, []);

    useEffect(() => {
        const search = window.location.search;
        const params = new URLSearchParams(search);
        const queryError = params.get("error");
        if (queryError) {
            addFlash({
                type: "error",
                message: queryError,
            });
        }
    }, [window.location.search]);

    const useWHMCS = () => {
        http.get("/auth/oauth/whmcs").then((response) => {
            window.location = response.data.redirect;
        });
    };

    const onSubmit = (
        values: Values,
        { setSubmitting }: FormikHelpers<Values>
    ) => {
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
                    window.location = response.intended || "/";
                    return;
                }

                history.replace("/auth/login/checkpoint", {
                    token: response.confirmationToken,
                });
            })
            .catch((error) => {
                console.error(error);

                setToken("");
                if (ref.current) ref.current.reset();

                setSubmitting(false);
                clearAndAddHttpError({ error });
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
                    onSubmit={onSubmit}
                    initialValues={{ username: "", password: "" }}
                    validationSchema={object().shape({
                        username: string().required(
                            "A username or email must be provided."
                        ),
                        password: string().required(
                            "Please enter your account password."
                        ),
                    })}
                >
                    {({ isSubmitting, setSubmitting, submitForm }) => (
                        <LoginFormContainer>
                            <div
                                css={tw`flex flex-col w-full py-20 px-10 sm:px-20 md:px-40 lg:px-60`}
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
                                        Sign in
                                    </h1>
                                    <p css={tw`text-icelineMuted my-5`}>
                                        You can sign in with Iceline Identity or
                                        by using your email and password.
                                    </p>
                                </div>
                                <Button
                                    css={tw`mt-5 mb-5 bg-icelinebrandcolour-100 border-icelinebrandcolour-200`}
                                >
                                    Sign in with Iceline Identity
                                </Button>
                                <div css={tw`flex items-center `}>
                                    <hr css={tw`w-full border-neutral-600`} />
                                    <span css={tw`mx-2 text-neutral-500`}>
                                        Or
                                    </span>
                                    <hr css={tw`w-full border-neutral-600`} />
                                </div>
                                <div css={tw`flex flex-col w-full`}>
                                    <div css={tw`pt-5`} />
                                    <LoginField
                                        name={"username"}
                                        type={"text"}
                                        placeholder={"Username or Email"}
                                        label={"Username or Email"}
                                    />
                                    <div css={tw`pb-5`} />
                                    <div css={tw`flex flex-col w-full `}>
                                        <div
                                            css={tw`flex flex-row justify-between mb-2 items-center`}
                                        >
                                            <label
                                                css={tw`text-xs font-bold text-neutral-300  block`}
                                            >
                                                Password{" "}
                                                <span css={tw`text-red-400`}>
                                                    *
                                                </span>
                                            </label>

                                            <h1>
                                                <a
                                                    css={tw`text-xs text-neutral-300 block`}
                                                    href="/auth/password"
                                                >
                                                    Forgot Password?
                                                </a>
                                            </h1>
                                        </div>
                                        <LoginField
                                            name={"password"}
                                            type={"password"}
                                            placeholder={"Password"}
                                        />
                                    </div>
                                </div>
                                <div css={tw`flex flex-col w-full mt-10`}>
                                    <Button
                                        type={"submit"}
                                        isLoading={isSubmitting}
                                        disabled={isSubmitting}
                                        css={tw`w-full bg-icelineBtnPrimary border transition transition-colors hover:bg-icelineBtnPrimary border-gray-800 py-2 text-white text-lg font-medium rounded-md hover:opacity-75 transition duration-150 ease-in-out`}
                                    >
                                        Sign In
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

export default LoginContainer;
