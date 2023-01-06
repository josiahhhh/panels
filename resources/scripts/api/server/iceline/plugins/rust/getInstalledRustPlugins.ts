import http from '@/api/http';
import { RustPlugin, toRustPlugin } from '@/api/server/iceline/plugins/rust/getRustPlugins';

export interface InstalledRustPlugin {
    filename: string;
    name: string;
    version: string;
    manifest: RustPlugin;
}

export interface RustPluginResponse {
    plugins: InstalledRustPlugin[];
}

export default async (uuid: string): Promise<RustPluginResponse> => {
    const { data } = await http.get(`/api/client/servers/${uuid}/plugins/rust/plugins`);

    data.plugins = data.plugins.map((p: InstalledRustPlugin) => ({ ...p, manifest: toRustPlugin(p.manifest, p) }));

    return data || {};
};
