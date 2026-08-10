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
            'clock_in_photo' => $attendance?->clock_in_photo
                ? asset('storage/'.$attendance->clock_in_photo)
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
            'photo' => 'required|image|max:2048',
            'address' => 'nullable|string',
            'reason' => 'nullable|string|max:500', // ← tambah
        ]);

        $user = $request->user();

        $existing = Attendance::where('user_id', $user->id)
            ->where('date', today())
            ->first();

        if ($existing?->hasClockedIn()) {
            return response()->json([
                'message' => 'Anda sudah melakukan clock in hari ini.',
            ], 422);
        }

        $office = OfficeLocation::where('is_active', true)->first();

        if ($office && ! $office->isWithinRadius($request->latitude, $request->longitude)) {
            $distance = round($office->distanceFrom($request->latitude, $request->longitude));

            return response()->json([
                'message' => "Anda berada di luar radius kantor ({$distance}m dari kantor, maksimal {$office->radius}m).",
            ], 422);
        }

        $now = Carbon::now();
        $lateThreshold = Carbon::today()->setTime(8, 30, 0);
        $isLate = $now->gt($lateThreshold);
        $status = $isLate ? 'late' : 'present';

        // Kalau terlambat tapi tidak ada alasan → tolak
        if ($isLate && empty($request->reason)) {
            return response()->json([
                'message' => 'Anda terlambat. Harap isi alasan keterlambatan.',
                'is_late' => true,
            ], 422);
        }

        $photoPath = $request->file('photo')->store('attendance/clock-in', 'public');

        $menit = $isLate
            ? $now->diffInMinutes($lateThreshold)
            : 0;

        $attendance = Attendance::updateOrCreate(
            ['user_id' => $user->id, 'date' => today()],
            [
                'clock_in' => $now,
                'clock_in_lat' => $request->latitude,
                'clock_in_lng' => $request->longitude,
                'clock_in_photo' => $photoPath,
                'clock_in_address' => $request->address,
                'status' => $status,
                'note' => $isLate
                    ? "Terlambat {$menit} menit. Alasan: {$request->reason}"
                    : null,
            ]
        );

        return response()->json([
            'message' => 'Clock in berhasil.',
            'clock_in' => $attendance->clock_in->format('H:i'),
            'status' => $attendance->status,
            'is_late' => $isLate,
            'note' => $attendance->note,
        ]);
    }

    public function clockOut(Request $request)
    {
        $request->validate([
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
            'photo' => 'required|image|max:2048',
            'address' => 'nullable|string',
            'reason' => 'nullable|string|max:500', // ← tambah
        ]);

        $user = $request->user();

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

        $now = Carbon::now();
        $checkoutTime = Carbon::today()->setTime(17, 0, 0);
        $isEarlyLeave = $now->lt($checkoutTime);

        // Kalau pulang lebih awal tapi tidak ada alasan → tolak
        if ($isEarlyLeave && empty($request->reason)) {
            return response()->json([
                'message' => 'Anda pulang lebih awal dari jam 17:00. Harap isi alasan.',
                'is_early_leave' => true,
            ], 422);
        }

        $photoPath = $request->file('photo')->store('attendance/clock-out', 'public');

        $attendance->update([
            'clock_out' => $now,
            'clock_out_lat' => $request->latitude,
            'clock_out_lng' => $request->longitude,
            'clock_out_photo' => $photoPath,
            'clock_out_address' => $request->address,
            'note' => $isEarlyLeave
                ? ($attendance->note
                    ? $attendance->note." | Pulang lebih awal. Alasan: {$request->reason}"
                    : "Pulang lebih awal. Alasan: {$request->reason}")
                : $attendance->note,
        ]);

        return response()->json([
            'message' => 'Clock out berhasil.',
            'clock_out' => $attendance->fresh()->clock_out->format('H:i'),
            'is_early_leave' => $isEarlyLeave,
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
                        ? asset('storage/'.$a->clock_in_photo)
                        : null,

                    'clock_out_photo' => $a->clock_out_photo
                        ? asset('storage/'.$a->clock_out_photo)
                        : null,

                    'clock_in_address' => $a->clock_in_address,
                    'clock_out_address' => $a->clock_out_address,

                    'note' => $a->note,
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
}
