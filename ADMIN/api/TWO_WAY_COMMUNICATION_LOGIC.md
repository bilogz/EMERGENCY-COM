# Two-Way Communication Logic (Implemented)

## Roles
- `citizen/user`: starts conversations, sends replies, receives staff updates.
- `staff/admin/super_admin`: replies, assigns, resolves/closes, reopens.

## Conversation Workflow Status
- Active workflow states: `open`, `in_progress`, `waiting_user` (+ legacy `active`).
- Closed workflow states: `resolved`, `closed`.
- UI compatibility:
  - Active states are exposed as `status: active`.
  - Closed states are exposed as `status: closed`.
  - Raw state is exposed as `workflowStatus`.

## Message Flow
- User sends:
  - Creates conversation if needed.
  - Auto-normalizes category/priority.
  - Attempts fallback assignment to available admin/staff.
  - Sets conversation status to `in_progress` (or DB-compatible fallback).
- Admin sends:
  - Appends message.
  - Updates assignment to replying admin when possible.
  - Sets conversation status to `waiting_user` (or DB-compatible fallback).

## Read / Unread Logic
- Admin reading thread marks inbound non-admin messages as read.
- User reading thread marks inbound admin messages as read.
- Unread badge counts active workflow states, not only literal `active`.

## Admin Inbox Filtering
`ADMIN/api/chat-get-conversations.php` supports:
- `status`: `open`, `assigned`, `closed`, `active`, `all`
- `category` / `department`
- `priority`
- `assigned_to`
- `assigned_to_me=true`
- `q` / `search`

Sorting priority:
1. Unread first
2. Urgent first
3. Latest message time

## Realtime (SSE)
- Admin: `ADMIN/api/realtime.php`
- User: `USERS/api/realtime.php`

Emitted events:
- `message:new`
- `conversation:status_changed`
- `conversation:unread`
- `heartbeat`

## Mark-As-Read Endpoints
- Admin: `ADMIN/api/chat-mark-read.php`
- User: `USERS/api/chat-mark-read.php`

