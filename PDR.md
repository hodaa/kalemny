# MVP Product Requirements Document: App-to-App Voice Calling

## Overview

Kalmany will let two registered users call each other using internet-based voice calling. This is app-to-app calling over WebRTC; it does not use phone numbers, the PSTN, or a mobile carrier network.

The MVP uses Laravel, Vue 3, TypeScript, and Inertia.js 2.0. Agora provides real-time audio. Laravel manages authentication, authorization, Agora token generation, call metadata, and real-time call events. The Vue frontend, delivered through Inertia, uses the Agora Web SDK to publish and receive audio directly between participants.

## Problem

Users cannot currently start a real-time voice conversation inside Kalmeny. They must leave the product and use another calling service.

## Goal

Allow two authenticated Kalmany users to complete a private, one-to-one internet voice call from the application.

## MVP scope

- One-to-one voice calls between authenticated app users.
- Start, accept, decline, cancel, miss, and end a call.
- Microphone mute and unmute.
- Basic active-call duration and status UI.
- Agora RTC tokens generated securely by Laravel.
- Call-history records for completed, declined, missed, cancelled, and failed calls.

## Out of scope

- Phone-number/PSTN calling or receiving calls from non-users.
- Video calls, group calls, recordings, voicemail, and SMS.
- Push notifications for offline users.
- Call-quality analytics, advanced reconnection, admin reports, and block lists.

## User stories

- As an authenticated user, I can start a voice call with another eligible Kalmany user.
- As a recipient, I can see an incoming call and accept or decline it.
- As either participant, I can mute my microphone and end the call.
- As a user, I can view whether recent calls were completed, declined, missed, cancelled, or failed.

## Functional requirements

### Start a call

- The caller can select another eligible app user and start a call.
- The system prevents users from calling themselves.
- The backend authorizes the relationship/permission before creating a call.
- Laravel creates a unique call record and Agora channel name, then issues a short-lived token to the authorized participant.
- The caller sees a ringing state until the recipient accepts, declines, or the call times out.

### Receive a call

- The recipient receives an `incoming call` event while signed in.
- The incoming-call UI identifies the caller and offers Accept and Decline actions.
- If the recipient does not answer within the configured timeout, the call is marked `missed`.

### Active call

- Once accepted, each participant joins the assigned Agora channel and can hear the other participant.
- The browser asks for microphone permission before audio is published.
- A permission denial or connection failure produces a clear user-facing error and a failed call record.
- Each participant can mute/unmute and end the call.
- The UI displays elapsed time while connected.

### Completion and history

- Either participant can end a connected call.
- The system persists status, timestamps, duration, and ending user when available.
- A user may access only their own call history.

## Call states

```text
ringing → accepted → completed
ringing → declined
ringing → missed
ringing → cancelled
ringing/accepted → failed
```

## Technical design

### Technology stack

| Layer | Technology |
| --- | --- |
| Backend | Laravel / PHP |
| Application bridge and routing | Inertia.js 2.0 |
| Frontend | Vue 3 + TypeScript |
| Real-time media | Agora Web SDK / WebRTC |
| Real-time call events | Laravel Reverb, Pusher, or equivalent WebSocket broadcaster |
| Persistence | MySQL |

### Frontend

- Vue 3 + TypeScript, delivered through Inertia.js 2.0, integrates the Agora Web SDK.
- The client requests microphone access, joins/leaves the channel, publishes local audio, subscribes to remote audio, and manages call UI state.
- Use a focused calling composable/service, for example `useCall` or `agoraCallService`, rather than scattering provider calls throughout components.

### Backend

- Laravel owns authentication, authorization, call creation, Agora token generation, call state updates, and call history.
- Use Laravel Reverb, Pusher, or an equivalent WebSocket broadcaster for call-invitation events. This signaling is separate from real-time audio.
- Never route audio through PHP or expose the Agora App Certificate to the browser.

### Suggested endpoints

```text
POST /api/calls                  Start a call
POST /api/calls/{call}/accept    Accept a call
POST /api/calls/{call}/decline   Decline a call
POST /api/calls/{call}/cancel    Cancel a ringing call
POST /api/calls/{call}/end       End a connected call
GET  /api/calls/history          Read current user's history
```

### Suggested events

```text
call.incoming
call.accepted
call.declined
call.cancelled
call.ended
```

## Data model: `calls`

| Field | Description |
| --- | --- |
| `id` | Internal call identifier |
| `channel_name` | Unique Agora RTC channel |
| `caller_id` | User starting the call |
| `recipient_id` | User receiving the call |
| `status` | `ringing`, `accepted`, `completed`, `declined`, `missed`, `cancelled`, or `failed` |
| `started_at` | Start of ringing |
| `answered_at` | Acceptance time |
| `ended_at` | End time |
| `duration_seconds` | Connected duration |
| `ended_by_user_id` | Participant who ended the call, if applicable |
| `failure_reason` | Optional failure detail |
| `created_at`, `updated_at` | Audit timestamps |

## Security and privacy

- Require authenticated users and authorize every call action.
- Generate short-lived Agora tokens on the server only.
- Keep the Agora App Certificate and all secrets in server environment configuration.
- Use HTTPS in production; browsers require a secure context for microphone access.
- Do not record or store audio in the MVP.
- Persist only necessary call metadata.

## Acceptance criteria

- Two signed-in users can complete a one-to-one voice call in supported browsers.
- A recipient can accept, decline, or miss a call.
- Both participants can mute and end an active call.
- Call records correctly show completed, declined, missed, cancelled, and failed outcomes.
- The frontend never contains server-side Agora credentials.
