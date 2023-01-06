import http from '@/api/http';

export interface TransferLocation {
    id: number;

    short: string;
    long: string;

    addresses: string[];

    ping?: number;
}

export interface TransferLocationsResponse {
    locations: TransferLocation[];
    current: TransferLocation;
}

export default async (uuid: string): Promise<TransferLocationsResponse> => {
    const { data } = await http.get(`/api/client/servers/${uuid}/transfer/locations`);

    return data.data || [];
};
