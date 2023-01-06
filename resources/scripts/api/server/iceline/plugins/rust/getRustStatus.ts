import http from '@/api/http';

export interface RustStatus {
    foundOxide: boolean;
}

export default async (uuid: string): Promise<RustStatus> => {
    const { data } = await http.get(`/api/client/servers/${uuid}/plugins/rust/status`);

    return data || {};
};
