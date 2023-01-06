import http from '@/api/http';

export default async (uuid: string, resource: number): Promise<void> => {
    await http.post(`/api/client/servers/${uuid}/minecraft/plugins/install`, {
        resource: resource,
    });
};
