<?php

namespace Pterodactyl\Jobs\Schedule;

use Exception;
use Pterodactyl\Jobs\Job;
use Carbon\CarbonImmutable;
use Pterodactyl\Models\Task;
use InvalidArgumentException;
use Pterodactyl\Models\Database;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Pterodactyl\Exceptions\DisplayException;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Pterodactyl\Services\Iceline\RustWipeService;
use Pterodactyl\Contracts\Extensions\HashidsInterface;
use Pterodactyl\Services\Backups\InitiateBackupService;
use Pterodactyl\Extensions\Iceline\DatabaseBackupManager;
use Pterodactyl\Repositories\Wings\DaemonPowerRepository;
use Pterodactyl\Repositories\Wings\DaemonCommandRepository;
use Pterodactyl\Exceptions\Http\Connection\DaemonConnectionException;

class RunTaskJob extends Job implements ShouldQueue
{
    use DispatchesJobs;
    use InteractsWithQueue;
    use SerializesModels;

    /**
     * @var \Pterodactyl\Models\Task
     */
    public $task;

    /**
     * @var bool
     */
    public $manualRun;

    /**
     * RunTaskJob constructor.
     */
    public function __construct(Task $task, $manualRun = false)
    {
        $this->queue = config('pterodactyl.queues.standard');
        $this->task = $task;
        $this->manualRun = $manualRun;
    }

    /**
     * Run the job and send actions to the daemon running the server.
     *
     * @param DaemonCommandRepository $commandRepository
     * @param InitiateBackupService $backupService
     * @param DaemonPowerRepository $powerRepository
     * @param DatabaseBackupManager $databaseBackupManager
     * @param HashidsInterface $hashids
     * @param RustWipeService $rustWipeService
     *
     * @throws DaemonConnectionException
     * @throws DisplayException
     * @throws \Pterodactyl\Exceptions\Service\Backup\TooManyBackupsException
     * @throws \Throwable
     *
     * @return void
     */
    public function handle(
        DaemonCommandRepository $commandRepository,
        InitiateBackupService   $backupService,
        DaemonPowerRepository   $powerRepository,
        DatabaseBackupManager   $databaseBackupManager,
        HashidsInterface        $hashids,
        RustWipeService         $rustWipeService
    )
    {
        // Do not process a task that is not set to active, unless it's been manually triggered.
        if (!$this->task->schedule->is_active && !$this->manualRun) {
            $this->markTaskNotQueued();
            $this->markScheduleComplete();

            return;
        }

        $server = $this->task->server;
        // If we made it to this point and the server status is not null it means the
        // server was likely suspended or marked as reinstalling after the schedule
        // was queued up. Just end the task right now — this should be a very rare
        // condition.
        if (!is_null($server->status)) {
            $this->failed();

            return;
        }

        // Perform the provided task against the daemon.
        try {
            switch ($this->task->action) {
                case Task::ACTION_POWER:
                    $powerRepository->setServer($server)->send($this->task->payload);
                    break;
                case Task::ACTION_COMMAND:
                    $commandRepository->setServer($server)->send($this->task->payload);
                    break;
                case Task::ACTION_BACKUP:
                    $backupService->setIgnoredFiles(explode(PHP_EOL, $this->task->payload))->handle($server, null, true);
                    break;
                case Task::ACTION_DATABASE_BACKUP:
                    $parameters = json_decode($this->task->payload, true);

                    $database_id = $hashids->decodeFirst($parameters['database']);

                    // Get a handle to the database to backup
                    /** @var Database $database */
                    $database = $server->databases()->where('id', '=', $database_id)->first();
                    if ($database == null) {
                        throw new DisplayException('Cannot find database with id ' . $parameters['database']);
                    }

                    // TODO: hang while executing the backup so the scheduler can properly detect task errors
                    $databaseBackupManager->start($server, $database, $parameters['name']);
                    break;
                CASE Task::ACTION_WIPE_SERVER:
                    $rustWipeService->handle($server, $this->task->payload);
                    break;
                default:
                    throw new InvalidArgumentException('Invalid task action provided: ' . $this->task->action);
            }
        } catch (Exception $exception) {
            // If this isn't a DaemonConnectionException on a task that allows for failures
            // throw the exception back up the chain so that the task is stopped.
            if (!($this->task->continue_on_failure && $exception instanceof DaemonConnectionException)) {
                throw $exception;
            }
        }

        $this->markTaskNotQueued();
        $this->queueNextTask();
    }

    /**
     * Handle a failure while sending the action to the daemon or otherwise processing the job.
     */
    public function failed(Exception $exception = null)
    {
        if (app()->bound('sentry')) {
            app('sentry')->captureException($exception);
        }

        $this->markTaskNotQueued();
        $this->markScheduleComplete();
    }

    /**
     * Get the next task in the schedule and queue it for running after the defined period of wait time.
     */
    private function queueNextTask()
    {
        /** @var \Pterodactyl\Models\Task|null $nextTask */
        $nextTask = Task::query()->where('schedule_id', $this->task->schedule_id)
            ->orderBy('sequence_id', 'asc')
            ->where('sequence_id', '>', $this->task->sequence_id)
            ->first();

        if (is_null($nextTask)) {
            $this->markScheduleComplete();

            return;
        }

        $nextTask->update(['is_queued' => true]);

        $this->dispatch((new self($nextTask, $this->manualRun))->delay($nextTask->time_offset));
    }

    /**
     * Marks the parent schedule as being complete.
     */
    private function markScheduleComplete()
    {
        $this->task->schedule()->update([
            'is_processing' => false,
            'last_run_at' => CarbonImmutable::now()->toDateTimeString(),
        ]);
    }

    /**
     * Mark a specific task as no longer being queued.
     */
    private function markTaskNotQueued()
    {
        $this->task->update(['is_queued' => false]);
    }
}
