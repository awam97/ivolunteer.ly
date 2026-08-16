# Device Testing Guide

## Backend URL

Set the app to point at your backend machine on the local network.

Examples:

- Android emulator: `http://10.0.2.2:8080`
- Android phone on Wi-Fi: `http://192.168.1.20:8080`
- iPhone on Wi-Fi: `http://192.168.1.20:8080`

Replace `192.168.1.20` with the real IP address of the computer running the PHP app.

## Android

1. Install Flutter and Android Studio.
2. Open `mobile/` in a Flutter terminal.
3. Run `flutter pub get`.
4. If needed, run `flutter create .` to generate the full Android shell.
5. Start the app with:

```bash
flutter run --dart-define=API_BASE_URL=http://192.168.1.20:8080
```

6. Use a physical Android phone with USB debugging or an Android emulator on the same network.

## iPhone

1. You need a Mac with Xcode installed to build and run the iOS app locally.
2. Run `flutter pub get` inside `mobile/`.
3. Run `flutter create .` if the iOS shell folders are not present.
4. Start with:

```bash
flutter run --dart-define=API_BASE_URL=http://192.168.1.20:8080
```

5. For local HTTP testing, iOS may require an App Transport Security exception in `Info.plist` unless you use HTTPS.
6. If you want to install from a file instead of Xcode, download the GitHub Actions IPA artifact and open it in Sideloadly.

## Backend Checklist

- The phone and backend computer must be on the same Wi-Fi network.
- The backend firewall must allow incoming requests on the PHP server port.
- If you test from a phone, do not use `localhost` in the API URL.
- If you test from an Android emulator, use `10.0.2.2` instead of `localhost`.
