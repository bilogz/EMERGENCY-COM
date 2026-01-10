# APK Update Summary

## ✅ Configuration Updates Completed

### Changes Made:

1. **NetworkConfig.kt** - Updated Production URL
   - **File**: `App-Emer-comms/Emergency_Comms_App/EmergencyCommunicationSystem-master/app/src/main/java/com/example/emergencycommunicationsystem/data/network/NetworkConfig.kt`
   - **Old URL**: `https://www.your-live-domain.com`
   - **New URL**: `https://emergency-comm.alertaraqc.com`
   - **API Path**: `/EMERGENCY-COM/USERS/api/` (already configured correctly)

2. **build.gradle.kts** - Updated Version Numbers
   - **Version Code**: 2 → 3
   - **Version Name**: 1.0.1 → 1.0.2

### Production API Endpoint:
The app will now connect to:
```
https://emergency-comm.alertaraqc.com/EMERGENCY-COM/USERS/api/
```

## 📱 Building the Updated APK

### Prerequisites:
- **Java JDK 11 or higher** (required)
- **Android SDK** (Android Studio includes this)
- **Gradle** (included in project)

### Option 1: Build with Android Studio (Recommended)

1. **Open Project in Android Studio**
   - Open: `c:\xampp\htdocs\App-Emer-comms\Emergency_Comms_App\EmergencyCommunicationSystem-master`
   - Wait for Gradle sync to complete

2. **Build Release APK**
   - Go to: **Build** → **Build Bundle(s) / APK(s)** → **Build APK(s)**
   - Or: **Build** → **Generate Signed Bundle / APK** → **APK** → **Next** → **release** → **Finish**

3. **APK Location**
   - Find APK at: `app/build/outputs/apk/release/app-release.apk`
   - Copy to: `EMERGENCY-COM/USERS/downloads/emergency-comms-app.apk`

### Option 2: Build with Command Line

If you have Java and Android SDK installed:

1. **Set Environment Variables** (if not already set):
   ```powershell
   $env:JAVA_HOME = "C:\Program Files\Java\jdk-11"
   $env:ANDROID_HOME = "C:\Users\YourName\AppData\Local\Android\Sdk"
   ```

2. **Build APK**:
   ```powershell
   cd "c:\xampp\htdocs\App-Emer-comms\Emergency_Comms_App\EmergencyCommunicationSystem-master"
   .\gradlew.bat assembleRelease
   ```

3. **Copy APK**:
   ```powershell
   Copy-Item "app\build\outputs\apk\release\app-release.apk" -Destination "..\..\EMERGENCY-COM\USERS\downloads\emergency-comms-app.apk" -Force
   ```

### Option 3: Install Java JDK First

If Java is not installed:

1. **Download Java JDK 11 or higher**:
   - Visit: https://adoptium.net/ (OpenJDK) or https://www.oracle.com/java/technologies/downloads/
   - Download and install JDK 11 or higher

2. **Set JAVA_HOME**:
   ```powershell
   # In PowerShell (as Administrator):
   [Environment]::SetEnvironmentVariable("JAVA_HOME", "C:\Program Files\Java\jdk-11", "Machine")
   ```

3. **Add to PATH**:
   - Add `%JAVA_HOME%\bin` to System PATH environment variable

4. **Then follow Option 2** to build

## 📤 Uploading to Production Server

After building the APK, upload it to your server:

1. **Copy APK to downloads folder** (if building locally):
   ```powershell
   Copy-Item "app\build\outputs\apk\release\app-release.apk" -Destination "c:\xampp\htdocs\EMERGENCY-COM\USERS\downloads\emergency-comms-app.apk" -Force
   ```

2. **Upload to server** (see `UPLOAD_APK_INSTRUCTIONS.md` for details):
   - Use FileZilla, cPanel, or WinSCP
   - Upload to: `/EMERGENCY-COM/USERS/downloads/emergency-comms-app.apk`

3. **Verify**:
   - Visit: https://emergency-comm.alertaraqc.com/index.php
   - Click "Download APK" button
   - Install on device and verify it connects to the production server

## ✅ Verification Checklist

After building and uploading:

- [ ] APK builds successfully
- [ ] APK version shows as 1.0.2
- [ ] App connects to `https://emergency-comm.alertaraqc.com`
- [ ] API calls work correctly (login, alerts, etc.)
- [ ] Download link on website works
- [ ] APK installs on Android device without errors

## 📝 Notes

- The app is configured to use the production server by default
- Version code 3 and version name 1.0.2 will help identify the updated build
- All API endpoints will point to the production domain after this update
- Local development mode will still work for debugging (uses local IP)

---

**Last Updated**: Configuration files updated with production URL
**Next Step**: Build the APK using one of the methods above


