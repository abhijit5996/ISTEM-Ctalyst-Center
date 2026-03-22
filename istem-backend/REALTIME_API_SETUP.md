# Backend Setup Guide for Real-Time Dashboard Updates

## Overview

This guide ensures the backend API endpoints are properly configured to support the real-time dashboard update system.

## Required API Endpoints

### 1. GET /api/admin/bookings

**Purpose**: Returns all booking requests (used for real-time bookings list updates)

**Expected Response Format**:

```json
{
    "success": true,
    "data": [
        {
            "id": "B123456",
            "name": "John Doe",
            "email": "john@example.com",
            "instrumentName": "Electron Microscope",
            "instrumentId": "INST-001",
            "fromDate": "2024-03-25",
            "toDate": "2024-03-26",
            "status": "pending",
            "userType": "student",
            "department": "Engineering",
            "created_at": "2024-03-21T10:00:00Z",
            "updated_at": "2024-03-21T10:00:00Z"
        }
    ]
}
```

**Status Field Values**:

- `pending` - Awaiting approval
- `approved` - Approved by admin
- `rejected` - Rejected by admin

**Update Frequency**: Polled every 3 seconds by frontend

### 2. GET /api/admin/dashboard

**Purpose**: Returns dashboard statistics and data (used for analytics)

**Expected Response Format**:

```json
{
    "success": true,
    "data": {
        "instruments": [
            {
                "id": "INST-001",
                "name": "Electron Microscope",
                "category": "Microscopy",
                "status": "available",
                "cost": 500,
                "usageCost": "₹500/hour",
                "bookedSlots": [],
                "waitingQueue": [],
                "description": "High-resolution EM"
            }
        ],
        "bookings": [
            {
                "id": "B123456",
                "name": "John Doe",
                "email": "john@example.com",
                "instrumentName": "Electron Microscope",
                "fromDate": "2024-03-25",
                "toDate": "2024-03-26",
                "status": "pending"
            }
        ],
        "stats": {
            "total_instruments": 15,
            "total_bookings": 42,
            "pending": 8,
            "approved": 28,
            "rejected": 6,
            "available": 12,
            "booked": 3,
            "blocked": 0,
            "totalQueue": 5,
            "avgResponseTime": 24,
            "avgUtilization": 68
        }
    }
}
```

**Update Frequency**: Polled every 5 seconds by frontend

## Backend Implementation Checklist

### Controller Methods

- [ ] `BookingController@adminBookings()` - Returns all bookings
- [ ] `BookingController@dashboard()` - Returns dashboard stats
- [ ] `InstrumentController@index()` - Returns all instruments
- [ ] All endpoints properly secured with authentication

### Data Consistency

- [ ] Booking status changes are immediately persisted to DB
- [ ] Status field is consistent (pending/approved/rejected)
- [ ] Timestamps are accurate (created_at, updated_at)
- [ ] Deleted bookings are soft-deleted (not included in list)

### API Response Validation

- [ ] Always wrap data in `{ success: true, data: [...] }` format
- [ ] Include all required fields in booking object
- [ ] Handle errors gracefully with proper HTTP status codes
- [ ] Support CORS requests from frontend URL

## Performance Optimization

### Database Queries

```php
// Optimize queries to avoid N+1 problems
public function adminBookings() {
    return Booking::with('instrument')
        ->orderBy('created_at', 'desc')
        ->get(); // Eager load relationships
}

public function dashboard() {
    $instruments = Instrument::with(['bookedSlots', 'waitingQueue'])->get();
    $bookings = Booking::with('instrument')->get();

    // Calculate stats
    $stats = [
        'total_instruments' => $instruments->count(),
        'total_bookings' => $bookings->count(),
        'pending' => $bookings->where('status', 'pending')->count(),
        'approved' => $bookings->where('status', 'approved')->count(),
        'rejected' => $bookings->where('status', 'rejected')->count(),
        // ... additional stats
    ];

    return response()->json([
        'success' => true,
        'data' => [
            'instruments' => $instruments,
            'bookings' => $bookings,
            'stats' => $stats
        ]
    ]);
}
```

### Caching Strategy

```php
// Implement caching for frequently accessed data
public function dashboard() {
    // Cache dashboard data for 2 seconds
    $data = Cache::remember('admin-dashboard', 2, function () {
        return [
            'instruments' => Instrument::with(['bookedSlots', 'waitingQueue'])->get(),
            'bookings' => Booking::get(),
            'stats' => $this->calculateStats(),
        ];
    });

    return response()->json(['success' => true, 'data' => $data]);
}
```

## API Endpoints Location

