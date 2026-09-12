<?php

declare(strict_types=1);

namespace BedFight\Utils;

use BedFight\Core\BedFight;
use Closure;
use pocketmine\scheduler\AsyncTask;
use pocketmine\Server;
use Threaded;
use function count;

class AsyncTaskScheduler {

    private BedFight $plugin;
    private int $maxThreads;
    private Threaded $taskQueue;
    private int $runningTasks = 0;
    private array $callbacks = [];

    public function __construct(BedFight $plugin, int $maxThreads = 4) {
        $this->plugin = $plugin;
        $this->maxThreads = $maxThreads;
        $this->taskQueue = new Threaded();
    }

    public function submit(callable $task, ?callable $callback = null): void {
        $asyncTask = new class($task, $callback) extends AsyncTask {
            private mixed $task;
            private mixed $callback = null;
            private mixed $result = null;
            private ?\Throwable $error = null;

            public function __construct(mixed $task, mixed $callback) {
                $this->task = $task;
                $this->callback = $callback;
            }

            public function onRun(): void {
                try {
                    $this->result = ($this->task)();
                } catch (\Throwable $e) {
                    $this->error = $e;
                }
            }

            public function onCompletion(Server $server): void {
                if ($this->callback !== null) {
                    if ($this->error !== null) {
                        ($this->callback)($this->error, null);
                    } else {
                        ($this->callback)(null, $this->result);
                    }
                }
            }
        };

        $this->plugin->getServer()->getAsyncPool()->submitTask($asyncTask);
        $this->runningTasks++;
    }

    public function submitBatch(array $tasks, callable $onComplete): void {
        $completed = 0;
        $results = [];
        $errors = [];
        $total = count($tasks);

        foreach ($tasks as $index => $task) {
            $this->submit($task, function (?\Throwable $error, mixed $result) use ($index, $total, &$completed, &$results, &$errors, $onComplete) {
                $completed++;
                if ($error !== null) {
                    $errors[$index] = $error;
                } else {
                    $results[$index] = $result;
                }

                if ($completed === $total) {
                    $onComplete($errors, $results);
                }
            });
        }
    }

    public function runAsync(callable $task): void {
        $this->submit($task);
    }

    public function runAsyncWithCallback(callable $task, callable $callback): void {
        $this->submit($task, $callback);
    }

    public function getRunningTasks(): int {
        return $this->runningTasks;
    }

    public function shutdown(): void {
        while ($this->runningTasks > 0) {
            usleep(10000);
        }
    }
}