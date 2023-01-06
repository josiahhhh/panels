import http from '@/api/http';

export default async (uuid: string, flavor: string, version: string): Promise<void> => {
    await http.post(`/api/client/servers/${uuid}/minecraft/versions/change`, {
        flavor: flavor,
        version: version,
    });
};
