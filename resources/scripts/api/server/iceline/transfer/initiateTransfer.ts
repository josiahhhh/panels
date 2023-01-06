import http from '@/api/http';

export default async (uuid: string, locationId: number): Promise<any> => {
    const { data } = await http.post(`/api/client/servers/${uuid}/transfer`, {
        location_id: locationId,
    });

    return data.data || [];
};
