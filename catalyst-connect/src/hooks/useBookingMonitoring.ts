/**
 * Example: Real-time Update Custom Hook Usage
 * 
 * This file demonstrates how to create custom hooks 
 * that leverage the real-time update system
 */

import { useEffect, useState, useCallback } from 'react';
import { realtimeService, RealtimeUpdate } from '@/services/realtimeService';
import { useBookingStore } from '@/store/bookingStore';
import { BookingRequest } from '@/types/instrument';

/**
 * Hook to monitor booking status changes
 * Triggers callback when a booking changes status
 */
export const useBookingStatusMonitor = (
  onStatusChange: (booking: BookingRequest, oldStatus: string, newStatus: string) => void
) => {
  const [previousBookings, setPreviousBookings] = useState<Map<string, string>>(new Map());

  useEffect(() => {
    const unsubscribe = realtimeService.subscribe('booking', (update: RealtimeUpdate) => {
      const bookings = Array.isArray(update.data) ? update.data : [];

      bookings.forEach((booking: BookingRequest) => {
        const oldStatus = previousBookings.get(booking.id);
        if (oldStatus && oldStatus !== booking.status) {
          onStatusChange(booking, oldStatus, booking.status);
        }
      });

      // Update previous bookings map
      const newMap = new Map<string, string>();
      bookings.forEach((booking: BookingRequest) => {
        newMap.set(booking.id, booking.status);
      });
      setPreviousBookings(newMap);
    });

    return () => unsubscribe();
  }, [onStatusChange, previousBookings]);
};

/**
 * Hook to get pending approvals count
 * Automatically updates as new bookings come in
 */
export const usePendingBookingsCount = (): number => {
  const bookingRequests = useBookingStore((s) => s.bookingRequests);
  return bookingRequests.filter((b) => b.status === 'pending').length;
};

/**
 * Hook to get recent bookings
 * Returns the N most recent bookings
 */
export const useRecentBookings = (limit: number = 5): BookingRequest[] => {
  const bookingRequests = useBookingStore((s) => s.bookingRequests);
  
  return bookingRequests
    .slice(0, limit)
    .sort((a, b) => new Date(b.fromDate).getTime() - new Date(a.fromDate).getTime());
};

/**
 * Hook to monitor approval rate
 * Calculates percentage of approved vs total bookings
 */
export const useApprovalRate = (): {
  approvalRate: number;
  total: number;
  approved: number;
  rejected: number;
  pending: number;
} => {
  const bookingRequests = useBookingStore((s) => s.bookingRequests);

  const total = bookingRequests.length;
  const approved = bookingRequests.filter((b) => b.status === 'approved').length;
  const rejected = bookingRequests.filter((b) => b.status === 'rejected').length;
  const pending = bookingRequests.filter((b) => b.status === 'pending').length;

  const approvalRate = total > 0 ? Math.round((approved / total) * 100) : 0;

  return { approvalRate, total, approved, rejected, pending };
};

/**
 * Hook to detect booking surges
 * Alerts when pending bookings exceed threshold
 */
export const useBookingSurgeDetection = (threshold: number = 5) => {
  const bookingRequests = useBookingStore((s) => s.bookingRequests);
  const [isSurge, setIsSurge] = useState(false);

  const pendingCount = bookingRequests.filter((b) => b.status === 'pending').length;

  useEffect(() => {
    setIsSurge(pendingCount >= threshold);
  }, [pendingCount, threshold]);

  return {
    isSurge,
    pendingCount,
    threshold,
    percentage: Math.round((pendingCount / threshold) * 100),
  };
};

/**
 * Hook to get booking statistics over time
 * Updates as new data comes in
 */
export const useBookingStats = () => {
  const bookingRequests = useBookingStore((s) => s.bookingRequests);
  const dashboardData = useBookingStore((s) => s.dashboardData);

  return {
    total: bookingRequests.length,
    approved: bookingRequests.filter((b) => b.status === 'approved').length,
    rejected: bookingRequests.filter((b) => b.status === 'rejected').length,
    pending: bookingRequests.filter((b) => b.status === 'pending').length,
    avgResponseTime: dashboardData?.stats?.avgResponseTime || 0,
    totalQueue: dashboardData?.stats?.totalQueue || 0,
  };
};

/**
 * Example usage in a component:
 * 
 * function BookingDashboard() {
 *   const pendingCount = usePendingBookingsCount();
 *   const { approvalRate } = useApprovalRate();
 *   const { isSurge } = useBookingSurgeDetection(10);
 * 
 *   useBookingStatusMonitor((booking, oldStatus, newStatus) => {
 *     console.log(`Booking ${booking.id} changed from ${oldStatus} to ${newStatus}`);
 *   });
 * 
 *   return (
 *     <div>
 *       <p>Pending: {pendingCount}</p>
 *       <p>Approval Rate: {approvalRate}%</p>
 *       {isSurge && <p>⚠️ Booking surge detected!</p>}
 *     </div>
 *   );
 * }
 */
