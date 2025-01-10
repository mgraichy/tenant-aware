<?php

namespace Mgraichy\TenantAware\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Mgraichy\TenantAware\Concerns\MakesTenantAware;


class TenantAwareArtisan extends Command
{
    use MakesTenantAware;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tenants:foreach
        {artisanCommand : The Artisan "command:modifier" you want to apply to 1+ tenants}
        {--tenant=* : Which tenant(s) to run the command on. Accepts: "tenant_switcher.id" or "tenant_switcher.tenant_name". Leave absent for "all tenants".}
        {--params= : Standard Artisan parameters e.g. --params="--database=tenant --no-interaction"}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Runs an Artisan command on 1+ tenants';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $baseDbManager = DB::table('tenant_switcher')->select('id', 'tenant_name', 'tenant_domain', 'tenant_database');
        $tenants = $this->option('tenant');
        $tenantSwitcher = $tenants ?
            $baseDbManager->whereIn('id', $tenants)->orWhereIn('tenant_name', $tenants)->get() :
            $baseDbManager->get();

        $artisanCommand = $this->argument('artisanCommand');
        $params = $this->option('params');

        $individualTenantCommand = "$artisanCommand $params";

        foreach ($tenantSwitcher as $switched) {
            $this->newLine(2);
            $this->line("---------------------------------------------------------------");
            $this->info("Running '$artisanCommand' for {$switched->tenant_name} (Tenant ID: {$switched->id})");
            $this->line("---------------------------------------------------------------");
            $this->configureTenant($switched);
            try {
                Artisan::call($individualTenantCommand);
                $this->line("Successfully ran '$artisanCommand'");
            } catch(\Throwable $e) {
                $this->line($e->getMessage());
            }
        }
    }

    protected function configureTenant($switched)
    {
        $this->configureDatabases($switched);
        $this->configureQueue();
    }
}
