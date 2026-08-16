# I Volunteer Flutter app

This folder contains the native cross-platform mobile client for the I Volunteer platform.

## What it does

- Volunteer login with the existing backend account
- Browse suitable activities
- Filter by city or search text
- Open full activity details
- Apply to an activity or withdraw from it
- View your submitted and approved activities
- See your profile and summary stats

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

## Run on a phone

1. Open this folder in Flutter and run `flutter pub get`.
2. If the Android and iOS folders are missing, run `flutter create .` once inside `mobile/` to generate them.
3. Start the app with:

```bash
flutter run --dart-define=API_BASE_URL=http://192.168.1.20:8080
```

4. Use the real IP address of your backend machine when testing on a physical phone.
5. For an Android emulator, use `10.0.2.2` instead of `localhost`.

## GitHub Builds

You can also build release files from GitHub Actions:

- Android APK workflow: [`.github/workflows/android-apk.yml`](/mnt/c/Users/porta/Desktop/PROGETTI/I-volunteer/.github/workflows/android-apk.yml)
- iOS IPA workflow: [`.github/workflows/ios-ipa.yml`](/mnt/c/Users/porta/Desktop/PROGETTI/I-volunteer/.github/workflows/ios-ipa.yml)

Android can build immediately.

iOS requires these GitHub secrets before the workflow can produce a signed IPA:

- `IOS_P12_BASE64`
- `IOS_P12_PASSWORD`
- `IOS_KEYCHAIN_PASSWORD`
- `IOS_MOBILEPROVISION_BASE64`
- `IOS_TEAM_ID`
- `IOS_BUNDLE_ID`
- `IOS_PROFILE_NAME`
