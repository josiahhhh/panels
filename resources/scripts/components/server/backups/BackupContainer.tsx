import React, { useContext, useEffect, useState } from 'react';
import Spinner from '@/components/elements/Spinner';
import useFlash from '@/plugins/useFlash';
import Can from '@/components/elements/Can';
import CreateBackupButton from '@/components/server/backups/CreateBackupButton';
import FlashMessageRender from '@/components/FlashMessageRender';
import BackupRow from '@/components/server/backups/BackupRow';
import tw, { theme } from 'twin.macro';
import getServerBackups, { Context as ServerBackupContext } from '@/api/swr/getServerBackups';
import { ServerContext } from '@/state/server';
import ServerContentBlock from '@/components/elements/ServerContentBlock';
import Pagination from '@/components/elements/Pagination';
// import getServerDatabaseBackups from '@/api/server/iceline/backups/getServerDatabaseBackups';
import CreateDatabaseBackupButton from '@/components/server/backups/CreateDatabaseBackupButton';
import DatabaseBackupRow from '@/components/server/backups/DatabaseBackupRow';
import ActionPrompt from '@/components/iceline/server/ActionPrompt';
import { Button } from '@/components/elements/button/index';
import { useHistory } from 'react-router';
import BackupUsage from '@/components/iceline/server/backups/BackupUsage';
import MessageBox from '@/components/MessageBox';
import Select from 'react-select';
import getBackupSettings, { BackupSettings } from '@/api/server/getBackupSettings';
import updateBackupSettings from '@/api/server/updateBackupSettings';
import getBackupsSize, { BackupSizeResponse } from '@/api/server/backups/getBackupsSize';
import getServerDatabaseBackups, { Context as ServerDatabaseBackupContext } from '@/api/swr/getServerDatabaseBackups';
import ErrorBoundary from '@/components/elements/ErrorBoundary';

const selectTheme = (theTheme: any) => ({
    ...theTheme,
    colors: {
        ...theTheme.colors,
        primary: theme`colors.icelinebrandcolour.500`.toString(),
        primary75: theme`colors.icelinebrandcolour.500`.toString(),
        primary50: theme`colors.icelinebrandcolour.500`.toString(),
        primary25: theme`colors.icelinebrandcolour.500`.toString(),
        neutral0: theme`colors.icelinebox.600`.toString(), // control background
        neutral5: theme`colors.icelinebox.600`.toString(),
        neutral10: theme`colors.icelinebox.600`.toString(),
        neutral20: theme`colors.icelinebox.400`.toString(), // control border, indicators/color
        neutral30: theme`colors.icelinebox.200`.toString(), // control border focused/hover
        neutral40: theme`colors.icelinebox.300`.toString(), // indicators/color:hover
        neutral50: theme`colors.icelinebox.400`.toString(),
        neutral60: theme`colors.icelinebox.300`.toString(),
        neutral70: theme`colors.icelinebox.200`.toString(),
        neutral80: theme`colors.icelinebox.100`.toString(),
        neutral90: theme`colors.icelinebox.50`.toString(),
    },
});

