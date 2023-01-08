import React, { useState } from "react";

import tw from "twin.macro";
import { useStoreState } from "easy-peasy";
import { ApplicationStore } from "@/state";

import styled from "styled-components/macro";
import { NavLink } from "react-router-dom";
import http from "@/api/http";
import SpinnerOverlay from "@/components/elements/SpinnerOverlay";

import { FontAwesomeIcon } from "@fortawesome/react-fontawesome";
import {
    faBars,
    faTimes,
    faSignOutAlt,
} from "@fortawesome/free-solid-svg-icons";

const SidebarItem = styled.li`
    ${tw`relative flex items-center px-4 py-2 my-3 text-sm text-neutral-200 no-underline`}

    &.active {
        ${tw`text-neutral-100 bg-icelinemainbackground-400 rounded-md border-2 border-icelinenew-50`}
    }

    &:hover {
        ${tw`text-neutral-100 bg-icelinemainbackground-700 rounded-md border-2 border-icelinenew-50`}
    }
`;

export default () => {
    const [visible, setVisible] = useState(true);

    const userId = useStoreState(
        (state: ApplicationStore) => state.user?.data?.uuid
    );
    const rootAdmin = useStoreState(
        (state: ApplicationStore) => state.user?.data?.rootAdmin
    );

    const staff = useStoreState(
        (state: ApplicationStore) => state.user?.data?.staff
    );

    const [isLoggingOut, setIsLoggingOut] = useState(false);

    const items = [
        {
            label: "Game Servers",
            path: "/account",
            icon: "/assets/iceline/sidebar/gs.svg",
            loggedIn: true,
        },
        {
            label: "VPSs",
            path: "/",
            icon: "/assets/iceline/sidebar/vps.svg",
            exact: true,
            loggedIn: true,
        },
        {
            label: "Proxies",
            path: "/proxies",
            icon: "/assets/iceline/sidebar/proxiess.svg",
            exact: true,
            loggedIn: true,
        },
    ];

    if (staff === 1) {
        items.push({
            label: "Staff",
            path: "/staff",
            icon: "/assets/iceline/sidebar/staff.svg",
            loggedIn: true,
        });
    }

    const onTriggerLogout = () => {
        setIsLoggingOut(true);
        http.post("/auth/logout").finally(() => {
            // @ts-expect-error this is valid
            window.location = "/";
        });
    };

    return (
        <>
            {!visible && (
                <div
                    css={tw`absolute top-0 left-0 z-50 p-4`}
                    onClick={() => setVisible(!visible)}
                >
                    <p css={tw`text-base text-neutral-200 font-medium`}>
                        <span>Open Sidebar</span>
                    </p>
                </div>
            )}
            {visible && (
                <div
                    css={tw`fixed top-0 left-0 right-0 bottom-0 z-50 sm:relative sm:right-auto bg-black bg-opacity-50`}
                    onClick={(e) => {
                        e.stopPropagation();
                        setVisible(false);
                    }}
                >
                    <aside
                        css={tw`flex flex-col w-sidebar h-full p-4 bg-icelinemainbackground-300 text-neutral-50`}
                        onClick={(e) => e.stopPropagation()}
                    >
                        <SpinnerOverlay visible={isLoggingOut} />
                        <div css={tw`flex items-center justify-center p-4 mb-20`}>
                            <div css={tw`flex items-center`}>
                                <img
                                    src="/assets/iceline/logo.svg"
                                    css={tw`w-11/12`}
                                />
                            </div>
                        </div>

                        <ul css={tw`flex-1 flex flex-col`}>
                            <h1
                                css={tw`px-4 py-2 text-sm mb-2 text-neutral-200`}
                            >
                                Services
                            </h1>
                            {items.map((item, index) => {
                                if (item.loggedIn && !userId) return null;

                                return (
                                    <SidebarItem
                                        key={index}
                                        className={
                                            window.location.pathname ===
                                            item.path
                                                ? "active"
                                                : ""
                                        }
                                    >
                                        <NavLink
                                            to={item.path}
                                            exact={item.exact}
                                            css={tw`flex items-center w-full h-full`}
                                            onClick={() => setVisible(false)}
                                        >
                                            <img
                                                src={item.icon}
                                                css={tw`w-6 h-6 mr-4`}
                                            />
                                            <span>{item.label}</span>
                                        </NavLink>
                                    </SidebarItem>
                                );
                            })}
                        </ul>

                        <div css={tw`flex items-center p-4 mb-8`}>
                            <button
                                css={tw`flex items-center justify-center  `}
                                onClick={onTriggerLogout}
                            >
                                <FontAwesomeIcon icon={faSignOutAlt} />
                                <span css={tw`text-white ml-2`}>Logout</span>
                            </button>
                        </div>
                    </aside>
                </div>
            )}
        </>
    );
};
