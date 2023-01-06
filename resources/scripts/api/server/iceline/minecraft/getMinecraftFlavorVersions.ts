import http from '@/api/http';

export interface MinecraftVersion {
    id: string;
    name: string;
    builds: string[];
    url: string;
    latest?: boolean;
}

export default async (uuid: string, flavor: string): Promise<MinecraftVersion[]> => {
    const { data } = await http.get(`/api/client/servers/${uuid}/minecraft/versions/${flavor}`);

    return data.data || [];
};
