# Real-Time Dashboard Updates - Documentation

## Overview

The real-time update system enables the analytical dashboard to automatically refresh when bookings are created, approved, or rejected. It uses polling to fetch the latest data from the backend API at regular intervals.

## Architecture

### Components

1. **RealtimeService** (`src/services/realtimeService.ts`)
   - Core service managing real-time updates
   - Handles polling intervals
   - Manages listener subscriptions
   - Provides singleton pattern instance

2. **useRealtimeUpdates Hook** (`src/hooks/useRealtimeUpdates.ts`)
   - React hook for subscribing to real-time updates
   - `useRealtimeUpdates()` - Subscribe to specific events
   - `useRealtimePolling()` - Manage polling lifecycle
   - `useRealtimeStatus()` - Get current polling status

3. **Zustand Store Integration** (`src/store/bookingStore.ts`)
   - `startRealtimeUpdates()` - Begin polling
   - `stopRealtimeUpdates()` - Stop all polling
   - `updateBookingsFromRealtime()` - Update booking data
   - `updateDashboardFromRealtime()` - Update dashboard stats

4. **Dashboard Components**
   - `AdminDashboard.tsx` - Main dashboard with live stats
   - `AdminAnalytics.tsx` - Analytics charts with live data
   - `AdminBookings.tsx` - Booking requests table with live status

## How It Works

### Polling Mechanism

```
1. Component mounts
   ↓
2. startRealtimeUpdates() is called
   ↓
3. RealtimeService starts polling:
   - Bookings API every 3 seconds
   - Dashboard API every 5 seconds
   ↓
4. API responses trigger listeners
   ↓
5. Zustand store is updated
   ↓
6. Components re-render with new data
   ↓
7. User sees live updates
```

### Event Flow

```
┌─────────────────────────────────────┐
│  User creates/approves/rejects      │
│  booking via AdminBookings page     │
└───────────┬───────────────────────┘
            │
            ▼
┌─────────────────────────────────────┐
│  API request sent to backend        │
└───────────┬───────────────────────┘
            │
            ▼
┌─────────────────────────────────────┐
│  Backend processes & updates DB     │
└───────────┬───────────────────────┘
            │
            ▼
┌─────────────────────────────────────┐
│  Meanwhile: RealtimeService polls   │
│  GET /admin/bookings every 3s       │
│  GET /admin/dashboard every 5s      │
└───────────┬───────────────────────┘
            │
            ▼
┌─────────────────────────────────────┐
│  New data received, listeners        │
│  notified, store updated             │
└───────────┬───────────────────────┘
            │
            ▼
┌─────────────────────────────────────┐
│  Components re-render with new data │
│  Dashboard shows live updates       │
└─────────────────────────────────────┘
```

## Usage

### Basic Usage in Components

```tsx
import { useBookingStore } from "@/store/bookingStore";

function MyDashboard() {
  const startRealtimeUpdates = useBookingStore((s) => s.startRealtimeUpdates);
  const stopRealtimeUpdates = useBookingStore((s) => s.stopRealtimeUpdates);

  useEffect(() => {
    // Start real-time updates on mount
    startRealtimeUpdates();

    // Cleanup on unmount
    return () => {
      stopRealtimeUpdates();
    };
  }, [startRealtimeUpdates, stopRealtimeUpdates]);

  return <div>Dashboard with live updates</div>;
}
```

### Subscribing to Specific Events

```tsx
import { useRealtimeUpdates } from "@/hooks/useRealtimeUpdates";

function BookingMonitor() {
  const [latestBooking, setLatestBooking] = useState(null);

  useRealtimeUpdates("booking", (update) => {
    console.log("New booking update:", update.data);
    setLatestBooking(update.data);
  });

  return <div>Latest Booking: {latestBooking}</div>;
}
```

### Custom Polling Intervals

```tsx
import { useRealtimePolling } from "@/hooks/useRealtimeUpdates";

function FastDashboard() {
  // Poll bookings every 2 seconds, dashboard every 3 seconds
  useRealtimePolling(
    true, // enable booking polling
    true, // enable dashboard polling
    2000, // booking interval
    3000, // dashboard interval
  );

  return <div>Fast updating dashboard</div>;
}
```

