import http from '@/api/http';

export default async (uuid: string, filename: string): Promise<void> => {
    await http.post(`/api/client/servers/${uuid}/plugins/rust/uninstall`, {
        filename: filename,
    });
};
