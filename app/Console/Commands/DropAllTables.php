<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DropAllTables extends Command
{
    protected $signature = 'db:drop-all-tables';
    protected $description = 'Drop all specified tables from the database';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        // List of tables to drop (extracted from your input)
        $tables = [
            'attendences',
            'billings',
            'cache',
            'cache_locks',
            'classes',
            'courses',
            'course_reviews',
            'events',
            'failed_jobs',
            'jobs',
            'job_batches',
            'migrations',
            'outlines',
            'sessions',
            'students',
            'student_performances',
            'student_policies',
            'teachers',
            'teacher_reviews',
            'users',
        ];

        // Disable foreign key checks to avoid constraint errors
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        foreach ($tables as $table) {
            if (Schema::hasTable($table)) {
                Schema::dropIfExists($table);
                $this->info("Dropped table: {$table}");
            } else {
                $this->warn("Table does not exist: {$table}");
            }
        }

        // Re-enable foreign key checks
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $this->info('All specified tables have been dropped successfully!');
    }
}