# I Volunteer

This repository now includes a native Flutter mobile client in `mobile/` for Android and iOS.

## What the mobile app does

- Volunteer login with the existing backend account
- Browse activities that match the volunteer
- Filter by city or search text
- Open full activity details
- Apply to an activity or withdraw from it
- View submitted and approved activities
- See profile details and summary stats

## Backend API

The app uses these JSON routes exposed by the PHP backend:

- `POST /mobile/login`
- `GET /mobile/me`
- `GET /mobile/cities`
- `GET /mobile/activities`
- `GET /mobile/activities/:id`
- `GET /mobile/my-activities`
- `POST /mobile/activities/enroll`
- `POST /mobile/activities/unenroll`

## Test on a phone

1. Run the PHP backend on a machine reachable from your phone on the same Wi-Fi network.
2. Open `mobile/` in Flutter and run `flutter pub get`.
3. Start the app with `flutter run --dart-define=API_BASE_URL=http://192.168.1.20:8080`.
4. Use the real IP address of your backend machine when testing on a physical phone.
5. Use `10.0.2.2` for an Android emulator.

For a more detailed device checklist, see `mobile/DEVICE_TESTING.md`.

## GitHub builds

Release build workflows are in:

- `.github/workflows/android-apk.yml`
- `.github/workflows/ios-ipa.yml`

Android can build directly from GitHub Actions.

iOS requires signing secrets before the workflow can produce a signed IPA:

- `IOS_P12_BASE64`
- `IOS_P12_PASSWORD`
- `IOS_KEYCHAIN_PASSWORD`
- `IOS_MOBILEPROVISION_BASE64`
- `IOS_TEAM_ID`
- `IOS_BUNDLE_ID`
- `IOS_PROFILE_NAME`
