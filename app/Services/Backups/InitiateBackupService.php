<?php

namespace Pterodactyl\Services\Backups;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Arr;
use Pterodactyl\Exceptions\Service\Backup\BackupQuotaReachedException;
use Pterodactyl\Repositories\Wings\DaemonServerRepository;
use Ramsey\Uuid\Uuid;
use Carbon\CarbonImmutable;
use Webmozart\Assert\Assert;
use Pterodactyl\Models\Backup;
use Pterodactyl\Models\Server;
use Illuminate\Database\ConnectionInterface;
use Pterodactyl\Extensions\Backups\BackupManager;
use Pterodactyl\Repositories\Eloquent\BackupRepository;
use Pterodactyl\Repositories\Wings\DaemonBackupRepository;
use Pterodactyl\Exceptions\Service\Backup\TooManyBackupsException;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;

class InitiateBackupService
{
    /**
     * @var string[]|null
     */
    private $ignoredFiles;

    /**
     * @var bool
     */
    private $isLocked = false;

    /**
     * @var \Pterodactyl\Repositories\Eloquent\BackupRepository
     */
    private $repository;

    /**
     * @var \Illuminate\Database\ConnectionInterface
     */
    private $connection;

    /**
     * @var \Pterodactyl\Repositories\Wings\DaemonBackupRepository
     */
    private $daemonBackupRepository;

    /**
     * @var \Pterodactyl\Extensions\Backups\BackupManager
     */
    private $backupManager;

    /**
     * @var \Pterodactyl\Services\Backups\DeleteBackupService
     */
    private $deleteBackupService;

    /**
     * @var \Pterodactyl\Repositories\Wings\DaemonServerRepository
     */
    private $daemonServerRepository;

    /**
     * InitiateBackupService constructor.
     *
     * @param \Pterodactyl\Services\Backups\DeleteBackupService $deleteBackupService
     * @param \Pterodactyl\Extensions\Backups\BackupManager $backupManager
     * @param DaemonServerRepository $daemonServerRepository
     */
    public function __construct(
        BackupRepository $repository,
        ConnectionInterface $connection,
        DaemonBackupRepository $daemonBackupRepository,
        DeleteBackupService $deleteBackupService,
        BackupManager $backupManager,
        DaemonServerRepository $daemonServerRepository
    ) {
        $this->repository = $repository;
        $this->connection = $connection;
        $this->daemonBackupRepository = $daemonBackupRepository;
        $this->backupManager = $backupManager;
        $this->deleteBackupService = $deleteBackupService;
        $this->daemonServerRepository = $daemonServerRepository;
    }

    /**
     * Set if the backup should be locked once it is created which will prevent
     * its deletion by users or automated system processes.
     *
     * @return $this
     */
    public function setIsLocked(bool $isLocked): self
    {
        $this->isLocked = $isLocked;

        return $this;
    }

    /**
     * Sets the files to be ignored by this backup.
     *
     * @param string[]|null $ignored
     *
     * @return $this
     */
    public function setIgnoredFiles(?array $ignored)
    {
        if (is_array($ignored)) {
            foreach ($ignored as $value) {
                Assert::string($value);
            }
        }

        // Set the ignored files to be any values that are not empty in the array. Don't use
        // the PHP empty function here incase anything that is "empty" by default (0, false, etc.)
        // were passed as a file or folder name.
        $this->ignoredFiles = is_null($ignored) ? [] : array_filter($ignored, function ($value) {
            return strlen($value) > 0;
        });

        return $this;
    }

    /**
     * Initiates the backup process for a server on Wings.
     *
     * @throws \Throwable
     * @throws \Pterodactyl\Exceptions\Service\Backup\TooManyBackupsException
     * @throws \Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException
     */
    public function handle(Server $server, string $name = null, bool $override = false): Backup
    {
        // Do not allow the user to continue if this server is already at its limit.
        $limit = config('backups.throttles.limit');
        $period = config('backups.throttles.period');
        if ($period > 0) {
            $previous = $this->repository->getBackupsGeneratedDuringTimespan($server->id, $period);
            if ($previous->count() >= $limit) {
                $message = sprintf('Only %d backups may be generated within a %d second span of time.', $limit, $period);

                throw new TooManyRequestsHttpException(CarbonImmutable::now()->diffInSeconds($previous->last()->created_at->addSeconds($period)), $message);
            }
        }

        // Do not allow the user to continue if this server is already at its limit.
        if ($server->backup_limit) {
            //if ($server->backup_limit_by_size) {
                $fileBackupTotalGB = DB::table('backups')
                        ->where('server_id', '=', $server->id)
                        ->where('is_successful', '=', true)
                        ->whereNull('deleted_at')
                        ->sum('bytes') / 1073741824;
                $databaseBackupTotalGB = DB::table('database_backups')
                        ->where('server_id', '=', $server->id)
                        ->where('is_successful', '=', true)
                        ->sum('bytes') / 1073741824;
                $totalUsed = $fileBackupTotalGB + $databaseBackupTotalGB;

                $serverDetails = $this->daemonServerRepository->setServer($server)->getDetails();
                $serverDiskUsage = Arr::get($serverDetails, 'utilization.disk_bytes', 0) / 1073741824;
                // $serverDiskUsageZipped = $serverDiskUsage * 0.38; // "The standard zip format provided approximately 62 percent compression" - https://www.techwalla.com/articles/how-much-does-a-zip-file-compress
                // TODO: Calculation was way off for a lot of servers with large files, need to re-evaluate later on
                $serverDiskUsageZipped = $serverDiskUsage;

                Log::info('checking current backup sizes against limit', [
                    'fileBackupTotalGB' => $fileBackupTotalGB,
                    'databaseBackupTotalGB' => $databaseBackupTotalGB,
                    'estimatedNewBackupUsage' => $serverDiskUsageZipped,
                    'limit' => $server->backup_limit
                ]);

                if (($totalUsed + $serverDiskUsageZipped) >= $server->backup_limit) {
                    throw new BackupQuotaReachedException($totalUsed, $server->backup_limit, $serverDiskUsageZipped);
                }
//            } else {
//                // Check if the server has reached or exceeded it's backup limit
//                if (! $server->backup_limit || $server->backups()->where('is_successful', true)->count() >= $server->backup_limit) {
//                    // Do not allow the user to continue if this server is already at its limit and can't override.
//                    if (! $override || $server->backup_limit <= 0) {
//                        throw new TooManyBackupsException($server->backup_limit);
//                    }
//
//                    // Get the oldest backup the server has.
//                    /** @var \Pterodactyl\Models\Backup $oldestBackup */
//                    $oldestBackup = $server->backups()->where('is_successful', true)->orderBy('created_at')->first();
//
//                    // Delete the oldest backup.
//                    $this->deleteBackupService->handle($oldestBackup);
//                }
//            }
        }

        return $this->connection->transaction(function () use ($server, $name) {
            /** @var \Pterodactyl\Models\Backup $backup */
            $backup = $this->repository->create([
                'server_id' => $server->id,
                'uuid' => Uuid::uuid4()->toString(),
                'name' => trim($name) ?: sprintf('Backup at %s', CarbonImmutable::now()->toDateTimeString()),
                'ignored_files' => array_values($this->ignoredFiles ?? []),
                'disk' => $this->backupManager->getDefaultAdapter(),
                'is_locked' => $this->isLocked,
            ], true, true);

            $this->daemonBackupRepository->setServer($server)
                ->setBackupAdapter($this->backupManager->getDefaultAdapter())
                ->backup($backup);

            return $backup;
        });
    }
}
