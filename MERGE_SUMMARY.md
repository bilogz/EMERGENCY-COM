# Emergency Alert Automation System - Merge Summary

## Changes Made

### 1. Database Schema (SQL Migration)
**File:** `sql/incident_alert_automation_schema.sql` (UNCHANGED - safe to run)

- Creates `incidents` table if not exists
- Adds `incident_id`, `category`, and `area` columns to `alerts` table safely
- Uses existence checks to prevent errors if columns already exist

### 2. Backend Integration

#### A. Added Incident Processing to Automated Warnings API
**File:** `ADMIN/api/automated-warnings.php`

**Added:**
- New action: `processIncident` (POST request)
- Function: `downgradeSeverity()` - Downgrades severity if confidence < 60%
- Function: `generateAlertMessage()` - Generates alert messages
- Function: `createIncidentAlert()` - Creates alerts in database

**Logic:**
- EXTREME severity → generates "Emergency Alert" immediately
- MODERATE severity → generates "Warning" for affected area only
- LOW severity → logs incident, no alert generated
- Confidence < 60% → downgrades severity by one level

**API Endpoint:** `POST /ADMIN/api/automated-warnings.php?action=processIncident`

#### B. Added Area Filtering to Get Alerts API
**File:** `USERS/api/get-alerts.php`

**Modified:**
- Added user area detection from `users.barangay` field
- Added area filtering logic (shows alerts for user's area OR city-wide alerts)
- Checks for `area` column existence before using it
- Returns `category` and `area` fields if they exist

**Behavior:**
- Logged-in users only see alerts for their barangay (or city-wide alerts)
- Guests see all alerts (no filtering)

### 3. Frontend Updates

#### A. Added Severity Color Coding
**File:** `USERS/alerts.php`

**Modified:**
- Updated `createAlertCard()` function to use severity-based colors
- EXTREME (Emergency Alert) → Red (#e74c3c)
- MODERATE (Warning) → Yellow (#f39c12)
- Displays category (Emergency Alert/Warning) in badge

### 4. Files Removed (Duplicates)

**Deleted:**
- `ADMIN/api/incident-processor.php` (merged into automated-warnings.php)
- `ADMIN/api/get-alerts-by-area.php` (merged into get-alerts.php)
- `ADMIN/sidebar/incident-alert-dashboard.php` (not needed - use existing dashboard)
- `ADMIN/sidebar/css/incident-alert-dashboard.css` (not needed)
- `ADMIN/sidebar/js/incident-alert-dashboard.js` (not needed)

## System Behavior

### Incident Processing Flow

1. **Receive Incident** → POST to `automated-warnings.php?action=processIncident`
2. **Validate** → Type (flood, earthquake, fire, crime, typhoon), Severity (LOW, MODERATE, EXTREME), Area
3. **Check Confidence** → If < 60%, downgrade severity by one level
4. **Process Severity:**
   - **EXTREME** → Create "Emergency Alert" (sent immediately)
   - **MODERATE** → Create "Warning" (sent to affected area only)
   - **LOW** → Log incident, no alert generated
5. **Store** → Incident saved to `incidents` table, Alert (if generated) saved to `alerts` table

### Alert Filtering Flow

1. **User Requests Alerts** → GET `get-alerts.php`
2. **Check Login** → If logged in, get user's barangay from `users.barangay`
3. **Filter Alerts** → Show alerts where `alerts.area` matches user's barangay OR is NULL (city-wide)
4. **Return** → Alerts filtered by area (for logged-in users)

### Display Flow

1. **Load Alerts** → Frontend polls `get-alerts.php` every 10 seconds
2. **Color Coding:**
   - Emergency Alert (EXTREME) → Red border and badge
   - Warning (MODERATE) → Yellow border and badge
   - Other → Category-based colors
3. **Display** → Alerts shown with severity-based styling

## API Usage

### Create Incident
```bash
POST /ADMIN/api/automated-warnings.php?action=processIncident
Content-Type: application/json

{
  "type": "flood",
  "severity": "EXTREME",
  "area": "Barangay Batasan",
  "confidence": 90,
  "description": "Heavy flooding reported"
}
```

### Get Alerts (with area filtering)
```bash
GET /USERS/api/get-alerts.php?status=active
# Automatically filters by user's barangay if logged in
```

## Database Requirements

**Required Tables:**
- `incidents` - Created by migration
- `alerts` - Extended with `incident_id`, `category`, `area` columns
- `users` - Must have `barangay` field (already exists)

**Migration:**
Run `sql/incident_alert_automation_schema.sql` to create/add required tables/columns.

## Integration Notes

- **No breaking changes** - Existing code continues to work
- **Backward compatible** - Checks for column existence before using new fields
- **Safe for production** - All changes use existence checks and fallbacks
- **No duplicate functionality** - Removed duplicate files, merged into existing structure

## Testing Checklist

- [x] Incident processing works (EXTREME/MODERATE/LOW)
- [x] Confidence downgrade works (< 60%)
- [x] Area filtering works for logged-in users
- [x] Severity color coding works (red/yellow)
- [x] No duplicate files remain
- [x] Existing functionality unchanged
