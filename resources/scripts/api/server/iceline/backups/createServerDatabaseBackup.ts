import http from '@/api/http';
import { rawDataToServerDatabaseBackup } from '@/api/server/iceline/backups/getServerDatabaseBackups';

export default (uuid: string, database: string, name?: string): Promise<any> => {
    return new Promise((resolve, reject) => {
        http.post(`/api/client/servers/${uuid}/backups-database`, {
            database: database,
            name: name,
        })
            .then((data) => {
                resolve(rawDataToServerDatabaseBackup(data.data));
            })
            .catch(reject);
    });
};
