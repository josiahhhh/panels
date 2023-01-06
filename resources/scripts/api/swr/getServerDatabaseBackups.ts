import useSWR from 'swr';
import http, { getPaginationSet, PaginatedResult } from '@/api/http';
import { ServerContext } from '@/state/server';
import { createContext, useContext } from 'react';
import { DatabaseBackup, rawDataToServerDatabaseBackup } from '@/api/server/iceline/backups/getServerDatabaseBackups';

interface ctx {
    page: number;
    setPage: (value: number | ((s: number) => number)) => void;
}

export const Context = createContext<ctx>({ page: 1, setPage: () => 1 });

export default () => {
    const { page } = useContext(Context);
    const uuid = ServerContext.useStoreState((state) => state.server.data!.uuid);

    return useSWR<PaginatedResult<DatabaseBackup>>(['server:databaseBackups', uuid, page], async () => {
        const { data } = await http.get(`/api/client/servers/${uuid}/backups-database`, { params: { page } });

        return {
            items: (data.data || []).map(rawDataToServerDatabaseBackup),
            pagination: getPaginationSet(data.meta.pagination),
        };
    });
};
