import http from '@/api/http';

export interface BackupSettings {
    // eslint-disable-next-line camelcase
    backup_retention: number;
}

export default (server: string): Promise<BackupSettings> => {
    return new Promise((resolve, reject) => {
        http.get(`/api/client/servers/${server}/settings/backups`)
            .then(({ data }) => resolve(data))
            .catch(reject);
    });
};
