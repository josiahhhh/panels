import http from '@/api/http';

export default (uuid: string, root: string, url: string): Promise<void> => {
    return new Promise((resolve, reject) => {
        http.post(
            `/api/client/servers/${uuid}/files/download-url`,
            { root, url },
            {
                timeout: 120000,
            }
        )
            .then(() => resolve())
            .catch(reject);
    });
};
