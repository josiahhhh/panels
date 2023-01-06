import React from 'react';
import { NavLink, Route as RouterRoute, Switch } from 'react-router-dom';
import TransitionRouter from '@/TransitionRouter';
import SubNavigation from '@/components/iceline/SubNavigation';
import * as Sentry from '@sentry/react';
import routes from '@/routers/routes';
import Spinner from '@/components/elements/Spinner';
import ErrorBoundary from '@/components/elements/ErrorBoundary';
const Route = Sentry.withSentryRouting(RouterRoute);
import { NotFound } from '@/components/elements/ScreenBlock';
import { useLocation } from 'react-router';

export default () => {
    const location = useLocation();

    return (
        <>
            {/*<NavigationBar/>*/}
            {location.pathname.startsWith('/staff') && (
                <SubNavigation>
                    <div>
                        <NavLink to={'/staff'} exact>
                            Request
                        </NavLink>
                    </div>
                </SubNavigation>
            )}
            <ErrorBoundary>
                <TransitionRouter>
                    <Switch location={location}>
                        {routes.staff.map(({ path, component: Component }) => (
                            <Route key={path} path={path} exact>
                                <Spinner.Suspense>
                                    <Component />
                                </Spinner.Suspense>
                            </Route>
                        ))}
                        <Route path={'*'} component={NotFound} />
                    </Switch>
                </TransitionRouter>
            </ErrorBoundary>
        </>
    );
};
