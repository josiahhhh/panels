import http from '@/api/http';

export default (uuid: string, id: number): Promise<void> => {
    return new Promise((resolve, reject) => {
        http.delete(`/api/client/servers/${uuid}/mods/uninstall/${id}`)
            .then(() => resolve())
            .catch(reject);
    });
};
