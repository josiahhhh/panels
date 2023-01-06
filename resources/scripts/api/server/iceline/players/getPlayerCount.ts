import http from '@/api/http';

export interface Player {
    id: string;
    name: string;
    metadata: any;
    isOp: boolean;
    inWhitelist: boolean;
}

export interface PlayerCount {
    max: number;
    current: number;
    players: Player[];
}

export default async (uuid: string): Promise<PlayerCount | null> => {
    const { data } = await http.get(`/api/client/servers/${uuid}/players/count`);

    return data.data || null;
};
