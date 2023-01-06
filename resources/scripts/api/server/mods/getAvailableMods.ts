import http from '@/api/http';
import { AvailableModsResponse } from '@/components/server/mods/AvailableModList';

export default async (uuid: string): Promise<AvailableModsResponse> => {
    const { data } = await http.get(`/api/client/servers/${uuid}/mods/available`);

    return data.data || [];
};
