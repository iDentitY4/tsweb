<?php

namespace App\Console\Commands;

use App\TsWeb\Bot;
use App\TsWeb\BotOptions;
use App\TsWeb\Events\ClientList;
use Illuminate\Console\Command;
use Symfony\Component\HttpKernel\Client;

class TsBotCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ts:bot 
                            {adapter? : The connection adapter that shall be used}
                            {--force : Force the worker to run even in maintenance mode}
                            {--memory=128 : The memory limit in megabytes}
                            {--sleep=3 : Number of seconds to sleep between queries}
                            {--timeout=60 : The number of seconds a child process can run}
                            {--tries=0 : Number of times to attempt a job before logging it failed}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Start a teamspeak bot';

    protected $bot;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(Bot $bot)
    {
        parent::__construct();

        $this->bot = $bot;
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $this->listenForEvents();

        $adapter = $this->argument('adapter')
            ?: $this->laravel['config']['ts.default'];

        $this->runBot($adapter);
    }

    protected function runBot($adapter)
    {
        $this->bot->daemon($adapter, $this->gatherBotOptions());
    }

    /**
     * Gather all of the bot options as a single object.
     *
     * @return \App\TsWeb\BotOptions
     */
    protected function gatherBotOptions()
    {
        return new BotOptions(
            $this->option('memory'),
            $this->option('timeout'), $this->option('sleep'),
            $this->option('tries'), $this->option('force')
        );
    }

    /**
     * Listen for the queue events in order to update the console output.
     *
     * @return void
     */
    protected function listenForEvents()
    {
        $this->laravel['events']->listen(ClientList::class, function (ClientList $event) {
            $this->info(json_encode($event->clients));
        });
    }
}
