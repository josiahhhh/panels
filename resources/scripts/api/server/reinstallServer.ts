import http from '@/api/http';

export default (uuid: string, purgeFiles?: boolean): Promise<void> => {
    return new Promise((resolve, reject) => {
        http.post(`/api/client/servers/${uuid}/settings/reinstall?purge=` + purgeFiles)
            .then(() => resolve())
            .catch(reject);
    });
};
