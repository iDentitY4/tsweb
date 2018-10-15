<?php

namespace App\TsWeb;

use Illuminate\Events\Dispatcher;

class Bot
{
    /**
     * The queue manager instance.
     *
     * @var \App\TsWeb\TsManager
     */
    protected $manager;

    /**
     * The event dispatcher instance.
     *
     * @var \Illuminate\Contracts\Events\Dispatcher
     */
    protected $events;

    /**
     * Indicates if the worker should exit.
     *
     * @var bool
     */
    public $shouldQuit = false;

    /**
     * Indicates if the worker is paused.
     *
     * @var bool
     */
    public $paused = false;

    /**
     * Create a new queue worker.
     *
     * @param  \App\TsWeb\TsManager $manager
     * @param  \Illuminate\Events\Dispatcher $events
     * @return void
     */
    public function __construct(TsManager $manager, Dispatcher $events)
    {
        $this->manager = $manager;
        $this->events = $events;
    }

    /**
     * Listen to the given queue in a loop.
     *
     * @param  string  $connectionName
     * @param  \App\TsWeb\BotOptions  $options
     * @return void
     */
    public function daemon($connectionName, BotOptions $options)
    {
        if ($this->supportsAsyncSignals()) {
            $this->listenForSignals();
        }

        //$lastRestart = $this->getTimestampOfLastQueueRestart();

        while (true) {
            // Before reserving any jobs, we will make sure this queue is not paused and
            // if it is we will just pause this worker for a given amount of time and
            // make sure we do not need to kill this worker process off completely.
            if (! $this->daemonShouldRun($options, $connectionName)) {
                $this->pauseBot($options);

                continue;
            }

            if ($this->supportsAsyncSignals()) {
                $this->registerTimeoutHandler($options);
            }

            // First, we will attempt to get the next job off of the queue. We will also
            // register the timeout handler and reset the alarm for this job so it is
            // not stuck in a frozen state forever. Then, we can fire off this job.
            $clients = $this->manager->connection($connectionName)->getNode()->clientListDb();


            // If the daemon should run (not in maintenance mode, etc.), then we can run
            // fire off this job for processing. Otherwise, we will need to sleep the
            // worker so no more jobs are processed until they should be processed.
            /*if ($job) {
                $this->runJob($job, $connectionName, $options);
            } else {
                $this->sleep($options->sleep);
            }*/
            $this->events->dispatch(new Events\ClientList(
                $connectionName, $clients
            ));

            // Finally, we will check to see if we have exceeded our memory limits or if
            // the queue should restart based on other indications. If so, we'll stop
            // this worker and let whatever is "monitoring" it restart the process.
            $this->stopIfNecessary($options);

            $this->sleep($options->sleep);
        }
    }

    /**
     * Determine if the daemon should process on this iteration.
     *
     * @param  \App\TsWeb\BotOptions  $options
     * @param  string  $connectionName
     * @param  string  $queue
     * @return bool
     */
    protected function daemonShouldRun(BotOptions $options, $connectionName)
    {
        return ! (($this->manager->isDownForMaintenance() && ! $options->force) ||
            $this->paused);
    }

    /**
     * Pause the worker for the current loop.
     *
     * @param  \App\TsWeb\BotOptions $options
     * @param  int  $lastRestart
     * @return void
     */
    protected function pauseBot(BotOptions $options)
    {
        $this->sleep($options->sleep > 0 ? $options->sleep : 1);
    }

    /**
     * Stop the process if necessary.
     *
     * @param  \App\TsWeb\BotOptions  $options
     * @param  int  $lastRestart
     * @param  mixed  $job
     */
    protected function stopIfNecessary(BotOptions $options)
    {
        if ($this->shouldQuit) {
            $this->stop();
        } elseif ($this->memoryExceeded($options->memory)) {
            $this->stop(12);
        }
    }

    /**
     * Register the worker timeout handler.
     *
     * @param  \App\TsWeb\BotOptions  $options
     * @return void
     */
    protected function registerTimeoutHandler(BotOptions $options)
    {
        // We will register a signal handler for the alarm signal so that we can kill this
        // process if it is running too long because it has frozen. This uses the async
        // signals supported in recent versions of PHP to accomplish it conveniently.
        pcntl_signal(SIGALRM, function () {
            $this->kill(1);
        });

        /*pcntl_alarm(
            max($this->timeoutForJob($job, $options), 0)
        );*/
    }

    /**
     * Enable async signals for the process.
     *
     * @return void
     */
    protected function listenForSignals()
    {
        pcntl_async_signals(true);

        pcntl_signal(SIGTERM, function () {
            $this->shouldQuit = true;
        });

        pcntl_signal(SIGUSR2, function () {
            $this->paused = true;
        });

        pcntl_signal(SIGCONT, function () {
            $this->paused = false;
        });
    }

    /**
     * Determine if "async" signals are supported.
     *
     * @return bool
     */
    protected function supportsAsyncSignals()
    {
        return extension_loaded('pcntl');
    }

    /**
     * Determine if the memory limit has been exceeded.
     *
     * @param  int   $memoryLimit
     * @return bool
     */
    public function memoryExceeded($memoryLimit)
    {
        return (memory_get_usage(true) / 1024 / 1024) >= $memoryLimit;
    }

    /**
     * Stop listening and bail out of the script.
     *
     * @param  int  $status
     * @return void
     */
    public function stop($status = 0)
    {
        //$this->events->dispatch(new Events\WorkerStopping($status));

        exit($status);
    }

    /**
     * Kill the process.
     *
     * @param  int  $status
     * @return void
     */
    public function kill($status = 0)
    {
        //$this->events->dispatch(new Events\WorkerStopping($status));

        if (extension_loaded('posix')) {
            posix_kill(getmypid(), SIGKILL);
        }

        exit($status);
    }

    /**
     * Sleep the script for a given number of seconds.
     *
     * @param  int|float   $seconds
     * @return void
     */
    public function sleep($seconds)
    {
        if ($seconds < 1) {
            usleep($seconds * 1000000);
        } else {
            sleep($seconds);
        }
    }

    /**
     * Get the queue manager instance.
     *
     * @return \Illuminate\Queue\QueueManager
     */
    public function getManager()
    {
        return $this->manager;
    }

    /**
     * Set the queue manager instance.
     *
     * @param  \Illuminate\Queue\QueueManager  $manager
     * @return void
     */
    public function setManager(QueueManager $manager)
    {
        $this->manager = $manager;
    }
}