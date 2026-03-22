/**
 * Custom hook for real-time updates
 * Integrates with RealtimeService to provide live data to React components
 */

import { useEffect, useCallback } from 'react';
import { realtimeService, RealtimeUpdate } from '@/services/realtimeService';

/**
 * Hook to subscribe to real-time updates
 * @param event - Event name to subscribe to ('booking', 'dashboard', etc.)
 * @param onUpdate - Callback when update is received
 * @param enabled - Whether to enable the subscription
 */
export const useRealtimeUpdates = (
  event: string,
  onUpdate: (update: RealtimeUpdate) => void,
  enabled: boolean = true
) => {
  useEffect(() => {
    if (!enabled) return;

    // Subscribe to updates
    const unsubscribe = realtimeService.subscribe(event, onUpdate);

    // Cleanup on unmount or when event changes
    return () => {
      unsubscribe();
    };
  }, [event, onUpdate, enabled]);
};

/**
 * Hook to manage real-time polling lifecycle
 * Starts polling on mount and stops on unmount
 * @param pollBookings - Whether to poll bookings
 * @param pollDashboard - Whether to poll dashboard
 * @param bookingInterval - Polling interval for bookings (ms)
 * @param dashboardInterval - Polling interval for dashboard (ms)
 */
export const useRealtimePolling = (
  pollBookings: boolean = true,
  pollDashboard: boolean = true,
  bookingInterval: number = 3000,
  dashboardInterval: number = 5000
) => {
  useEffect(() => {
    if (pollBookings) {
      realtimeService.startBookingUpdates(bookingInterval);
    }

    if (pollDashboard) {
      realtimeService.startDashboardUpdates(dashboardInterval);
    }

    return () => {
      if (pollBookings) {
        realtimeService.stopUpdates('bookings');
      }
      if (pollDashboard) {
        realtimeService.stopUpdates('dashboard');
      }
    };
  }, [pollBookings, pollDashboard, bookingInterval, dashboardInterval]);
};

/**
 * Hook to get current polling status
 */
export const useRealtimeStatus = () => {
  return realtimeService.getPollingStatus();
};
