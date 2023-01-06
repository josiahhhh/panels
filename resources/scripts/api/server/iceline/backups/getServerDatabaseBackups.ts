import useSWR from 'swr';
import http, { FractalResponseData, getPaginationSet, PaginatedResult } from '@/api/http';
import { ServerContext } from '@/state/server';

export interface DatabaseBackup {
    uuid: string;
    serverID: number;
    databaseID: number;
    isSuccessful: boolean;
    name: string;
    createdAt: Date;
    completedAt: Date | null;
    error: string;
    bytes: number;
}

export const rawDataToServerDatabaseBackup = ({ attributes }: FractalResponseData): DatabaseBackup => ({
    uuid: attributes.uuid,
    isSuccessful: attributes.is_successful,
    serverID: attributes.server_id,
    databaseID: attributes.database_id,
    name: attributes.name,
    createdAt: new Date(attributes.created_at),
    completedAt: attributes.completed_at ? new Date(attributes.completed_at) : null,
    error: attributes.error,
    bytes: attributes.bytes,
});

export default (page?: number | string) => {
    const uuid = ServerContext.useStoreState((state) => state.server.data!.uuid);

    return useSWR<PaginatedResult<DatabaseBackup>>(['server:backups:database', uuid, page], async () => {
        const { data } = await http.get(`/api/client/servers/${uuid}/backups-database`, { params: { page } });

        return {
            items: (data.data || []).map(rawDataToServerDatabaseBackup),
            pagination: getPaginationSet(data.meta.pagination),
        };
    });
};
