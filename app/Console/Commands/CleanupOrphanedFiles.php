<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use App\Models\SalaryFile;
use App\Models\Template;
use App\Models\TemplateSelection;
use App\Models\SalaryBulkExport;

class CleanupOrphanedFiles extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:cleanup-orphaned-files';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clean up orphaned private files in storage/app/private';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting cleanup...');
        $disk = Storage::disk('local'); // maps to storage/app/private

        // 1. Clean salary-files
        $this->info('--- Cleaning salary-files ---');
        $dbPaths = collect();
        SalaryFile::chunk(100, function($files) use ($dbPaths) {
            foreach ($files as $f) {
                $dbPaths->push($this->normalizeDbPath($f->file_path));
                $dbPaths->push($this->normalizeDbPath($f->salary_sheet_path));
            }
        });
        
        if ($disk->exists('salary-files')) {
            $fsFiles = $disk->files('salary-files');
            foreach ($fsFiles as $file) {
                if (!$dbPaths->contains($file)) {
                    $this->line("Deleting orphan: $file");
                    $disk->delete($file);
                }
            }
        }

        // 2. Clean templates (excluding logos dir)
        $this->info('--- Cleaning templates ---');
        $dbTemplateFiles = TemplateSelection::pluck('file_name')->map(fn($name) => 'templates/' . $name);
        
        if ($disk->exists('templates')) {
            $fsTemplates = $disk->files('templates');
            foreach ($fsTemplates as $file) {
                if (!$dbTemplateFiles->contains($file)) {
                    $this->line("Deleting orphan: $file");
                    $disk->delete($file);
                }
            }
        }

        // 3. Clean templates/logos
        $this->info('--- Cleaning templates/logos ---');
        $dbLogos = Template::pluck('logo_path')->map(fn($path) => $this->normalizeDbPath($path));
        
        if ($disk->exists('templates/logos')) {
            $fsLogos = $disk->files('templates/logos');
            foreach ($fsLogos as $file) {
                if (!$dbLogos->contains($file)) {
                    $this->line("Deleting orphan: $file");
                    $disk->delete($file);
                }
            }
        }

        // 4. Clean salary-bulk
        $this->info('--- Cleaning salary-bulk ---');
        $dbBulkParams = SalaryBulkExport::pluck('file_path')->map(fn($path) => $this->normalizeDbPath($path));
        
        if ($disk->exists('salary-bulk')) {
            $fsBulk = $disk->files('salary-bulk');
            foreach ($fsBulk as $file) {
                if (!$dbBulkParams->contains($file)) {
                    $this->line("Deleting orphan: $file");
                    $disk->delete($file);
                }
            }
        }

        // 5. Clean temporary directories
        $tempDirs = ['mail_uploads', 'excel', 'salary-bulk-temp'];
        foreach ($tempDirs as $dir) {
            $this->info("--- Cleaning $dir ---");
            if ($disk->exists($dir)) {
                $files = $disk->files($dir);
                foreach ($files as $file) {
                    $this->line("Deleting file: $file");
                    $disk->delete($file);
                }
                
                // Try to remove dir if empty
                if (empty($disk->allFiles($dir))) {
                    $disk->deleteDirectory($dir);
                }
            }
        }

        $this->info('Cleanup complete.');
    }

    private function normalizeDbPath($path)
    {
        if (!$path) return null;
        $path = str_replace(['storage/app/private/', 'storage/app/'], '', $path);
        return trim($path, '/');
    }
}
