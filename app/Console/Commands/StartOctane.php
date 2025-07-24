<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class StartOctane extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:start-octane {--host=127.0.0.1} {--port=8000} {--workers=1}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $path = storage_path('logs/octane-server-state.json');
        if (file_exists($path)) {
            unlink($path);
        }

        $this->call('octane:start', [
            '--host' => $this->option('host'),
            '--port' => $this->option('port'),
            '--workers' => $this->option('workers'),
        ]);
    }
}
