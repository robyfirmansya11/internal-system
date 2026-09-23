# Mobile Attendance Integrity Contract

The attendance API records device-integrity evidence for clock-in and clock-out without storing the raw attestation token.

## Request fields

Send these optional fields with both `POST /api/v1/attendance/clock-in` and `POST /api/v1/attendance/clock-out`:

```json
{
  "device_platform": "android",
  "integrity_provider": "play_integrity",
  "device_integrity_status": "verified",
  "integrity_token": "provider-issued-attestation-token",
  "is_rooted": false,
  "is_emulator": false,
  "is_mock_location": false
}
```

Allowed values:

- `device_platform`: `android` or `ios`
- `integrity_provider`: `play_integrity` or `app_attest`
- `device_integrity_status`: `verified`, `unverified`, `failed`, or `compromised`

The API rejects attendance from mock-location, rooted, emulator, failed, or compromised devices. It stores only a SHA-256 hash of `integrity_token`, together with the provider, status, platform, and timestamp.

## Android

Use Google Play Integrity Standard API immediately before sending attendance. Send the returned token as `integrity_token`; report root/emulator/mock-location checks from the device as boolean fields.

## iOS

Use Apple App Attest for a device-bound assertion. Send `app_attest` as the provider and the assertion token as `integrity_token`.

## Important limitation

This server currently rejects a failed device-side check and preserves evidence for audit. Cryptographic verification of a Google or Apple token requires provider credentials, a verifier endpoint, and mobile application changes. Do not mark this as fully enforced until those credentials are configured and the token is verified server-side.
