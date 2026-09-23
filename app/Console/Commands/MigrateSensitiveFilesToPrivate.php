<?php

namespace App\Console\Commands;

use App\Models\Attendance;
use App\Models\EmployeeDocument;
use App\Models\FormCuti;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class MigrateSensitiveFilesToPrivate extends Command
{
    protected $signature = 'files:migrate-sensitive-to-private {--dry-run : Report files without copying} {--delete-public : Delete public copies after verified copy}';
    protected $description = 'Copies legacy sensitive files from public storage to private storage safely.';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $deletePublic = (bool) $this->option('delete-public');
        $paths = collect();

        EmployeeDocument::query()->select(['id', 'ktp_file', 'kk_file', 'cv_file', 'ijazah_file', 'kontrak_file'])->each(function (EmployeeDocument $document) use ($paths): void {
            $paths->push($document->ktp_file, $document->kk_file, $document->cv_file, $document->ijazah_file, $document->kontrak_file);
        });
        FormCuti::query()->whereNotNull('lampiran')->pluck('lampiran')->each(fn ($path) => $paths->push($path));
        Attendance::query()->select(['clock_in_photo', 'clock_out_photo'])->each(function (Attendance $attendance) use ($paths): void {
            $paths->push($attendance->clock_in_photo, $attendance->clock_out_photo);
        });

        $copied = 0;
        $missing = 0;
        $skipped = 0;

        foreach ($paths->filter()->unique() as $path) {
            if (Storage::disk('private')->exists($path)) {
                if (! $dryRun && $deletePublic && Storage::disk('public')->exists($path)) {
                    Storage::disk('public')->delete($path);
                    $copied++;
                    continue;
                }

                $skipped++;
                continue;
            }

            if (! Storage::disk('public')->exists($path)) {
                $missing++;
                $this->warn("Missing public file: {$path}");
                continue;
            }

            if ($dryRun) {
                $this->line("Would migrate: {$path}");
                $copied++;
                continue;
            }

            $stream = Storage::disk('public')->readStream($path);
            $success = $stream && Storage::disk('private')->writeStream($path, $stream);
            if (is_resource($stream)) {
                fclose($stream);
            }

            if (! $success || ! Storage::disk('private')->exists($path)) {
                $this->error("Failed to copy: {$path}");
                return self::FAILURE;
            }

            if ($deletePublic) {
                Storage::disk('public')->delete($path);
            }

            $copied++;
        }

        $this->info("Processed {$copied}; already private {$skipped}; missing {$missing}.");
        $this->info($dryRun ? 'Dry run complete. No files were changed.' : ($deletePublic ? 'Verified copies moved to private storage.' : 'Files copied to private storage; public copies retained.'));

        return self::SUCCESS;
    }
}
