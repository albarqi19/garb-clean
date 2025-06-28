<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Config;

class TestDatabaseConnection extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'db:test-connection';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test database connection and display configuration';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔍 Testing Database Connection...');
        $this->newLine();

        // Display current configuration
        $this->info('📋 Current Database Configuration:');
        $this->table(['Setting', 'Value'], [
            ['Driver', Config::get('database.default')],
            ['Host', Config::get('database.connections.pgsql.host')],
            ['Port', Config::get('database.connections.pgsql.port')],
            ['Database', Config::get('database.connections.pgsql.database')],
            ['Username', Config::get('database.connections.pgsql.username')],
            ['SSL Mode', Config::get('database.connections.pgsql.sslmode')],
        ]);
        $this->newLine();

        // Test basic connection
        try {
            $this->info('🔌 Testing basic connection...');
            $pdo = DB::connection()->getPdo();
            $this->info('✅ PDO connection successful');
        } catch (\Exception $e) {
            $this->error('❌ PDO connection failed: ' . $e->getMessage());
            return 1;
        }

        // Test database query
        try {
            $this->info('📊 Testing database query...');
            $result = DB::select('SELECT version() as version');
            $this->info('✅ Database query successful');
            $this->info('📝 PostgreSQL Version: ' . $result[0]->version);
        } catch (\Exception $e) {
            $this->error('❌ Database query failed: ' . $e->getMessage());
            return 1;
        }

        // Test migrations table
        try {
            $this->info('🗃️ Checking migrations table...');
            $exists = DB::select("SELECT EXISTS (SELECT 1 FROM information_schema.tables WHERE table_name = 'migrations')");
            if ($exists[0]->exists) {
                $this->info('✅ Migrations table exists');
                $count = DB::table('migrations')->count();
                $this->info("📊 Migrations count: {$count}");
            } else {
                $this->warn('⚠️ Migrations table does not exist');
            }
        } catch (\Exception $e) {
            $this->error('❌ Migrations table check failed: ' . $e->getMessage());
        }

        $this->newLine();
        $this->info('🎉 Database connection test completed!');
        return 0;
    }
}
