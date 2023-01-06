import http from '@/api/http';

export interface BackupRestoreStatus {
    uuid: string;
    type: string;
    databaseBackupID: number;
    backupID: number;
    isSuccessful: boolean;
    error: string;
    createdAt: Date;
    completedAt: Date | null;
}

export const rawDataToServerBackupRestoreStatus = (attributes: any): BackupRestoreStatus => ({
    uuid: attributes.uuid,
    type: attributes.type,
    databaseBackupID: attributes.database_backup_id,
    backupID: attributes.backup_id,
    isSuccessful: attributes.is_successful,
    createdAt: new Date(attributes.created_at),
    completedAt: attributes.completed_at ? new Date(attributes.completed_at) : null,
    error: attributes.error,
});

export default (uuid: string): Promise<BackupRestoreStatus | null> => {
    return new Promise((resolve, reject) => {
        http.get(`/api/client/servers/${uuid}/restoring`)
            .then(({ data }) => resolve(rawDataToServerBackupRestoreStatus(data)))
            .catch((err) => {
                if (err.response) {
                    if (err.response.status === 404) {
                        resolve(null);
                        return;
                    }
                }

                reject(err);
            });
    });
};
