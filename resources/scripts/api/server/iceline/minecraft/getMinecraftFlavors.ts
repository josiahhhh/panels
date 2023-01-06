import http from '@/api/http';

export interface MinecraftFlavor {
    id: string;
    name: string;
    latestVersion: string;
}

export default async (uuid: string): Promise<MinecraftFlavor[]> => {
    const { data } = await http.get(`/api/client/servers/${uuid}/minecraft/versions`);

    return data.data || [];
};
