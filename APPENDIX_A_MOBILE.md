# Appendix A - Detailed Technical Documentation (Mobile App Version)

Project: **Emergency Communication System (Android Mobile App)**  
Scope: This document covers the Android app implementation. It is written so it can be merged later with the **website/admin platform** documentation into one consolidated appendix.

## 1. System Architecture

### Overview
The mobile app follows an MVVM + Repository architecture:

- **Presentation Layer**: Jetpack Compose screens and UI components.
- **State Layer**: ViewModels expose screen state and orchestrate use-cases.
- **Data Layer**: Repositories coordinate remote APIs (Retrofit) and local persistence (Room).
- **Realtime Layer**: Firebase Cloud Messaging (FCM), Socket.IO, and WebRTC for urgent communication paths.

### Major Components

- `ui/screens/*`: End-user screens (alerts, map, messaging, calls, profile, incident reports).
- `viewmodel/*`: UI-facing business orchestration.
- `data/repository/*`: Data access abstraction for each module.
- `network/*ApiService.kt`: REST endpoints.
- `data/local/*`: Room entities/DAOs/database.
- `services/MyFirebaseMessagingService.kt`: Push notification ingestion + token refresh.
- `socket/*` and `webrtc/*`: Realtime signaling and media session support.

### Responsibilities and Relationships

- Screens consume `StateFlow`/`Flow` from ViewModels.
- ViewModels call repositories for online/offline data.
- Repositories read/write Room and call backend APIs.
- Notification and call subsystems can trigger UI updates asynchronously.

## 2. Information Systems Integration

### Integrated Systems

- Android Mobile App (citizen-facing client).
- PHP API backend (`/PHP/api/`).
- Firebase (messaging + analytics).
- Google Sign-In.
- OpenWeather APIs.
- ML Kit Translation.
- Socket server for signaling.

### Integration and Data Flow

1. Mobile client authenticates user via backend auth endpoints.
2. App stores session/profile data locally.
3. Alerts, messages, and incident records are synchronized via REST APIs.
4. Push notifications arrive via FCM; token is synced to backend.
5. Internet call signaling uses Socket.IO; media handling uses WebRTC.

### Data Mapping

- API DTOs are parsed with Gson.
- Domain/network objects are transformed to Room entities for caching and offline continuity.

## 3. Application Design and Development

### Software Modules

- Authentication and profile management.
- Alert feed and acknowledgment.
- Incident reporting.
- Two-way messaging.
- Map and weather monitoring.
- Internet emergency call and call history.
- App settings (language, theme, notifications, accessibility tools).

### Code Structure

- Compose UI under `ui/`.
- ViewModels under `viewmodel/`.
- Repositories under `data/repository/`.
- Network clients and service contracts under `network/` and `data/network/`.
- Realtime features under `socket/` and `webrtc/`.

### Algorithms and Data Structures

- Reactive streams with Kotlin `Flow`/`StateFlow`.
- Indexed Room tables for efficient call/message retrieval.
- Offline cache strategy via Room + OkHttp cache.
- Connectivity-based fallback logic for backend selection (debug-configurable).

### Libraries and Frameworks

- Kotlin, AndroidX, Jetpack Compose, Navigation Compose.
- Retrofit, OkHttp, Gson.
- Room, Coroutines, DataStore.
- Firebase Messaging/Analytics.
- ML Kit Translate.
- osmdroid.
- Socket.IO client and WebRTC SDK.

## 4. Database Schema and Data Management

### Room Database

`AppDatabase` (version 7) includes:

- `alerts`
- `users`
- `call_logs`
- `call_messages`

### ERD (Text Form)

- `users (1) -> (many) call_logs`
- `call_logs (1) -> (many) call_messages`
- `alerts` is an independent cache-focused entity.

