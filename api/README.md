# Centralized Department REST API Documentation

This directory contains the centralized gateway URL allowing other departments (e.g., Health, Fire, Police, or generic services) to securely integrate with the Emergency Communication System using clean, RESTful URLs.

---

## 🔗 Clean Gateway Endpoints

The base API entry point URL is:

```http
http://localhost/EMERGENCY-COM/api/
```

By adding the resource path directly to the URL, you can fetch specific module feeds:

- **Consolidated System Overview**:
  `GET http://localhost/EMERGENCY-COM/api/` (No resource path needed)
- **Alerts Feed & Broadcasting**:
  `GET / POST http://localhost/EMERGENCY-COM/api/alerts`
- **Citizen Profiles & Telemetry**:
  `GET http://localhost/EMERGENCY-COM/api/users`
- **Emergency Call Audits**:
  `GET / POST http://localhost/EMERGENCY-COM/api/calls`
- **Disaster Weather & Earthquakes**:
  `GET http://localhost/EMERGENCY-COM/api/disaster`
- **Live Chat & Message Dispatch**:
  `GET / POST http://localhost/EMERGENCY-COM/api/chat`
- **System & API Auditing Logs**:
  `GET http://localhost/EMERGENCY-COM/api/audit`

---

## 🔑 Authentication

Every request must include the valid **Integrated API Key**:

```text
EMERGENCY-SYSTEM-INTEGRATED-KEY-2026
```

You can authenticate by providing the key through any of the following methods:

1. **Custom HTTP Header**: `X-Department-API-Key: EMERGENCY-SYSTEM-INTEGRATED-KEY-2026`
2. **Standard HTTP Header**: `X-API-Key: EMERGENCY-SYSTEM-INTEGRATED-KEY-2026`
3. **Bearer Token Authorization**: `Authorization: Bearer EMERGENCY-SYSTEM-INTEGRATED-KEY-2026`
4. **URL Query Parameter**: `?api_key=EMERGENCY-SYSTEM-INTEGRATED-KEY-2026` (Recommended only for testing)

---

## 📡 API Modules & Parameter Reference

### 1. Centralized System Overview (`/api/`)
Retrieves a combined overview of all modules in a single payload.
- **Method**: `GET`
- **Example Command**:
  ```bash
  curl -H "X-Department-API-Key: EMERGENCY-SYSTEM-INTEGRATED-KEY-2026" "http://localhost/EMERGENCY-COM/api/"
  ```

### 2. Alerts Module (`/api/alerts`)
Manages community notification broadcasts.

#### Retrieve Community Feed
- **Method**: `GET`
- **Filters**:
  - `status`: Filter by status (`active`, `archived`). Default: `active`
  - `category`: Filter by category name (e.g. `Weather`, `Earthquake`).
  - `time_filter`: Time window (`24h`, `week`, `month`, `all`). Default: `24h`
  - `limit`: Records to return. Default: `50`
- **Example Command**:
  ```bash
  curl -H "X-Department-API-Key: EMERGENCY-SYSTEM-INTEGRATED-KEY-2026" "http://localhost/EMERGENCY-COM/api/alerts?limit=5"
  ```

#### Broadcast a New Alert
- **Method**: `POST`
- **Body parameters (JSON)**:
  - `title` (string, **required**): Alert heading.
  - `message` (string, **required**): Content description.
  - `severity` (string, optional): `Low`, `Medium`, `High`, `Critical`. Default: `Medium`
  - `channels` (array/string, optional): Dispatch channels, e.g. `["push", "sms"]`.
- **Example Command**:
  ```bash
  curl -X POST -H "Content-Type: application/json" \
       -H "X-Department-API-Key: EMERGENCY-SYSTEM-INTEGRATED-KEY-2026" \
       -d '{"title": "Extreme Heat Warning", "message": "Stay hydrated.", "severity": "High", "channels": ["push", "sms"]}' \
       "http://localhost/EMERGENCY-COM/api/alerts"
  ```

---

### 3. Citizens & Locations Module (`/api/users`)
Provides details about registered citizens and coordinates telemetry.

#### List Citizens
- **Method**: `GET`
- **Example Command**:
  ```bash
  curl -H "X-Department-API-Key: EMERGENCY-SYSTEM-INTEGRATED-KEY-2026" "http://localhost/EMERGENCY-COM/api/users?limit=10"
  ```

#### Get User Profile Details
- **Method**: `GET`
- **Parameters**: `id` (**required**)
- **Example Command**:
  ```bash
  curl -H "X-Department-API-Key: EMERGENCY-SYSTEM-INTEGRATED-KEY-2026" "http://localhost/EMERGENCY-COM/api/users?id=1"
  ```

#### Get Current User Locations (for mapping telemetry)
- **Method**: `GET`
- **Parameters**: `action=locations` (**required**)
- **Example Command**:
  ```bash
  curl -H "X-Department-API-Key: EMERGENCY-SYSTEM-INTEGRATED-KEY-2026" "http://localhost/EMERGENCY-COM/api/users?action=locations"
  ```

---

### 4. Incident Logs & Calls Module (`/api/calls`)
Audits emergency signaling calls.

#### Retrieve Call Records
- **Method**: `GET`
- **Example Command**:
  ```bash
  curl -H "X-Department-API-Key: EMERGENCY-SYSTEM-INTEGRATED-KEY-2026" "http://localhost/EMERGENCY-COM/api/calls?limit=10"
  ```

#### Log Call/Incident Event
- **Method**: `POST`
- **Body parameters (JSON)**:
  - `call_id` (string, **required**): Unique call session ID.
  - `event` (string, **required**): Call state (`started`, `connected`, `ended`).
- **Example Command**:
  ```bash
  curl -X POST -H "Content-Type: application/json" \
       -H "X-Department-API-Key: EMERGENCY-SYSTEM-INTEGRATED-KEY-2026" \
       -d '{"call_id": "call_987654", "event": "started", "role": "dispatcher"}' \
       "http://localhost/EMERGENCY-COM/api/calls"
  ```

---

### 5. Weather & Earthquake Indicators (`/api/disaster`)
Provides live feeds of weather and seismic activities.

#### Retrieve Disaster Feeds
- **Method**: `GET`
- **Example Command**:
  ```bash
  curl -H "X-Department-API-Key: EMERGENCY-SYSTEM-INTEGRATED-KEY-2026" "http://localhost/EMERGENCY-COM/api/disaster?type=weather"
  ```

---

### 6. Open Communications & Chat Module (`/api/chat`)
Audit active live chat threads with citizens.

#### List Open Chat Threads
- **Method**: `GET`
- **Example Command**:
  ```bash
  curl -H "X-Department-API-Key: EMERGENCY-SYSTEM-INTEGRATED-KEY-2026" "http://localhost/EMERGENCY-COM/api/chat?action=conversations"
  ```

#### Fetch Message Transcripts
- **Method**: `GET`
- **Parameters**: `action=messages`, `conversation_id` (int, **required**)
- **Example Command**:
  ```bash
  curl -H "X-Department-API-Key: EMERGENCY-SYSTEM-INTEGRATED-KEY-2026" "http://localhost/EMERGENCY-COM/api/chat?action=messages&conversation_id=2"
  ```
