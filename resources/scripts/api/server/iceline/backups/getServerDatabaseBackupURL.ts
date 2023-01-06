import http from '@/api/http';

export default (uuid: string, backup: string): Promise<string> => {
    return new Promise((resolve, reject) => {
        http.get(`/api/client/servers/${uuid}/backups-database/${backup}/download`)
            .then(({ data }) => resolve(data.url))
            .catch(reject);
    });
};