erDiagram
    USERS {
        int id PK
        string name
        string email
    }

    CALL_LOGS {
        int id PK
        int user_id FK
        string call_id
        long timestamp
    }

    CALL_MESSAGES {
        int id PK
        int call_log_id FK
        string sender
        string message
        long timestamp
    }

    ALERTS {
        int id PK
        string title
        string message
        string severity
    }

    USERS ||--o{ CALL_LOGS : has
    CALL_LOGS ||--o{ CALL_MESSAGES : contains
```

### Data Modeling and Normalization

- User, call log, and call message data are normalized into separate entities.
- Foreign keys and indices are used to maintain integrity and query performance.

### CRUD Coverage

- Full CRUD and query operations are implemented via DAOs:
  - `AlertDao`
  - `UserDao`
  - `CallLogDao`
  - `CallMessageDao`

## 5. Network Configuration

### Topology

- Mobile app -> HTTPS production API host.
- Optional debug-only local fallback for emulator/device LAN testing.
- Push channel through Firebase.
- Realtime signaling via Socket.IO endpoint.

### Security Measures

- Production API uses HTTPS.
- Network security config defines cleartext exceptions for development hosts.
- Release build disables local fallback by default.

### Protocols and Communication

- REST over HTTP/HTTPS (Retrofit).
- WebSocket/polling transport (Socket.IO).
- Push messaging protocol (FCM).

### Failover/Resilience

- Connectivity checks and fallback behavior in `ApiClient`.
- Response caching via OkHttp (10 MB) and Room local storage.

## 6. Deployment and Infrastructure

### Deployment Strategy

- Android APK deployment for testing and release distribution.
- Signed release APK generation via Gradle signing configuration.

### Build and Configuration Management

- `app/build.gradle.kts` handles build types, signing, and runtime constants.
- Sensitive values loaded from `local.properties`.
- Build and release process documented in `BUILD_INSTRUCTIONS.md` and `PRODUCTION_READY_GUIDE.md`.

### Scalability and Optimization

- Release minification and resource shrinking enabled.
- Caching reduces repeated network calls.
- Modular repository/services design eases feature growth.

## 7. Security Measures

### Security Protocols

- HTTPS for production backend communication.
- Firebase-based push token lifecycle.
- Build-time environment separation (debug vs release behavior).

### Authentication and Authorization

- Backend-authenticated login endpoints.
- Session/user state persisted through local secure app storage mechanisms.
- Google Sign-In integration with configured OAuth client IDs.

### Encryption and Data Protection

- TLS transport for production requests.
- Local storage scoped to app sandbox.

### Incident Response and Mitigation

- Production checklist in `PRODUCTION_READY_GUIDE.md`.
- Known hardening tasks tracked (secret rotation, backend enforcement items).

## 8. Testing and Quality Assurance

### Current Status

- Automated testing is currently minimal (baseline unit test in `ExampleUnitTest.kt`).
- Main verification approach is manual test execution and release checklist validation.

### Recommended Test Cases (for report table)

1. Login success/failure.
2. Alert fetch and acknowledgment synchronization.
3. Incident report submission and retrieval.
4. Messaging send/receive flow.
5. FCM notification delivery (notification + data payload).
6. Internet call setup/teardown and history persistence.
7. Offline cache behavior for alerts/weather.

### Validation Against Requirements

- Release readiness and stability checks are documented in `PRODUCTION_READY_GUIDE.md`.

### Performance QA

- No dedicated benchmark suite yet.
- Existing optimizations validated through release tests and runtime behavior.

## 9. System Monitoring and Maintenance

### Monitoring Tools and Techniques

- Android logging with custom filtering utility (`LogFilter`).
- Runtime event monitoring in FCM and API initialization flows.

### Logging and Error Handling

- Exception handling around key network and FCM flows.
- Error logs for token upload failures and connectivity problems.

### Scheduled Maintenance

- Manual maintenance and release cycle via documented build pipeline.

### Backup and Recovery

- Android backup/data extraction config files exist and can be expanded for production policy.

## 10. APIs and Integration Points

### API Services (Mobile)

- `AuthApiService`
- `AlertsApiService`
- `IncidentApiService`
- `MessagingApiService`
- `CallApiService`
- `SettingsApiService`

### Endpoint Patterns

- Auth: register/login/logout/profile updates.
- Alerts: list, acknowledge/unacknowledge, poll interactions.
- Messaging: create conversation, send, list.
- Incidents: report and retrieve user reports.
- Calls: call events/history.
- Settings: FCM token, location, subscription preferences.

### External Interaction

- Mobile app exchanges JSON payloads with backend APIs.
- Backend/admin panel is expected to originate alerts/polls that the app consumes.

## 11. User Documentation

### Existing Documents

- `README.md`
- `BUILD_INSTRUCTIONS.md`
- `PRODUCTION_READY_GUIDE.md`

### User Guide Coverage Needed for Final Combined Docs

- App onboarding and login.
- Alert acknowledgment and incident reporting flow.
- Messaging and emergency internet call usage.
- Language/theme/notification settings.
- Troubleshooting and FAQ.

## 12. Known Issues and Troubleshooting

### Known Issues (Current)

- Backend-triggered push dispatch is marked with limitations in readiness guide.
- Poll endpoint currently has partial/placeholder behavior (`poll: null` case noted in guide).
- Security hardening tasks remain (credential/key rotation and secret handling improvements).

### Troubleshooting Steps

1. Verify release signing keys and `local.properties` values.
2. Confirm Google/Firebase config and SHA fingerprints.
3. Validate production endpoint reachability and fallback mode.
4. Check FCM token upload and backend send flow.

### Technical Support

- Add project/team contact details in the final combined documentation package.

## 13. Version Control and Source Code Repository

### Version Control

- Git-based source control.

### Repository and Access

- Remote: `https://github.com/AbysmalFox/EmergencyCommunicationSystem.git`
- Active default branch observed: `master` (tracks `origin/master`).

### Branching and Merging

- Current repository indicates a simple branch model.
- Recommended for combined project: `master/main` stable + `feature/*` branches + pull request review.

## 14. DevOps and CI/CD

### Current State

- Build/deploy automation is Gradle-driven, documented in project guides.
- Release signing and build validation are enforced via configuration checks.

### Scripts and Tools

- Gradle wrapper (`gradlew`, `gradlew.bat`).
- Android Studio build/sign pipelines.

### Deployment and Rollback

- APK distribution workflow documented.
- Rollback handled by redeploying previously stable signed APK.

## 15. Licensing and Open Source Libraries

### Open Source Usage

Project depends on multiple OSS components including:

- AndroidX / Jetpack libraries
- Retrofit / OkHttp / Gson
- Room / Coroutines / DataStore
- Firebase SDKs
- ML Kit
- osmdroid
- Socket.IO client
- WebRTC SDK
- Coil

### Licensing Documentation Requirement

- Final combined documentation should include:
  - library name
  - version
  - license type
  - attribution link

## 16. Performance Metrics and Monitoring

### Metrics Collected/Derivable

- Call counts and total call duration (from `CallLogDao` queries).
- Message counts and unread state.
- API connectivity and fallback behavior logs.
- Cache usage patterns (Room + OkHttp).

### Tools and Dashboards

- Current monitoring is log/query-based.
- No external dashboard stack is integrated yet (e.g., Crashlytics dashboard can be added later).

### Actions Taken

- Release optimizations: minify + shrink resources.
- Network resilience: caching and fallback behavior.
- Production safety: release backend restrictions.

---

## Merge Notes for Final Combined (Mobile + Admin Website) Appendix

When integrating this file with admin/website documentation:

1. Keep this file as **mobile subsection** (for example, `A.1 Mobile Client`).
2. Add corresponding **Admin Web subsection** with backend control panel architecture.
3. Add a unified cross-platform diagram showing:
   - Admin Web -> Backend -> Mobile App
   - FCM push flow from backend/admin actions to mobile devices
4. Consolidate shared sections (Security, CI/CD, Licensing, Monitoring) into one platform-wide version.

---

## A.2 Web-Based Platform (Admin + User Portals)

Project: **Emergency Communication System (Web-Based Platform)**  
Scope: This subsection documents the PHP web modules for both **Admin** and **User** portals, including shared backend API patterns and realtime behavior.

### 1. Web Architecture

#### Overview
The web platform is a PHP + MySQL system with role-separated interfaces:

- **Admin Portal** (`ADMIN/`): operational control center for alerts, dispatch, approvals, analytics, and communication.
- **User Portal** (`USERS/`): citizen-facing interface for alerts, weather/earthquake monitoring, messaging, and emergency call actions.
- **Backend API Layer** (`ADMIN/api/`, `USERS/api/`, `PHP/api/`): JSON endpoints for web clients and mobile integration.
- **Realtime Layer** (`server.js`): Socket.IO signaling for internet call and live messaging events.

#### Major Components

- Admin UI pages: `ADMIN/sidebar/*.php`, shared layout in `ADMIN/sidebar/includes/*`.
- User UI pages: `USERS/*.php`, shared layout in `USERS/includes/*`.
- Admin APIs: `ADMIN/api/*.php` (alerts, approvals, weather, dashboard, chat, analytics, secure API config).
- User APIs: `USERS/api/*.php` (alerts, chat, login, profile, language, OAuth, call logs).
- Mobile-compatible APIs: `PHP/api/*.php` (auth, alerts, conversations, call events/history, FCM token, subscriptions).

### 2. Admin Portal Functional Coverage

#### Core Modules

- Dashboard and operational analytics (`ADMIN/sidebar/dashboard.php`).
- Mass notification and alert categorization workflows.
- Automated warnings and AI-assisted warning generation.
- Two-way communication queue for citizen conversations.
- Earthquake and weather monitoring modules.
- Citizen subscription management.
- Audit trail and login/activity monitoring.
- Admin approvals and role-restricted actions.

#### Data and Workflow Responsibilities

- Admin actions create/dispatch alert records consumed by web users and mobile clients.
- Dashboard cards aggregate subscriber volume, delivery trends, and unresolved communication queues.
- Communication modules coordinate with call and chat APIs to support incident response escalation.

### 3. User Portal Functional Coverage

#### Core Modules

- Account login and profile pages.
- Alert feed and acknowledgment pages (`USERS/alerts.php`, related API endpoints).
- Emergency internet call UI (`USERS/emergency-call.php`) with message exchange.
- Weather and earthquake monitoring views.
- Multilingual support and language preference synchronization.
- Policy/support pages (privacy, terms, support, data deletion status).

#### UX and Interaction Model

- Citizen pages use shared header/sidebar components and API-driven live content blocks.
- Alert and communication actions are submitted through JSON APIs in `USERS/api/`.
- Language features use frontend translation scripts and preference APIs.

### 4. Shared Backend and Integration

#### Database Connectivity

- Admin and User web APIs use secure config loaders (`config.env.php`) and support primary/fallback DB connection attempts.
- Mobile-oriented API connection now reads env-based credentials (`PHP/api/db_connect.php` with `.env` loading and fallback profile).

#### External Services

- Firebase Cloud Messaging via PHP helper for push notifications.
- Google OAuth endpoints for web login variants.
- Weather providers and AI integrations through admin-side configuration and service APIs.
- Socket.IO signaling server (`server.js`) for internet call setup/relay.

### 5. Security and Configuration

#### Implemented Practices

- Error details suppressed from clients and logged server-side.
- Sensitive keys intended to be loaded from environment/config files (not hardcoded in committed code).
- `.env` patterns and local config files excluded via `.gitignore`.
- Session checks guard admin pages before rendering secured modules.

#### Hardening Priorities

- Continue secret rotation (DB, SMTP, token secrets) and periodic credential renewal.
- Keep direct API key entry via URL/query disabled in production workflows.
- Maintain role checks and audit logging for admin-only operations.

### 6. Realtime Communication (Web)

#### Call and Chat Flow

- User side starts internet call from `USERS/emergency-call.php`.
- Admin receives and manages calls in `ADMIN/sidebar/two-way-communication.php`.
- Signaling and message relay occur through Socket.IO events handled by `server.js`.
- Call lifecycle metadata persists through `USERS/api/call-log.php`, `ADMIN/api/call-log.php`, and `ADMIN/api/save-completed-call.php`.

### 7. API Surface (Web Context)

#### Admin API Domains

- Dashboard/analytics, alert generation/dispatch, approvals, user management, audit logs, chat queue, AI/weather integrations.

#### User API Domains

- Auth/session, alerts, chat/conversations, language preferences, OAuth, device/location updates, call logs.

#### Cross-Platform API Domains (`PHP/api`)

- Mobile/web shared auth, alert retrieval and acknowledgment, messaging list/send, profile/settings, FCM token, subscription settings.

### 8. Monitoring and Maintenance (Web)

- Operational logs generated in API and realtime layers.
- Health verification for socket service via `check-socket-server.php`.
- Maintenance includes API key management, schema checks, and deployment validation of both PHP and Node signaling services.

### 9. Documentation Merge Guidance

For the final consolidated appendix:

1. Keep **A.1 Mobile Client** and **A.2 Web-Based Platform** as separate implementation subsections.
2. Add a shared architecture diagram with explicit role split:
   - `Admin Web (ADMIN/*) -> PHP APIs -> MySQL`
   - `User Web (USERS/*) -> PHP APIs -> MySQL`
   - `Mobile App -> PHP/api/* -> MySQL`
   - `Admin-triggered alerts -> FCM -> Mobile`
   - `User/Admin internet call -> Socket.IO/WebRTC`
3. Consolidate shared controls (security, monitoring, DevOps, licensing) into one project-wide section to avoid duplication.
