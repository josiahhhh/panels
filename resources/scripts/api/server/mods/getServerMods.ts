import http from '@/api/http';
import { ModsResponse } from '@/components/server/mods/ModsContainer';

export default async (uuid: string): Promise<ModsResponse> => {
    const { data } = await http.get(`/api/client/servers/${uuid}/mods`);

    return data.data || [];
};
