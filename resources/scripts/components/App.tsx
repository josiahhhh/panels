import { setupInterceptors } from '@/api/interceptors';
import GlobalStylesheet from '@/assets/css/GlobalStylesheet';
import '@/assets/tailwind.css';
import AuthenticatedRoute from '@/components/elements/AuthenticatedRoute';
import ProgressBar from '@/components/elements/ProgressBar';
import { NotFound } from '@/components/elements/ScreenBlock';
import Spinner from '@/components/elements/Spinner';
import { history } from '@/components/history';
import Sidebar from '@/components/iceline/Sidebar';
import { store } from '@/state';
import { ServerContext } from '@/state/server';
import { SiteSettings } from '@/state/settings';
import { StoreProvider } from 'easy-peasy';
import React, { lazy } from 'react';
import { hot } from 'react-hot-loader/root';
import { Route, Router, Switch } from 'react-router-dom';
import tw from 'twin.macro';

const DashboardRouter = lazy(() => import(/* webpackChunkName: 'dashboard' */ '@/routers/DashboardRouter'));
const ServerRouter = lazy(() => import(/* webpackChunkName: 'server' */ '@/routers/ServerRouter'));
const AuthenticationRouter = lazy(() => import(/* webpackChunkName: 'auth' */ '@/routers/AuthenticationRouter'));
const StaffRouter = lazy(() => import(/* webpackChunkName: 'auth' */ '@/routers/StaffRouter'));

interface ExtendedWindow extends Window {
    SiteConfiguration?: SiteSettings;
    PterodactylUser?: {
        uuid: string;
        username: string;
        email: string;
        /* eslint-disable camelcase */
        root_admin: boolean;
        use_totp: boolean;
        staff: 0;
        language: string;
        updated_at: string;
        created_at: string;
        /* eslint-enable camelcase */
    };
}

setupInterceptors(history);

const App = () => {
    const { PterodactylUser, SiteConfiguration } = window as ExtendedWindow;
    if (PterodactylUser && !store.getState().user.data) {
        store.getActions().user.setUserData({
            uuid: PterodactylUser.uuid,
            username: PterodactylUser.username,
            email: PterodactylUser.email,
            language: PterodactylUser.language,
            rootAdmin: PterodactylUser.root_admin,
            useTotp: PterodactylUser.use_totp,
            staff: PterodactylUser.staff,
            createdAt: new Date(PterodactylUser.created_at),
            updatedAt: new Date(PterodactylUser.updated_at),
        });
    }

    if (!store.getState().settings.data) {
        store.getActions().settings.setSettings(SiteConfiguration!);
    }

    return (
        <>
            <GlobalStylesheet />
            <StoreProvider store={store}>
                <ProgressBar />
                <div css={tw`mx-auto w-auto`}>
                    <Router history={history}>
                        <div className={'content-wrapper'} css={tw`grid grid-cols-1 sm:grid-cols-dashboard h-screen relative`}>
   
                            <div className={'content'} css={tw`h-screen overflow-x-hidden overflow-y-auto relative `}>
                                <Switch>
                                    <Route path={'/auth'}>
                                        <Spinner.Suspense>
                                            <AuthenticationRouter />
                                        </Spinner.Suspense>
                                    </Route>
                                    <AuthenticatedRoute path={'/server/:id'}>
                                        <Spinner.Suspense>
                                            <ServerContext.Provider>
                                                <ServerRouter />
                                            </ServerContext.Provider>
                                        </Spinner.Suspense>
                                    </AuthenticatedRoute>
                                    {PterodactylUser?.staff && (
                                        <AuthenticatedRoute path={'/staff'}>
                                            <Spinner.Suspense>
                                                <ServerContext.Provider>
                                                    <StaffRouter />
                                                </ServerContext.Provider>
                                            </Spinner.Suspense>
                                        </AuthenticatedRoute>
                                    )}
                                    <AuthenticatedRoute path={'/'}>
                                        <Spinner.Suspense>
                                            <DashboardRouter />
                                        </Spinner.Suspense>
                                    </AuthenticatedRoute>
                                    <Route path={'*'}>
                                        <NotFound />
                                    </Route>
                                </Switch>
                            </div>
                        </div>
                    </Router>
                </div>
            </StoreProvider>
        </>
    );
};

export default hot(App);
