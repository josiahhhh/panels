import http from '@/api/http';
import { DatabaseBackup } from '@/api/server/iceline/backups/getServerDatabaseBackups';

export const rawDataToServerDatabaseBackup = (attributes: any): DatabaseBackup => ({
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

export default (uuid: string, backup: string): Promise<any> => {
    return new Promise((resolve, reject) => {
        http.post(`/api/client/servers/${uuid}/backups-database/${backup}/restore`, {})
            .then((data) => {
                resolve(rawDataToServerDatabaseBackup(data.data));
            })
            .catch(reject);
    });
};