## Polling Intervals

- **Bookings**: 3000ms (3 seconds) - detects new bookings quickly
- **Dashboard**: 5000ms (5 seconds) - less frequent for efficiency

Adjust these intervals based on your needs:

- Lower intervals = more real-time but more server load
- Higher intervals = less server load but slower updates

## API Endpoints Used

The real-time system polls these endpoints:

1. **GET /admin/bookings**
   - Returns array of all booking requests
   - Used for: AdminBookings page live updates

2. **GET /admin/dashboard**
   - Returns dashboard data with stats
   - Used for: AdminDashboard and AdminAnalytics updates

## Performance Considerations

1. **Efficient Updates**
   - Only updates store if data has changed
   - Reduces unnecessary re-renders

2. **Memory Management**
   - Properly cleans up intervals on unmount
   - Removes event listeners when stopping

3. **Server Load**
   - Polling every 3-5 seconds is reasonable
   - Can be adjusted based on requirements

4. **Network Usage**
   - Each poll is a separate HTTP request
   - Consider response caching if needed

## Visual Indicators

All pages show a live update indicator:

```
🔴 Live Updates Active
```

The indicator:

- Appears when real-time updates are enabled
- Shows a pulsing red dot
- Disappears when updates are stopped

## Extending the System

### Adding New Polling Interfaces

```tsx
// In realtimeService.ts
startCustomUpdates(intervalMs: number = 3000) {
  if (this.pollIntervals.has('custom')) return;

  const poll = async () => {
    try {
      const response = await API.get('/custom/endpoint');
      this.emit('custom', {
        type: 'custom',
        data: response?.data?.data,
        timestamp: Date.now(),
      });
    } catch (error) {
      console.error('Custom update error:', error);
    }
  };

  poll();
  const intervalId = setInterval(poll, intervalMs);
  this.pollIntervals.set('custom', intervalId);
}
```

### Using Custom Events in Components

```tsx
import { realtimeService } from "@/services/realtimeService";

useEffect(() => {
  const unsubscribe = realtimeService.subscribe("custom", (update) => {
    console.log("Custom update:", update.data);
  });

  return () => unsubscribe();
}, []);
```

## Troubleshooting

### Dashboard not updating?

1. Check network tab - ensure API calls are being made
2. Verify real-time service is running: `realtimeService.getPollingStatus()`
3. Check console for errors
4. Ensure endpoints return correct data format

### Too many API calls?

1. Increase polling intervals in store/bookingStore.ts
2. Example: Change from 3000ms to 5000ms (less frequent polling)

### Performance issues?

1. Disable real-time listening when not on admin pages
2. Properly unmount components to stop polling
3. Consider adding request debouncing

## Browser Console Commands

```javascript
// Check if real-time updates are active
realtimeService.getPollingStatus();

// Get registered events
realtimeService.getRegisteredEvents();

// Stop all updates
realtimeService.stopAllUpdates();

// Start specific update
realtimeService.startBookingUpdates(3000);
```

## Mobile Responsiveness

The real-time system works on all devices:

- Desktop: Smooth real-time updates with desktop UI
- Tablet: Optimized card view with live updates
- Mobile: Card-based interface with auto-refreshing data

## Future Enhancements

Possible improvements:

1. **WebSocket Support**
   - Replace polling with WebSocket for true real-time
   - Reduces server load significantly

2. **Push Notifications**
   - Notify users of new bookings/approvals
   - Browser notifications integration

3. **Server-Sent Events (SSE)**
   - One-way server push
   - More efficient than polling

4. **Local Caching**
   - Cache recent updates
   - Faster initial page loads

5. **Update Batching**
   - Group multiple updates
   - Reduce re-render frequency

## Related Files

- `src/services/realtimeService.ts` - Core real-time service
- `src/hooks/useRealtimeUpdates.ts` - React hooks
- `src/store/bookingStore.ts` - Zustand store
- `src/pages/admin/AdminDashboard.tsx` - Main dashboard
- `src/pages/admin/AdminAnalytics.tsx` - Analytics page
- `src/pages/admin/AdminBookings.tsx` - Bookings page
