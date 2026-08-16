# I Volunteer Flutter app

This folder contains the native cross-platform mobile client for the I Volunteer platform.

## What it does

- Volunteer login with the existing backend account
- Browse activities suitable for the volunteer
- Filter by city or search text
- Open full activity details
- Apply to an activity or withdraw from it
- View your submitted and approved activities
- See your profile and summary stats

## Backend API

The app uses the JSON routes exposed by the PHP backend:

- `POST /mobile/login`
- `GET /mobile/me`
- `POST /mobile/refresh`
- `POST /mobile/logout`
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
flutter run --dart-define=API_BASE_URL=https://portal.i-volunteer.ly
```

4. If you are testing against a local PHP server, replace the URL with your computer's LAN IP, for example `http://192.168.1.20:8080`.
5. For an Android emulator, use `http://10.0.2.2:8080` instead of `localhost`.
6. For iPhone testing over HTTP on a local machine, you may need an App Transport Security exception unless you use HTTPS.
7. You can also change the API URL from inside the app through the `API settings` button on the login or profile screen.

## GitHub Builds

You can also build release files from GitHub Actions:

- Android APK workflow: [`.github/workflows/android-apk.yml`](/mnt/c/Users/porta/Desktop/PROGETTI/I-volunteer/.github/workflows/android-apk.yml)
- iOS sideload IPA workflow: [`.github/workflows/ios-ipa.yml`](/mnt/c/Users/porta/Desktop/PROGETTI/I-volunteer/.github/workflows/ios-ipa.yml)

Android can build immediately.

iOS is configured to produce an unsigned IPA for tools like Sideloadly, so it does not need Apple signing secrets in GitHub.

## Sideloadly

If you want to install the iPhone build from a file:

1. Download the IPA artifact from GitHub Actions.
2. Open Sideloadly on your Mac or Windows PC.
3. Connect your iPhone with USB.
4. Drag the IPA into Sideloadly.
5. Sign in with your Apple ID and install.

If you want, I can also add push notifications, biometric login, or a branded splash screen next.