const BackupContainer = () => {
    const { setPage } = useContext(ServerBackupContext);
    const { setPage: setDatabasePage } = useContext(ServerDatabaseBackupContext);

    const history = useHistory();

    const { clearFlashes, clearAndAddHttpError } = useFlash();
    const { data: backups, error, isValidating } = getServerBackups();
    const { data: databaseBackups, error: errorDatabase, isValidating: isValidatingDatabase } = getServerDatabaseBackups();

    const [totalBackups, setTotalBackups] = useState(0);
    const [limitReached, setLimitReached] = useState(false);

    const [backupSizes, setBackupSizes] = useState<BackupSizeResponse>({
        file: 0,
        database: 0,
    });

    const [backupSettings, setBackupSettings] = useState<BackupSettings>({
        backup_retention: 0,
    });
    const [_, setSavingBackupSettings] = useState(false);

    const uuid = ServerContext.useStoreState((state) => state.server.data!.uuid);

    const backupLimit = ServerContext.useStoreState((state) => state.server.data!.featureLimits.backups);

    useEffect(() => {
        if (!error) {
            clearFlashes('backups');

            return;
        }

        clearAndAddHttpError({ error, key: 'backups' });
    }, [error]);

    useEffect(() => {
        if (!errorDatabase) {
            clearFlashes('backups');

            return;
        }

        clearAndAddHttpError({ error: errorDatabase, key: 'backups' });
    }, [errorDatabase]);

    useEffect(() => {
        if (!backups || !databaseBackups) {
            return;
        }

        // if (!backupLimitBySize) {
        setTotalBackups(backups.items.length + databaseBackups.items.length);
        // }
    }, [backups, databaseBackups]);

    useEffect(() => {
        /* if (!backupLimitBySize) {
            return;
        } */

        getBackupsSize(uuid)
            .then((sizes) => {
                const fileBackupSize = sizes.file / 1073741824;
                const dbBackupSize = sizes.database / 1073741824;

                setTotalBackups(fileBackupSize + dbBackupSize);
                setBackupSizes(sizes);
            })
            .catch((err) => clearAndAddHttpError({ error: err, key: 'backups' }));
    }, [uuid]);

    useEffect(() => {
        if (totalBackups >= backupLimit) {
            setLimitReached(true);
        } else {
            setLimitReached(false);
        }
    }, [totalBackups]);

    useEffect(() => {
        getBackupSettings(uuid)
            .then((settings) => setBackupSettings(settings))
            .catch((err) => clearAndAddHttpError({ error: err, key: 'backups' }));
    }, [uuid]);

    const saveSettings = () => {
        setSavingBackupSettings(true);

        updateBackupSettings(uuid, backupSettings)
            .then(() => setSavingBackupSettings(false))
            .catch((err) => clearAndAddHttpError({ error: err, key: 'backups' }));
    };

    if (!backups || (error && isValidating)) {
        return <Spinner size={'large'} centered />;
    }

    if (!databaseBackups || (errorDatabase && isValidatingDatabase)) {
        return <Spinner size={'large'} centered />;
    }

    const backupRetentionOptions = [
        {
            value: 0,
            label: 'Forever',
        },
        {
            value: 1800,
            label: 'Half an hour',
        },
        {
            value: 3600,
            label: 'An hour',
        },
        {
            value: 43200,
            label: '12 Hours',
        },
        {
            value: 86400,
            label: 'A Day',
        },
        {
            value: 604800,
            label: 'A Week',
        },
        {
            value: 604800 * 4,
            label: 'A Month',
        },
    ];

    return (
        <ServerContentBlock title={'Backups'}>
            <FlashMessageRender byKey={'backups'} css={tw`mb-4`} />
            {backupLimit === 0 ? (
                <div css={tw`mb-6`}>
                    <MessageBox type='info' title='Info'>
                        Backups cannot be created for this server. If you would like to create backups, please upgrade your plan to a plan that includes backups.
                    </MessageBox>
                </div>
            ) : (
                <BackupUsage
                    fileUsed={((backupSizes.file !== undefined ? backupSizes.file / 1073741824 : 0) / backupLimit) * 100}
                    databaseUsed={((backupSizes.database !== undefined ? backupSizes.database / 1073741824 : 0) / backupLimit) * 100}
                    limitGB={backupLimit}
                />
            )}
            <div css={tw`grid grid-cols-1 xl:grid-cols-2 gap-x-6 gap-y-6 w-full mb-16`}>
                <div>
                    <h1 css={tw`mb-4 text-base`}>File Backups</h1>
                    {!backups.items.length ? (
                        <p css={tw`text-center text-sm text-neutral-400`}>There are no file backups stored for this server.</p>
                    ) : (
                        <div>
                            <Pagination data={backups} onPageSelect={setPage}>
                                {({ items }) =>
                                    !items.length ? (
                                        <p>End of backups</p>
                                    ) : (
                                        backups.items.map((backup, index) => <BackupRow key={backup.uuid} backup={backup} css={index > 0 ? tw`mt-2` : undefined} />)
                                    )
                                }
                            </Pagination>
                        </div>
                    )}
                    <Can action={'backup.create'}>
                        {backupLimit > 0 && totalBackups > 0 && (
                            <p css={tw`text-center text-xs text-neutral-400 mt-2`}>
                                {/* {totalBackups.toFixed(2)} of {backupLimitBySize ? backupLimit + 'GB' : backupLimit} backups have been created for this server. */}
                                {totalBackups.toFixed(2)}GB of {backupLimit + 'GB'} backups have been created for this server.
                            </p>
                        )}
                        {backupLimit > 0 && !limitReached && (
                            <div css={tw`mt-6 flex justify-end`}>
                                <CreateBackupButton />
                            </div>
                        )}
                    </Can>
                </div>
                <div>
                    <h1 css={tw`mb-4 text-base`}>Database Backups</h1>
                    <ErrorBoundary>
                        {!databaseBackups.items.length ? (
                            <p css={tw`text-center text-sm text-neutral-400`}>There are no database backups stored for this server.</p>
                        ) : (
                            <div>
                                <Pagination data={databaseBackups} onPageSelect={setDatabasePage}>
                                    {({ items }) =>
                                        !items.length ? (
                                            <p>End of backups</p>
                                        ) : (
                                            databaseBackups.items.map((backup, index) => (
                                                <DatabaseBackupRow key={backup.uuid} backup={backup} css={index > 0 ? tw`mt-2` : undefined} />
                                            ))
                                        )
                                    }
                                </Pagination>
                            </div>
                        )}
                        <Can action={'database_backup.create'}>
                            {backupLimit > 0 && totalBackups > 0 && (
                                <p css={tw`text-center text-xs text-neutral-400 mt-2`}>
                                    {/* {totalBackups.toFixed(2)} of {backupLimitBySize ? backupLimit + 'GB' : backupLimit} backups have been created for this server. */}
                                    {totalBackups.toFixed(2)}GB of {backupLimit + 'GB'} backups have been created for this server.
                                </p>
                            )}
                            {backupLimit > 0 && !limitReached && (
                                <div css={tw`mt-6 flex justify-end`}>
                                    <CreateDatabaseBackupButton />
                                </div>
                            )}
                        </Can>
                    </ErrorBoundary>
                </div>
            </div>
            <div css={tw`grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-x-6 gap-y-6 w-full`}>
                <ActionPrompt
                    title={'Automated Backups'}
                    subtitle={
                        'Create automated backups to run at regular schedules with our task scheduler systems. Simply create a new schedule, and then add a task to create a database or file backup.'
                    }
                >
                    <Button
                        size={Button.Sizes.Small}
                        css={tw`mr-2`}
                        onClick={(e) => {
                            e.preventDefault();

                            history.push('/server/' + uuid + '/schedules');
                        }}
                    >
                        Create Schedule
                    </Button>
                </ActionPrompt>
                <ActionPrompt title={'File Backup Retention'} subtitle={'Configure how long file backups are stored before they are automatically deleted.'}>
                    <div css={tw`space-y-4`}>
                        <div>
                            <Select
                                options={backupRetentionOptions}
                                defaultValue={{
                                    value: 0,
                                    label: 'Forever',
                                }}
                                value={
                                    backupRetentionOptions.find((o) => o.value === backupSettings.backup_retention) || {
                                        value: backupSettings.backup_retention,
                                        label: backupSettings.backup_retention + ' Seconds',
                                    }
                                }
                                blurInputOnSelect
                                hideSelectedOptions
                                theme={selectTheme}
                                onChange={(v) => {
                                    if (v) {
                                        const newBackupSettings = JSON.parse(JSON.stringify(backupSettings));
                                        newBackupSettings.backup_retention = v.value;
                                        setBackupSettings(newBackupSettings);
                                    }
                                }}
                            />
                        </div>
                        <Button size={Button.Sizes.Small} css={tw`mr-2`} onClick={saveSettings}>
                            Save
                        </Button>
                    </div>
                </ActionPrompt>
            </div>
        </ServerContentBlock>
    );
};

export default () => {
    const [page, setPage] = useState<number>(1);
    const [databasePage, setDatabasePage] = useState<number>(1);
    return (
        <ServerBackupContext.Provider value={{ page, setPage }}>
            <ServerDatabaseBackupContext.Provider value={{ page: databasePage, setPage: setDatabasePage }}>
                <BackupContainer />
            </ServerDatabaseBackupContext.Provider>
        </ServerBackupContext.Provider>
    );
};
