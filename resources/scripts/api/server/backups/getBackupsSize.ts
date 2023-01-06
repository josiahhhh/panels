import http from '@/api/http';

export interface BackupSizeResponse {
    file: number;
    database: number;
}

export default (uuid: string): Promise<BackupSizeResponse> => {
    return new Promise((resolve, reject) => {
        http.get(`/api/client/servers/${uuid}/backups-size`)
            .then(({ data }) => resolve(data))
            .catch(reject);
    });
};