### In `routes/api.php`:

```php
<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\BookingController;

// Admin dashboard endpoints
Route::get('/admin/bookings', [BookingController::class, 'adminBookings']);
Route::get('/admin/dashboard', [BookingController::class, 'dashboard']);

// Additional booking endpoints
Route::post('/bookings', [BookingController::class, 'store']);
Route::put('/bookings/{id}/approve', [BookingController::class, 'approve']);
Route::put('/bookings/{id}/reject', [BookingController::class, 'reject']);
```

## Error Handling

### Common Issues & Solutions

**Issue**: Frontend shows "No updates" indicator

- **Solution**: Ensure GET requests return correct JSON format
- **Debug**: Check API response in Network tab browser dev tools

**Issue**: Dashboard numbers don't match backend

- **Solution**: Verify stats calculation logic matches frontend expectations
- **Debug**: Log stats generation in `dashboard()` method

**Issue**: Past bookings still showing as pending

- **Solution**: Implement auto-expiration of old pending bookings
- **Example**: Delete pending bookings older than 7 days

## CORS Configuration

Ensure CORS is properly configured for the frontend origin:

```php
// config/cors.php
'allowed_origins' => ['http://localhost:5173', 'https://yourdomain.com'],
'allowed_methods' => ['GET', 'POST', 'PUT', 'DELETE'],
'allowed_headers' => ['*'],
'supports_credentials' => true,
```

## Real-time Enhancement (WebSocket - Future)

For true real-time updates without polling:

```php
// Option 1: Laravel Broadcasting + Pusher
// Broadcast booking status changes to websocket
event(new BookingStatusChanged($booking));

// Option 2: Server-Sent Events (SSE)
Route::get('/admin/bookings-stream', function () {
    response()->stream(function () {
        while (true) {
            $bookings = Booking::latest()->get();
            echo "data: " . json_encode($bookings) . "\n\n";
            ob_flush();
            flush();
            sleep(3);
        }
    }, 200, [
        'Content-Type' => 'text/event-stream',
        'Cache-Control' => 'no-cache',
        'Connection' => 'keep-alive',
    ]);
});
```

## Testing the API

### Using cURL:

```bash
# Test bookings endpoint
curl http://localhost:8000/api/admin/bookings

# Test dashboard endpoint
curl http://localhost:8000/api/admin/dashboard

# Test with headers
curl -H "Accept: application/json" \
     -H "Authorization: Bearer TOKEN" \
     http://localhost:8000/api/admin/dashboard
```

### Using Laravel Tinker:

```php
php artisan tinker

>>> app('App\Http\Controllers\BookingController')->adminBookings();
>>> app('App\Http\Controllers\BookingController')->dashboard();
```

## Monitoring & Logging

### Log API Calls:

```php
// Add to BookingController
public function adminBookings() {
    Log::info('Admin fetching bookings', ['timestamp' => now()]);
    return response()->json(['success' => true, 'data' => Booking::all()]);
}

public function dashboard() {
    Log::info('Admin fetching dashboard', ['timestamp' => now()]);
    // ... rest of method
}
```

### Check Logs:

```bash
tail -f storage/logs/laravel.log | grep -i "admin fetching"
```

## Load Testing

If experiencing slow response times with many bookings:

1. **Implement pagination**:

    ```php
    Booking::paginate(50)->toArray();
    ```

2. **Add database indexes**:

    ```php
    Schema::table('bookings', function (Blueprint $table) {
        $table->index('status');
        $table->index('created_at');
    });
    ```

3. **Use query optimization**:
    ```php
    // Avoid selecting unnecessary columns
    Booking::select('id', 'name', 'status', 'instrument_id')
            ->where('status', 'pending')
            ->get();
    ```

## Deployment Checklist

- [ ] API endpoints accessible from production frontend URL
- [ ] CORS headers properly configured
- [ ] Database migrations run successfully
- [ ] Error responses formatted correctly
- [ ] Rate limiting configured (if needed)
- [ ] SSL/HTTPS enabled
- [ ] Database indexes created for frequently queried fields
- [ ] Logging configured for monitoring
- [ ] Backup strategy in place

## Support & Troubleshooting

For issues with real-time updates:

1. Check backend logs: `storage/logs/laravel.log`
2. Verify API response format with browser DevTools
3. Test endpoints with Postman or cURL
4. Check database for data consistency
5. Review CORS configuration

## Related Files

- `app/Http/Controllers/BookingController.php` - Main controller
- `routes/api.php` - API routes definition
- `app/Models/Booking.php` - Booking model
- `database/migrations/` - Database schema
