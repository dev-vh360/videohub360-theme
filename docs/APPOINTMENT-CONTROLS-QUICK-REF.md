# Quick Reference: Appointment Session Controls

## Issue → Solution

### ❌ Problem
```
Professional books appointment
      ↓
Live Room created automatically
      ↓
🚫 NO WAY TO START IT 🚫
      ↓
Room stays offline forever
```

### ✅ Solution
```
Professional books appointment
      ↓
Live Room created automatically
      ↓
✅ Dashboard → Availability → "Start Session" button
      ↓
Professional clicks "Start Session"
      ↓
Room goes LIVE instantly
      ↓
Client sees "Live Now" in My Appointments
      ↓
Client clicks "Join Session"
      ↓
Video consultation begins! 🎥
```

---

## Where to Find It

### For Professionals (Starting Sessions)
```
Dashboard
  └── Availability tab
      └── Upcoming Appointments section
          └── [Start Session] button
```

### For Clients (Joining Sessions)
```
Dashboard
  └── My Appointments tab (NEW!)
      └── Appointment list
          └── [Join Session] button (pulses when live)
```

---

## Quick Start Guide

### Professional (Starting a Session)
1. Go to **Dashboard**
2. Click **Availability** tab
3. Scroll down to **Upcoming Appointments**
4. Find your appointment
5. Click **Start Session** button
6. ✅ You're live! Client can now join.

### Client (Joining a Session)
1. Go to **Dashboard**
2. Click **My Appointments** tab (new!)
3. Look for **Live Now** green badge
4. Click the blue **Join Session** button
5. ✅ Enter the Live Room!

---

## Visual Guide

### Professional View (Availability Tab)

```
┌─────────────────────────────────────────────┐
│ Upcoming Appointments                        │
├─────────────────────────────────────────────┤
│                                             │
│ ┌─────────────────────────────────────┐   │
│ │ Jane Smith               [Live Now] │   │
│ │ 📅 Feb 24, 2026  🕐 2:00 PM        │   │
│ │                                     │   │
│ │ [Open Room]  [End Session]          │   │
│ └─────────────────────────────────────┘   │
│                                             │
│ ┌─────────────────────────────────────┐   │
│ │ John Doe                 [Offline]  │   │
│ │ 📅 Feb 25, 2026  🕐 3:00 PM        │   │
│ │                                     │   │
│ │ [Open Room]  [Start Session]        │   │
│ └─────────────────────────────────────┘   │
│                                             │
└─────────────────────────────────────────────┘
```

### Client View (My Appointments Tab)

```
┌─────────────────────────────────────────────┐
│ My Appointments                              │
├─────────────────────────────────────────────┤
│                                             │
│ ┌─────────────────────────────────────┐   │
│ │ Appointment with Dr. Smith           │   │
│ │                        [Live Now] 🟢 │   │
│ │ 📅 Feb 24, 2026  🕐 2:00 PM        │   │
│ │                                     │   │
│ │ ⚡ The professional is live now!    │   │
│ │                                     │   │
│ │          [Join Session] ← (pulsing) │   │
│ └─────────────────────────────────────┘   │
│                                             │
│ ┌─────────────────────────────────────┐   │
│ │ Appointment with Dr. Jones           │   │
│ │                        [Scheduled]   │   │
│ │ 📅 Feb 25, 2026  🕐 3:00 PM        │   │
│ │                                     │   │
│ │              [View Room]             │   │
│ └─────────────────────────────────────┘   │
│                                             │
└─────────────────────────────────────────────┘
```

---

## Status Indicators

### Professional's View
| Badge | Meaning | Action Available |
|-------|---------|------------------|
| 🟢 **Live Now** | Session is active | End Session |
| ⚪ **Offline** | Session not started | Start Session |
| 🔴 **Ended** | Session was ended | - |

### Client's View
| Badge | Meaning | Action Available |
|-------|---------|------------------|
| 🟢 **Live Now** | Can join now | Join Session (pulsing) |
| ⚪ **Scheduled** | Future appointment | View Room |
| 🔴 **Past** | Date has passed | View Room |
| 🔴 **Ended** | Professional ended it | View Room |

---

## Technical Flow

```
[Professional clicks "Start Session"]
         ↓
   AJAX Request
    action: vh360_set_stream_status
    post_id: live_room_id
    status: 'yes'
         ↓
   Update meta: _vh360_is_live = 'yes'
         ↓
   JavaScript updates UI
   - Button → "End Session"
   - Status → "Live Now"
         ↓
   [Client sees "Live Now" badge]
         ↓
   [Client clicks "Join Session"]
         ↓
   Opens Live Room page
         ↓
   Access control verifies client
         ↓
   Client enters video session ✅
```

---

## Files Involved

```
Professional Controls:
  template-parts/dashboard/availability.php
    - Query upcoming appointments
    - Display with Start/End buttons
    - JavaScript handlers for AJAX

Client Access:
  template-parts/dashboard/appointments.php (NEW!)
    - Query user's appointments
    - Display with Join button
    - Live status indicators

Navigation:
  template-parts/dashboard/nav.php
    - Added "My Appointments" tab
  
  template-dashboard.php
    - Load appointments tab content

Backend:
  bundled-plugins/videohub360-core/includes/class-videohub360-ajax.php
    - vh360_set_stream_status endpoint (existing)
    - Sets _vh360_is_live meta
```

---

## Key Features

✅ **One-Click Start:** Professional starts session instantly
✅ **Real-Time Updates:** No page refresh needed
✅ **Live Notifications:** Client sees green alert when live
✅ **Easy Joining:** Prominent Join button for clients
✅ **Status Clarity:** Color-coded badges for all states
✅ **Mobile Friendly:** Works on all screen sizes
✅ **Existing Infrastructure:** Uses existing AJAX endpoints
✅ **No Breaking Changes:** Doesn't affect other features

---

## Common Questions

**Q: Where do professionals start sessions?**
A: Dashboard → Availability tab → Upcoming Appointments section

**Q: Where do clients join sessions?**
A: Dashboard → My Appointments tab → Click "Join Session"

**Q: How do clients know when to join?**
A: Green "Live Now" badge appears + pulsing Join button

**Q: Can anyone join an appointment room?**
A: No - only professional, client, and admins (access control enforced)

**Q: What if professional forgets to end session?**
A: They can click "End Session" button anytime from Availability tab

**Q: Do clients need to book first?**
A: Yes - they must book an appointment through the professional's business profile

---

## Success Criteria ✅

- [x] Professionals can start sessions
- [x] Professionals can end sessions
- [x] Clients can see their appointments
- [x] Clients can join when live
- [x] Status updates in real-time
- [x] Mobile responsive
- [x] Uses existing infrastructure
- [x] No breaking changes

**Status: COMPLETE** ✅

---

*For detailed technical documentation, see `docs/APPOINTMENT-SESSION-CONTROLS.md`*
