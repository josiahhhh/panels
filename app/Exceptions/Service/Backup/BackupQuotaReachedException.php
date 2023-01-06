<?php

namespace Pterodactyl\Exceptions\Service\Backup;

use Pterodactyl\Exceptions\DisplayException;

class BackupQuotaReachedException extends DisplayException
{
    /**
     * BackupQuotaReachedException constructor.
     *
     * @param int $backupLimit
     */
    public function __construct(int $usedGB, int $backupLimitGB, int $estimated = -1)
    {
        if ($estimated > -1) {
            parent::__construct(
                sprintf('Cannot create a new backup, this server has used %dGB of its %dGB limit for backups, and this backup is estimated to take an additional %dGB.', $usedGB, $backupLimitGB, $estimated)
            );
        } else {
            parent::__construct(
                sprintf('Cannot create a new backup, this server has used %dGB of its %dGB limit for backups.', $usedGB, $backupLimitGB)
            );
        }
    }
}
