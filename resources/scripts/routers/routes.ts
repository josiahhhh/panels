import React, { lazy } from 'react';
import ServerConsole from '@/components/server/console/ServerConsoleContainer';
import DatabasesContainer from '@/components/server/databases/DatabasesContainer';
import ScheduleContainer from '@/components/server/schedules/ScheduleContainer';
import UsersContainer from '@/components/server/users/UsersContainer';
import BackupContainer from '@/components/server/backups/BackupContainer';
import NetworkContainer from '@/components/server/network/NetworkContainer';
import StartupContainer from '@/components/server/startup/StartupContainer';
import FileManagerContainer from '@/components/server/files/FileManagerContainer';
import SettingsContainer from '@/components/server/settings/SettingsContainer';
import AccountOverviewContainer from '@/components/dashboard/AccountOverviewContainer';
import AccountApiContainer from '@/components/dashboard/AccountApiContainer';
import AccountSSHContainer from '@/components/dashboard/ssh/AccountSSHContainer';
import ActivityLogContainer from '@/components/dashboard/activity/ActivityLogContainer';
import ServerActivityLogContainer from '@/components/server/ServerActivityLogContainer';
import StaffContainer from '@/components/server/staff/StaffContainer';
import StaffRequetsContainer from '@/components/dashboard/staff/StaffRequetsContainer';
import TransferContainer from '@/components/server/transfer/TransferContainer';
import SetupContainer from '@/components/server/proxy/SetupContainer';
import MinecraftPluginsContainer from '@/components/server/plugins/minecraft/PluginsContainer';
import RustPluginsContainer from '@/components/server/plugins/rust/PluginsContainer';
import ModsContainer from '@/components/server/mods/ModsContainer';
// import SubdomainContainer from '@/components/server/subdomain/SubdomainContainer';

// Each of the router files is already code split out appropriately — so
// all of the items above will only be loaded in when that router is loaded.
//
// These specific lazy loaded routes are to avoid loading in heavy screens
// for the server dashboard when they're only needed for specific instances.
const FileEditContainer = lazy(() => import('@/components/server/files/FileEditContainer'));
const ScheduleEditContainer = lazy(() => import('@/components/server/schedules/ScheduleEditContainer'));

interface RouteDefinition {
    path: string;
    // If undefined is passed this route is still rendered into the router itself
    // but no navigation link is displayed in the sub-navigation menu.
    name: string | undefined;
    component: React.ComponentType;
    exact?: boolean;
}

interface ServerRouteDefinition extends RouteDefinition {
    permission: string | string[] | null;
    condition?: (eggName: string | undefined, availableMods: any[]) => boolean;
}

interface Routes {
    // All of the routes available under "/account"
    account: RouteDefinition[];
    // All of the routes available under "/server/:id"
    server: ServerRouteDefinition[];
    staff: RouteDefinition[];
}

export default {
    account: [
        {
            path: '/',
            name: 'Account',
            component: AccountOverviewContainer,
            exact: true,
        },
        {
            path: '/api',
            name: 'API Credentials',
            component: AccountApiContainer,
        },
        {
            path: '/ssh',
            name: 'SSH Keys',
            component: AccountSSHContainer,
        },
        {
            path: '/activity',
            name: 'Activity',
            component: ActivityLogContainer,
        },
    ],
    server: [
        {
            path: '/',
            permission: null,
            name: 'Console',
            component: ServerConsole,
            exact: true,
        },
        {
            path: '/proxy',
            permission: null,
            name: 'Setup Proxy',
            component: SetupContainer,
            condition: (eggName) => eggName?.toLowerCase().includes('proxy'),
        },
        {
            path: '/files',
            permission: 'file.*',
            name: 'Files',
            component: FileManagerContainer,
            condition: (eggName) => !eggName?.toLowerCase().includes('proxy'),
        },
        {
            path: '/mods',
            permission: 'mods.*',
            name: 'Mods',
            component: ModsContainer,
            condition: (eggName, mods) => mods.length > 0,
        },
        {
            path: '/plugins/minecraft',
            permission: 'plugins.*',
            name: 'Plugins',
            component: MinecraftPluginsContainer,
            condition: (eggName) => eggName && (eggName.includes('Minecraft') || eggName.includes('Spigot') || eggName.includes('Paper')),
        },
        {
            path: '/plugins/rust',
            permission: 'plugins.*',
            name: 'Plugins',
            component: RustPluginsContainer,
            condition: (eggName) => eggName === 'Rust',
        },
        {
            path: '/files/:action(edit|new)',
            permission: 'file.*',
            name: undefined,
            component: FileEditContainer,
        },
        {
            path: '/databases',
            permission: 'database.*',
            name: 'Databases',
            component: DatabasesContainer,
            condition: (eggName) => !eggName?.toLowerCase().includes('proxy'),
        },
        {
            path: '/schedules',
            permission: 'schedule.*',
            name: 'Schedules',
            component: ScheduleContainer,
            condition: (eggName) => !eggName?.toLowerCase().includes('proxy'),
        },
        {
            path: '/schedules/:id',
            permission: 'schedule.*',
            name: undefined,
            component: ScheduleEditContainer,
        },
        {
            path: '/users',
            permission: 'user.*',
            name: 'Users',
            component: UsersContainer,
        },
        {
            path: '/backups',
            permission: 'backup.*',
            name: 'Backups',
            component: BackupContainer,
            condition: (eggName) => !eggName?.toLowerCase().includes('proxy'),
        },
        /* {
            path: 'subdomain',
            permission: 'subdomain.*',
            name: 'Subdomain',
            component: SubdomainContainer,
        }, */
        {
            path: '/network',
            permission: 'allocation.*',
            name: 'Network',
            component: NetworkContainer,
        },
        {
            path: '/startup',
            permission: 'startup.*',
            name: 'Startup',
            component: StartupContainer,
        },
        {
            path: '/transfer',
            permission: 'transfer.*',
            name: 'Transfer',
            component: TransferContainer,
        },
        {
            path: '/staff',
            permission: 'staff.*',
            name: 'Staff Requests',
            component: StaffContainer,
        },
        {
            path: '/settings',
            permission: ['settings.*', 'file.sftp'],
            name: 'Settings',
            component: SettingsContainer,
        },
        {
            path: '/activity',
            permission: 'activity.*',
            name: 'Activity',
            component: ServerActivityLogContainer,
        },
    ],
    staff: [
        {
            path: '/staff',
            name: 'Staff',
            component: StaffRequetsContainer,
        },
    ],
} as Routes;
