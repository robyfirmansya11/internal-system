<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\OfficeLocation;
use Carbon\Carbon;
use Illuminate\Http\Request;

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
            'clock_in_photo' => $attendance?->clock_in_photo
                ? route('private.attendance-photo', [$attendance, 'clock_in_photo'])
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

        // Cek radius — TIDAK block lagi, cuma flag & wajib alasan
        $office = OfficeLocation::where('is_active', true)->first();
        $isOutsideRadius = false;
        $distance = null;

        if ($office && ! $office->isWithinRadius($request->latitude, $request->longitude)) {
            $isOutsideRadius = true;
            $distance = round($office->distanceFrom($request->latitude, $request->longitude));

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

        // Cek radius untuk clock out juga
        $office = OfficeLocation::where('is_active', true)->first();
        $isOutsideRadius = false;
        $distance = null;

        if ($office && ! $office->isWithinRadius($request->latitude, $request->longitude)) {
            $isOutsideRadius = true;
            $distance = round($office->distanceFrom($request->latitude, $request->longitude));

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
        ]);
    }

    /**
     * Attendance history for the logged-in user (paginated).
     */
    public function history(Request $request)
    {
        $attendances = Attendance::where('user_id', $request->user()->id)
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
                        ? route('private.attendance-photo', [$a, 'clock_in_photo'])
                        : null,
                    'clock_out_photo' => $a->clock_out_photo
                        ? route('private.attendance-photo', [$a, 'clock_out_photo'])
                        : null,
                    'clock_in_address' => $a->clock_in_address,
                    'clock_out_address' => $a->clock_out_address,
                    'note' => $a->note,
                    'is_outside_radius' => (bool) $a->is_outside_radius, // ← tambah
                    'clock_in_location_reason' => $a->clock_in_location_reason, // ← tambah
                    'clock_out_location_reason' => $a->clock_out_location_reason, // ← tambah
                ];
            })
        );
    }

    /**
     * Active office location info (latitude, longitude, radius).
     * Used by the Flutter app to display the radius on the map.
     */
    public function officeLocation()
    {
        $office = OfficeLocation::where('is_active', true)->first();

        if (! $office) {
            return response()->json([
                'message' => 'Office location has not been configured.',
            ], 404);
        }

        return response()->json([
            'name' => $office->name,
            'latitude' => (float) $office->latitude,
            'longitude' => (float) $office->longitude,
            'radius' => $office->radius,
        ]);
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
