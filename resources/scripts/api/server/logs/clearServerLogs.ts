import http from '@/api/http';

export default (uuid: string): Promise<void> => {
    return new Promise((resolve, reject) => {
        http.delete(`/api/client/servers/${uuid}/logs/delete`)
            .then(() => resolve())
            .catch(reject);
    });
};
