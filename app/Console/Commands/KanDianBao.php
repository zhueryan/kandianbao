<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class KanDianBao extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'task:kandianbao';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '看店宝爬虫';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
	dd('Hello　看店宝');
    }
}
