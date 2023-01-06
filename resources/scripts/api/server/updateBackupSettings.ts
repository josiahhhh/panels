import http from '@/api/http';
import { BackupSettings } from '@/api/server/getBackupSettings';

export default async (uuid: string, settings: BackupSettings): Promise<any> => {
    const { data } = await http.post(`/api/client/servers/${uuid}/settings/backups`, settings);

    return data;
};
