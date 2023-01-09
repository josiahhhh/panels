import TransferListener from '@/components/server/TransferListener';
import React, { useEffect, useState } from 'react';
import { NavLink, Route as RouterRoute, Switch, useRouteMatch } from 'react-router-dom';
import TransitionRouter from '@/TransitionRouter';
import WebsocketHandler from '@/components/server/WebsocketHandler';
import { ServerContext } from '@/state/server';
import { CSSTransition } from 'react-transition-group';
import Can from '@/components/elements/Can';
import Spinner from '@/components/elements/Spinner';
import { NotFound, ServerError } from '@/components/elements/ScreenBlock';
import { httpErrorToHuman } from '@/api/http';
import { useStoreState } from 'easy-peasy';
import InstallListener from '@/components/server/InstallListener';
import ErrorBoundary from '@/components/elements/ErrorBoundary';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import { faExternalLinkAlt } from '@fortawesome/free-solid-svg-icons';
import { useLocation } from 'react-router';
import ConflictStateRenderer from '@/components/server/ConflictStateRenderer';
import PermissionRoute from '@/components/elements/PermissionRoute';
import routes from '@/routers/routes';
import getAvailableMods from '@/api/server/mods/getAvailableMods';
import getServerRestoreStatus, { BackupRestoreStatus } from '@/api/server/iceline/backups/getServerRestoreStatus';
import useWebsocketEvent from '@/plugins/useWebsocketEvent';
import IcelineSubNavigation from '@/components/iceline/SubNavigation';
import ServerHeader from '@/components/iceline/ServerHeader';
import * as Sentry from '@sentry/react';
import { SocketEvent } from '@/components/server/events';
import AlertContainer from '../components/elements/AlertContainer';
import NavigationBar from '@/components/NavigationBar';
const Route = Sentry.withSentryRouting(RouterRoute);

export default () => {
    const match = useRouteMatch<{ id: string }>();
    const location = useLocation();

    const rootAdmin = useStoreState((state) => state.user.data!.rootAdmin);
    const [error, setError] = useState('');

    const [availableMods, setAvailableMods] = useState([] as any[]);
    const [_, setBackupRestoreState] = useState(null as BackupRestoreStatus | null);

    const id = ServerContext.useStoreState((state) => state.server.data?.id);
    const uuid = ServerContext.useStoreState((state) => state.server.data?.uuid);
    const inConflictState = ServerContext.useStoreState((state) => state.server.inConflictState);
    const egg = ServerContext.useStoreState((state) => state.server.data?.egg.name);
    const serverId = ServerContext.useStoreState((state) => state.server.data?.internalId);
    const getServer = ServerContext.useStoreActions((actions) => actions.server.getServer);
    const clearServerState = ServerContext.useStoreActions((actions) => actions.clearServerState);

    const to = (value: string, url = false) => {
        if (value === '/') {
            return url ? match.url : match.path;
        }
        return `${(url ? match.url : match.path).replace(/\/*$/, '')}/${value.replace(/^\/+/, '')}`;
    };

    useEffect(
        () => () => {
            clearServerState();
        },
        []
    );

    useEffect(() => {
        setError('');

        getServer(match.params.id).catch((error) => {
            console.error(error);
            setError(httpErrorToHuman(error));
        });

        return () => {
            clearServerState();
        };
    }, [match.params.id]);

    useEffect(() => {
        setError('');
        getAvailableMods(match.params.id)
            .then((res) => {
                setAvailableMods(res.mods);
            })
            .catch((error) => {
                console.error(error);
                setError(httpErrorToHuman(error));
            });
    }, [match.params.id]);

    const refreshBackupRestoreStatus = function () {
        getServerRestoreStatus(match.params.id)
            .then((status) => {
                setBackupRestoreState(status);

                if (status !== null) {
                    console.log(`found ${status.type} backup restore in progress`);
                    console.log(status);
                }
            })
            .catch((error) => {
                console.error(error);
                setError(httpErrorToHuman(error));
            });
    };

    useEffect(() => {
        setError('');
        refreshBackupRestoreStatus();
    }, [match.params.id]);

    // TODO: this is bad, need to fix
    useWebsocketEvent('backup restore starting' as SocketEvent, () => {
        refreshBackupRestoreStatus();
    });

    useWebsocketEvent(SocketEvent.BACKUP_RESTORE_COMPLETED, () => {
        refreshBackupRestoreStatus();
    });

    return (
        <React.Fragment key={'server-router'}>
            <NavigationBar />
            {!uuid || !id ? (
                error ? (
                    <ServerError message={error} />
                ) : (
                    <Spinner size={'large'} centered />
                )
            ) : (
                <>
                    <ServerHeader />
                    <CSSTransition timeout={150} classNames={'fade'} appear in>
                        <IcelineSubNavigation>
                            <div>
                                {routes.server
                                    .filter((route) => !!route.name)
                                    .map(
                                        (route) =>
                                            (route.condition ? route.condition(egg, availableMods) : true) &&
                                            (route.permission ? (
                                                <Can key={route.path} action={route.permission} matchAny>
                                                    <NavLink to={to(route.path, true)} exact={route.exact}>
                                                        {route.name}
                                                    </NavLink>
                                                </Can>
                                            ) : (
                                                <NavLink key={route.path} to={to(route.path, true)} exact={route.exact}>
                                                    {route.name}
                                                </NavLink>
                                            ))
                                    )}
                                {rootAdmin && (
                                    // eslint-disable-next-line react/jsx-no-target-blank
                                    <a href={`/admin/servers/view/${serverId}`} target={'_blank'}>
                                        <FontAwesomeIcon icon={faExternalLinkAlt} />
                                    </a>
                                )}
                            </div>
                        </IcelineSubNavigation>
                    </CSSTransition>
                    <AlertContainer />
                    <InstallListener />
                    <TransferListener />
                    <WebsocketHandler />
                    {inConflictState && (!rootAdmin || (rootAdmin && !location.pathname.endsWith(`/server/${id}`))) ? (
                        <ConflictStateRenderer />
                    ) : (
                        <ErrorBoundary>
                            <TransitionRouter>
                                <Switch location={location}>
                                    {routes.server.map(({ path, permission, component: Component }) => (
                                        <PermissionRoute key={path} permission={permission} path={to(path)} exact>
                                            <Spinner.Suspense>
                                                <Component />
                                            </Spinner.Suspense>
                                        </PermissionRoute>
                                    ))}
                                    <Route path={'*'} component={NotFound} />
                                </Switch>
                            </TransitionRouter>
                        </ErrorBoundary>
                    )}
                </>
            )}
        </React.Fragment>
    );
};
