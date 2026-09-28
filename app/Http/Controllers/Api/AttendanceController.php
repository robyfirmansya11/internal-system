<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\OfficeLocation;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class AttendanceController extends Controller
{
    /**
     * Today's attendance status for the logged-in user.
     */
    public function today(Request $request)
    {
        $attendance = Attendance::where('user_id', $request->user()->id)
            ->where('date', today())
            ->first();

        return response()->json([
            'date' => today()->toDateString(),
            'has_clocked_in' => (bool) $attendance?->clock_in,
            'has_clocked_out' => (bool) $attendance?->clock_out,
            'clock_in' => $attendance?->clock_in?->format('H:i'),
            'clock_out' => $attendance?->clock_out?->format('H:i'),
            'status' => $attendance?->status,
            'note' => $attendance?->note,
            'is_outside_radius' => (bool) $attendance?->is_outside_radius,
            'clock_in_location_reason' => $attendance?->clock_in_location_reason,
            'clock_out_location_reason' => $attendance?->clock_out_location_reason,
            'clock_in_office_location' => $this->officeSummary($attendance?->clockInOfficeLocation),
            'clock_out_office_location' => $this->officeSummary($attendance?->clockOutOfficeLocation),
            'clock_in_photo' => $attendance?->clock_in_photo
                ? route('api.attendance.photo', [$attendance, 'clock_in_photo'])
                : null,
        ]);
    }

    /**
     * Clock In — submit selfie photo + GPS coordinates.
     * Validates office radius before saving.
     */
    public function clockIn(Request $request)
    {
        $request->validate([
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
            'gps_accuracy' => 'required|numeric|min:0|max:50',
            'device_id' => 'required|string|max:255',
            'is_mock_location' => 'nullable|boolean',
            'device_platform' => 'nullable|in:android,ios',
            'integrity_provider' => 'nullable|in:play_integrity,app_attest',
            'device_integrity_status' => 'nullable|in:verified,unverified,failed,compromised',
            'integrity_token' => 'nullable|string|max:10000',
            'is_rooted' => 'nullable|boolean',
            'is_emulator' => 'nullable|boolean',
            'photo' => 'required|image|max:2048',
            'address' => 'nullable|string',
            'reason' => 'nullable|string|max:500',
            'location_reason' => 'nullable|string|max:500', // ← alasan luar radius
        ]);

        $user = $request->user();

        if ($response = $this->rejectCompromisedDevice($request)) return $response;

        $existing = Attendance::where('user_id', $user->id)
            ->where('date', today())
            ->first();

        if ($existing?->hasClockedIn()) {
            return response()->json([
                'message' => 'Anda sudah melakukan clock in hari ini.',
            ], 422);
        }

        // Check every active site and use the closest one for the attendance audit.
        $officeMatch = $this->nearestActiveOffice((float) $request->latitude, (float) $request->longitude);
        $office = $officeMatch['office'];
        $distance = $officeMatch['distance'];
        $isOutsideRadius = $office !== null && $distance > $office->radius;

        if ($isOutsideRadius) {

            // Wajib isi alasan kalau di luar radius
            if (empty($request->location_reason)) {
                return response()->json([
                    'message' => "Anda berada di luar radius kantor ({$distance}m dari kantor, maksimal {$office->radius}m). Harap isi alasan.",
                    'is_outside_radius' => true,
                    'distance' => $distance,
                ], 422);
            }
        }

        $now = Carbon::now();
        $lateThreshold = Carbon::today()->setTime(8, 30, 0);
        $isLate = $now->gt($lateThreshold);
        $status = $isLate ? 'late' : 'present';

        if ($isLate && empty($request->reason)) {
            return response()->json([
                'message' => 'Anda terlambat. Harap isi alasan keterlambatan.',
                'is_late' => true,
            ], 422);
        }

        $photoPath = $request->file('photo')->store('attendance/clock-in', 'private');

        $menit = $isLate ? $now->diffInMinutes($lateThreshold) : 0;

        $note = $isLate
            ? "Terlambat {$menit} menit. Alasan: {$request->reason}"
            : null;

        $attendance = Attendance::updateOrCreate(
            ['user_id' => $user->id, 'date' => today()],
            [
                'clock_in' => $now,
                'clock_in_lat' => $request->latitude,
                'clock_in_lng' => $request->longitude,
                'clock_in_accuracy' => $request->gps_accuracy,
                'clock_in_photo' => $photoPath,
                'clock_in_address' => $request->address,
                'clock_in_office_location_id' => $isOutsideRadius ? null : $office?->id,
                'clock_in_location_reason' => $isOutsideRadius ? $request->location_reason : null,
                'is_outside_radius' => $isOutsideRadius,
                'status' => $status,
                'note' => $note,
                'device_id' => $request->device_id,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                ...$this->deviceIntegrityData($request),
            ]
        );

        return response()->json([
            'message' => 'Clock in berhasil.',
            'clock_in' => $attendance->clock_in->format('H:i'),
            'status' => $attendance->status,
            'is_late' => $isLate,
            'is_outside_radius' => $isOutsideRadius,
            'office_location' => $this->officeSummary($isOutsideRadius ? null : $office, $distance),
            'note' => $attendance->note,
        ]);
    }

    public function clockOut(Request $request)
    {
        $request->validate([
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
            'gps_accuracy' => 'required|numeric|min:0|max:50',
            'device_id' => 'required|string|max:255',
            'is_mock_location' => 'nullable|boolean',
            'device_platform' => 'nullable|in:android,ios',
            'integrity_provider' => 'nullable|in:play_integrity,app_attest',
            'device_integrity_status' => 'nullable|in:verified,unverified,failed,compromised',
            'integrity_token' => 'nullable|string|max:10000',
            'is_rooted' => 'nullable|boolean',
            'is_emulator' => 'nullable|boolean',
            'photo' => 'required|image|max:2048',
            'address' => 'nullable|string',
            'reason' => 'nullable|string|max:500',
            'location_reason' => 'nullable|string|max:500',
        ]);

        $user = $request->user();

        if ($response = $this->rejectCompromisedDevice($request)) return $response;

        $attendance = Attendance::where('user_id', $user->id)
            ->where('date', today())
            ->first();

        if (! $attendance?->hasClockedIn()) {
            return response()->json([
                'message' => 'Anda belum melakukan clock in hari ini.',
            ], 422);
        }

        if ($attendance->hasClockedOut()) {
            return response()->json([
                'message' => 'Anda sudah melakukan clock out hari ini.',
            ], 422);
        }

        // The employee may clock out at a different active site from clock-in.
        $officeMatch = $this->nearestActiveOffice((float) $request->latitude, (float) $request->longitude);
        $office = $officeMatch['office'];
        $distance = $officeMatch['distance'];
        $isOutsideRadius = $office !== null && $distance > $office->radius;

        if ($isOutsideRadius) {

            if (empty($request->location_reason)) {
                return response()->json([
                    'message' => "Anda berada di luar radius kantor ({$distance}m dari kantor, maksimal {$office->radius}m). Harap isi alasan.",
                    'is_outside_radius' => true,
                    'distance' => $distance,
                ], 422);
            }
        }

        $now = Carbon::now();
        $checkoutTime = Carbon::today()->setTime(17, 0, 0);
        $isEarlyLeave = $now->lt($checkoutTime);

        if ($isEarlyLeave && empty($request->reason)) {
            return response()->json([
                'message' => 'Anda pulang lebih awal dari jam 17:00. Harap isi alasan.',
                'is_early_leave' => true,
            ], 422);
        }

        $photoPath = $request->file('photo')->store('attendance/clock-out', 'private');

        $attendance->update([
            'clock_out' => $now,
            'clock_out_lat' => $request->latitude,
            'clock_out_lng' => $request->longitude,
            'clock_out_accuracy' => $request->gps_accuracy,
            'clock_out_photo' => $photoPath,
            'clock_out_address' => $request->address,
            'clock_out_office_location_id' => $isOutsideRadius ? null : $office?->id,
            'clock_out_location_reason' => $isOutsideRadius ? $request->location_reason : null,
            'is_outside_radius' => $attendance->is_outside_radius || $isOutsideRadius,
            'note' => $isEarlyLeave
                ? ($attendance->note
                    ? $attendance->note." | Pulang lebih awal. Alasan: {$request->reason}"
                    : "Pulang lebih awal. Alasan: {$request->reason}")
                : $attendance->note,
            'device_id' => $request->device_id ?? $attendance->device_id,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            ...$this->deviceIntegrityData($request),
        ]);

        return response()->json([
            'message' => 'Clock out berhasil.',
            'clock_out' => $attendance->fresh()->clock_out->format('H:i'),
            'is_early_leave' => $isEarlyLeave,
            'is_outside_radius' => $isOutsideRadius,
            'office_location' => $this->officeSummary($isOutsideRadius ? null : $office, $distance),
        ]);
    }

    /**
     * Attendance history for the logged-in user (paginated).
     */
    public function history(Request $request)
    {
        $attendances = Attendance::with(['clockInOfficeLocation', 'clockOutOfficeLocation'])
            ->where('user_id', $request->user()->id)
            ->orderByDesc('date')
            ->paginate(20);

        return response()->json(
            $attendances->through(function ($a) {
                return [
                    'id' => $a->id,
                    'date' => $a->date->format('Y-m-d'),
                    'formatted_date' => $a->date->format('d M Y'),
                    'status' => $a->status,
                    'clock_in' => optional($a->clock_in)->format('H:i'),
                    'clock_out' => optional($a->clock_out)->format('H:i'),
                    'clock_in_photo' => $a->clock_in_photo
                        ? route('api.attendance.photo', [$a, 'clock_in_photo'])
                        : null,
                    'clock_out_photo' => $a->clock_out_photo
                        ? route('api.attendance.photo', [$a, 'clock_out_photo'])
                        : null,
                    'clock_in_address' => $a->clock_in_address,
                    'clock_out_address' => $a->clock_out_address,
                    'note' => $a->note,
                    'is_outside_radius' => (bool) $a->is_outside_radius, // ← tambah
                    'clock_in_location_reason' => $a->clock_in_location_reason, // ← tambah
                    'clock_out_location_reason' => $a->clock_out_location_reason, // ← tambah
                    'clock_in_office_location' => $this->officeSummary($a->clockInOfficeLocation),
                    'clock_out_office_location' => $this->officeSummary($a->clockOutOfficeLocation),
                ];
            })
        );
    }

    /**
     * Active office locations for Flutter. Top-level fields retain the old
     * single-location response shape for a gradual mobile-app upgrade.
     */
    public function officeLocation()
    {
        $offices = OfficeLocation::where('is_active', true)->orderBy('id')->get();

        if ($offices->isEmpty()) {
            return response()->json([
                'message' => 'Office location has not been configured.',
            ], 404);
        }

        $primary = $offices->first();

        return response()->json([
            'name' => $primary->name,
            'latitude' => (float) $primary->latitude,
            'longitude' => (float) $primary->longitude,
            'radius' => $primary->radius,
            'locations' => $offices->map(fn (OfficeLocation $office) => $this->officeSummary($office))->values(),
        ]);
    }

    /**
     * Streams a private attendance photo to an authenticated mobile client.
     * Flutter must send the same Bearer token used for its other API calls.
     */
    public function photo(Request $request, Attendance $attendance, string $field)
    {
        abort_unless(in_array($field, ['clock_in_photo', 'clock_out_photo'], true), 404);

        $viewer = $request->user();
        abort_unless(
            $viewer->id === $attendance->user_id || $viewer->isAdmin() || $viewer->isSuperadmin(),
            403,
        );

        $path = $attendance->{$field};
        abort_unless($path && str_starts_with($path, 'attendance/') && Storage::disk('private')->exists($path), 404);

        return Storage::disk('private')->response($path, null, [
            'Cache-Control' => 'private, no-store',
        ]);
    }

    /** @return array{office: ?OfficeLocation, distance: ?int} */
    private function nearestActiveOffice(float $latitude, float $longitude): array
    {
        $match = OfficeLocation::where('is_active', true)
            ->get()
            ->map(fn (OfficeLocation $office): array => [
                'office' => $office,
                'distance' => (int) round($office->distanceFrom($latitude, $longitude)),
            ])
            ->sortBy('distance')
            ->first();

        return $match ?? ['office' => null, 'distance' => null];
    }

    private function officeSummary(?OfficeLocation $office, ?int $distance = null): ?array
    {
        if (! $office) {
            return null;
        }

        return array_filter([
            'id' => $office->id,
            'name' => $office->name,
            'latitude' => (float) $office->latitude,
            'longitude' => (float) $office->longitude,
            'radius' => $office->radius,
            'distance' => $distance,
        ], fn (mixed $value): bool => $value !== null);
    }

    /**
     * These values are supplied by the mobile security checks. A server cannot
     * reliably detect a rooted device by itself, so a failed client check is
     * rejected and the attestation evidence is retained as an audit hash.
     */
    private function rejectCompromisedDevice(Request $request): ?\Illuminate\Http\JsonResponse
    {
        if ($request->boolean('is_mock_location')) {
            return response()->json(['message' => 'Mock location is not allowed for attendance.'], 422);
        }

        if ($request->boolean('is_rooted') || $request->boolean('is_emulator') || in_array($request->input('device_integrity_status'), ['failed', 'compromised'], true)) {
            return response()->json(['message' => 'Compromised or emulator devices are not allowed for attendance.'], 422);
        }

        return null;
    }

    private function deviceIntegrityData(Request $request): array
    {
        $hasIntegrityData = $request->filled('device_integrity_status') || $request->filled('integrity_token');

        return [
            'device_platform' => $request->input('device_platform'),
            'integrity_provider' => $request->input('integrity_provider'),
            'device_integrity_status' => $request->input('device_integrity_status', 'not_provided'),
            'device_integrity_token_hash' => $request->filled('integrity_token')
                ? hash('sha256', $request->input('integrity_token'))
                : null,
            'device_integrity_checked_at' => $hasIntegrityData ? now() : null,
        ];
    }
}
