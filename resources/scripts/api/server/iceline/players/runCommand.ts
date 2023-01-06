import http from '@/api/http';

export default (uuid: string, command: string): Promise<any> => {
    return new Promise((resolve, reject) => {
        http.post(`/api/client/servers/${uuid}/command`, {
            command,
        })
            .then((data) => {
                resolve(data.data || []);
            })
            .catch(reject);
    });
};
