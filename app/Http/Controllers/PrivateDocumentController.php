<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\FormCuti;
use App\Models\User;
use Illuminate\Support\Facades\Storage;

class PrivateDocumentController extends Controller
{
    public function userDocument(User $user, string $field)
    {
        abort_unless(in_array($field, ['ktp_file', 'kk_file', 'cv_file', 'ijazah_file', 'kontrak_file'], true), 404);
        $viewer = auth()->user();
        abort_unless($viewer->id === $user->id || $viewer->isAdmin() || $viewer->isSuperadmin(), 403);

        return $this->download($user->document?->{$field}, 'documents/');
    }

    public function leaveAttachment(FormCuti $cuti)
    {
        $viewer = auth()->user();
        abort_unless($viewer->id === $cuti->user_id || $viewer->isAdmin() || $viewer->isSuperadmin() || $cuti->user?->profile?->atasan_id === $viewer->id, 403);

        return $this->download($cuti->lampiran, 'lampiran-cuti/');
    }

    public function attendancePhoto(Attendance $attendance, string $field)
    {
        abort_unless(in_array($field, ['clock_in_photo', 'clock_out_photo'], true), 404);
        $viewer = auth()->user();
        abort_unless($viewer->id === $attendance->user_id || $viewer->isAdmin() || $viewer->isSuperadmin(), 403);

        return $this->download($attendance->{$field}, 'attendance/');
    }

    private function download(?string $path, string $prefix)
    {
        abort_unless($path && str_starts_with($path, $prefix) && Storage::disk('private')->exists($path), 404);

        return Storage::disk('private')->download($path);
    }
}
