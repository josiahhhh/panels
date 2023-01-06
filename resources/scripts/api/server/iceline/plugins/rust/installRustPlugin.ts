import http from '@/api/http';

export default async (uuid: string, slug: string): Promise<void> => {
    await http.post(`/api/client/servers/${uuid}/plugins/rust/install`, {
        plugin: slug,
    });
};
