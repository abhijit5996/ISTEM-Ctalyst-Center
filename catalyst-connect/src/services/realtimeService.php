/**
 * Real-time Updates Service
 * Handles real-time updates for bookings, approvals, and rejections
 * Uses polling with configurable intervals
 */

import API from '@/api/axios';

export interface RealtimeUpdate {
  type: 'booking' | 'approval' | 'rejection' | 'dashboard';
  data: any;
  timestamp: number;
}

type UpdateCallback = (update: RealtimeUpdate) => void;

class RealtimeService {
  private listeners: Map<string, Set<UpdateCallback>> = new Map();
  private pollIntervals: Map<string, NodeJS.Timeout> = new Map();
  private lastFetchTimestamps: Map<string, number> = new Map();
  private isPolling: boolean = false;

  /**
   * Subscribe to real-time updates
   */
  subscribe(event: string, callback: UpdateCallback): () => void {
    if (!this.listeners.has(event)) {
      this.listeners.set(event, new Set());
    }
    this.listeners.get(event)!.add(callback);

    // Return unsubscribe function
    return () => {
      this.listeners.get(event)?.delete(callback);
    };
  }

  /**
   * Emit updates to all listeners
   */
  private emit(event: string, update: RealtimeUpdate) {
    const callbacks = this.listeners.get(event);
    if (callbacks) {
      callbacks.forEach(callback => {
        try {
          callback(update);
        } catch (error) {
          console.error(`Error in listener for ${event}:`, error);
        }
      });
    }
  }

  /**
   * Start polling for booking updates
   */
  startBookingUpdates(intervalMs: number = 3000) {
    if (this.pollIntervals.has('bookings')) {
      return; // Already polling
    }

    const poll = async () => {
      try {
        const response = await API.get('/admin/bookings');
        const bookings = response?.data?.data || [];

        if (bookings.length > 0) {
          this.emit('booking', {
            type: 'booking',
            data: bookings,
            timestamp: Date.now(),
          });
        }
      } catch (error) {
        console.error('Error fetching booking updates:', error);
      }
    };

    // Initial poll
    poll();

    // Set up interval
    const intervalId = setInterval(poll, intervalMs);
    this.pollIntervals.set('bookings', intervalId);
    this.isPolling = true;
  }

  /**
   * Start polling for dashboard updates
   */
  startDashboardUpdates(intervalMs: number = 5000) {
    if (this.pollIntervals.has('dashboard')) {
      return; // Already polling
    }

    const poll = async () => {
      try {
        const response = await API.get('/admin/dashboard');
        const data = response?.data?.data || {};

        if (data) {
          this.emit('dashboard', {
            type: 'dashboard',
            data: data,
            timestamp: Date.now(),
          });
        }
      } catch (error) {
        console.error('Error fetching dashboard updates:', error);
      }
    };

    // Initial poll
    poll();

    // Set up interval
    const intervalId = setInterval(poll, intervalMs);
    this.pollIntervals.set('dashboard', intervalId);
  }

  /**
   * Stop polling for specific event
   */
  stopUpdates(event: string) {
    const interval = this.pollIntervals.get(event);
    if (interval) {
      clearInterval(interval);
      this.pollIntervals.delete(event);
    }

    if (this.pollIntervals.size === 0) {
      this.isPolling = false;
    }
  }

  /**
   * Stop all polling
   */
  stopAllUpdates() {
    this.pollIntervals.forEach(interval => clearInterval(interval));
    this.pollIntervals.clear();
    this.isPolling = false;
  }

  /**
   * Check if actively polling
   */
  getPollingStatus(): boolean {
    return this.isPolling && this.pollIntervals.size > 0;
  }

  /**
   * Get all registered events
   */
  getRegisteredEvents(): string[] {
    return Array.from(this.listeners.keys());
  }

  /**
   * Clear all listeners for an event
   */
  clearListeners(event: string) {
    this.listeners.delete(event);
  }

  /**
   * Clear all listeners
   */
  clearAllListeners() {
    this.listeners.clear();
  }
}

// Singleton instance
export const realtimeService = new RealtimeService();
export default realtimeService;
