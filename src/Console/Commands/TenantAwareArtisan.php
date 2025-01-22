<?php

namespace TenantAware\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use TenantAware\Concerns\MakesTenantAware;
use TenantAware\Exceptions\TenantAwareDatabaseException;

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
        $baseDbManager = DB::connection('mysql')->table('tenant_switcher')->select('id', 'tenant_name', 'tenant_domain', 'tenant_database');
        $tenants = $this->option('tenant');
        $tenantSwitcher = $tenants ?
            $baseDbManager->whereIn('id', $tenants)->orWhereIn('tenant_name', $tenants)->get() :
            $baseDbManager->get();

        $artisanCommand = $this->argument('artisanCommand');
        $params = $this->option('params');

        $individualTenantCommand = "$artisanCommand $params";

        foreach ($tenantSwitcher as $switched) {
            $this->newLine();
            $this->line('---------------------------------------------------------------');
            $this->info("Running '$artisanCommand' for {$switched->tenant_name} (Tenant ID: {$switched->id})");
            $this->line('---------------------------------------------------------------');

            try {
                $this->configureDatabases($switched);
                $this->doesCurrentDatabaseExist($switched->tenant_database);
                Artisan::call($individualTenantCommand);
                $this->line("Successfully ran: $individualTenantCommand");
            } catch (\Throwable $e) {
                $this->line('An exception occurred:');
                $this->line($e->getMessage());
                Log::error(__METHOD__.'():', ['stacktrace' => $e->getTrace()]);
            }
        }
    }

    protected function doesCurrentDatabaseExist(string $database): void
    {
        $exists = <<<'SQL'
            SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME =  ?;
        SQL;
        if (empty(DB::connection('mysql')->select($exists, [$database]))) {
            if ($this->confirm("Database '$database' missing. Shall I create it?")) {
                // Security:
                $database = preg_replace('/[^A-Za-z0-9_\-]/', '', $database);
                $db = <<<SQL
                    CREATE SCHEMA IF NOT EXISTS `$database`
                        DEFAULT CHARACTER SET = utf8mb4
                        COLLATE = utf8mb4_unicode_ci;
                SQL;
                DB::connection('mysql')->select($db);
                $this->line("Database '$database' successfully created!");

                return;
            }

            throw new TenantAwareDatabaseException('Skipping creation of this database..');
        }
    }
}
