import React, { useState } from 'react';

import tw from 'twin.macro';
import { useStoreState } from 'easy-peasy';
import { ApplicationStore } from '@/state';

import styled from 'styled-components/macro';
import { NavLink } from 'react-router-dom';
import http from '@/api/http';
import SpinnerOverlay from '@/components/elements/SpinnerOverlay';

const SidebarItem = styled.li`
    & > a,
    & > button {
        ${tw`flex flex-col items-center justify-center text-neutral-50 no-underline p-4 cursor-pointer w-full`};

        opacity: 0.6;

        &:hover {
            ${tw`bg-icelineSidebarSelected text-neutral-50`};
            opacity: 1;
        }

        &.active {
            ${tw`bg-icelineSidebarSelected border-icelinePrimary`};
            border-right-width: 3px;
        }
    }
`;

export default () => {
    const [visible, setVisible] = useState(true);

    const userId = useStoreState((state: ApplicationStore) => state.user?.data?.uuid);
    const rootAdmin = useStoreState((state: ApplicationStore) => state.user?.data?.rootAdmin);

    const staff = useStoreState((state: ApplicationStore) => state.user?.data?.staff);

    const [isLoggingOut, setIsLoggingOut] = useState(false);

    const items = [
        {
            label: 'Account',
            path: '/account',
            icon: '/assets/iceline/sidebar/account.svg',
            loggedIn: true,
        },
        {
            label: 'Servers',
            path: '/',
            icon: '/assets/iceline/sidebar/servers.svg',
            exact: true,
            loggedIn: true,
        },
        {
            label: 'Proxies',
            path: '/proxies',
            icon: '/assets/iceline/sidebar/proxies.svg',
            exact: true,
            loggedIn: true,
        },
        {
            label: 'Billing',
            path: 'https://iceline-hosting.com/billing',
            icon: '/assets/iceline/sidebar/billing.svg',
            external: true,
        },
    ];

    if (staff === 1) {
        items.push({
            label: 'Staff',
            path: '/staff',
            icon: '/assets/iceline/sidebar/staff.svg',
            loggedIn: true,
        });
    }

    const onTriggerLogout = () => {
        setIsLoggingOut(true);
        http.post('/auth/logout').finally(() => {
            // @ts-expect-error this is valid
            window.location = '/';
        });
    };

    return (
        <>
            {!visible && (
                <div css={tw`absolute top-0 left-0 z-50 p-4`} onClick={() => setVisible(!visible)}>
                    <p css={tw`text-base text-neutral-200 font-medium`}>
                        <span>Open Sidebar</span>
                    </p>
                </div>
            )}
            {visible && (
                <div
                    css={tw`fixed top-0 left-0 right-0 bottom-0 z-50 sm:relative sm:right-auto`}
                    style={{
                        backgroundColor: 'rgba(0,0,0,0.5)',
                    }}
                    onClick={(e) => {
                        e.stopPropagation();
                        setVisible(false);
                    }}
                >
                    <aside
                        css={tw`flex flex-col w-sidebar h-full`}
                        style={{ background: 'linear-gradient(180deg, #1A1E3A 0%, #171A33 58.5%)' }}
                        onClick={(e) => e.stopPropagation()}
                    >
                        <SpinnerOverlay visible={isLoggingOut} />
                        <div css={tw`flex items-center justify-center mb-8`}>
                            <img src={'/assets/iceline/logo.png'} />
                        </div>
                        <ul css={tw`flex-grow`}>
                            {items.map((item) => {
                                if (item.loggedIn && !userId) {
                                    return <></>;
                                }
                                return (
                                    <SidebarItem key={item.path}>
                                        {item.external ? (
                                            <a href={item.path} rel={'noreferrer'}>
                                                <img src={item.icon} />
                                                <p css={tw`text-center mt-4 text-base`}>{item.label}</p>
                                            </a>
                                        ) : (
                                            <NavLink to={item.path} exact={item.exact ? item.exact : false}>
                                                <img src={item.icon} />
                                                <p css={tw`text-center mt-4 text-base`}>{item.label}</p>
                                            </NavLink>
                                        )}
                                    </SidebarItem>
                                );
                            })}
                        </ul>
                        <ul>
                            {rootAdmin && (
                                <SidebarItem>
                                    <a href={'/admin'} rel={'noreferrer'}>
                                        <img src={'/assets/iceline/sidebar/settings.svg'} />
                                        <p css={tw`text-center mt-4 text-base`}>Admin</p>
                                    </a>
                                </SidebarItem>
                            )}
                            {userId && (
                                <SidebarItem>
                                    <button onClick={onTriggerLogout}>
                                        <img src={'/assets/iceline/sidebar/logout.svg'} />
                                        <p css={tw`text-center mt-4 text-base`}>Log Out</p>
                                    </button>
                                </SidebarItem>
                            )}
                        </ul>
                    </aside>
                </div>
            )}
        </>
    );
};
