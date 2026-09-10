<?php

namespace Vonso\FaspayTestLab\Commands;

use Illuminate\Console\Command;

class InstallCommand extends Command
{
    protected $signature = 'faspay-test-lab:install {--force : Overwrite existing published files}';

    protected $description = 'Install and publish Faspay Test Lab resources';

    public function handle(): int
    {
        $this->info('Installing Faspay Test Lab...');

        $params = ['--tag' => 'faspay-test-lab-config'];
        if ($this->option('force')) {
            $params['--force'] = true;
        }

        $this->call('vendor:publish', $params);

        if ($this->confirm('Run package migrations now?', true)) {
            $this->call('migrate');
        }

        $this->info('Faspay Test Lab installed successfully!');
        $prefix = config('faspay-test-lab.route_prefix', 'faspay-test-lab');
        $this->comment("Access the dashboard at: /{$prefix}");

        return self::SUCCESS;
    }
}
