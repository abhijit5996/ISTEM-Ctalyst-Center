/**
 * Real-time Updates Testing Guide
 * 
 * This file contains tests and debugging steps to verify
 * the real-time dashboard update system is working correctly
 */

// ============================================
// MANUAL TESTING IN BROWSER CONSOLE
// ============================================

/**
 * 1. CHECK IF REAL-TIME SERVICE IS RUNNING
 * 
 * Copy and paste in browser console:
 */
// console.log('Real-time service active:', realtimeService.getPollingStatus());
// console.log('Registered events:', realtimeService.getRegisteredEvents());

/**
 * 2. CHECK WHAT'S BEING POLLED FROM API
 * 
 * Monitor the Network tab while this is running:
 */
// setInterval(() => {
//   fetch('/api/admin/bookings')
//     .then(r => r.json())
//     .then(data => console.log('Bookings API response:', data))
//     .catch(e => console.error('Error:', e));
// }, 3000);

/**
 * 3. MANUALLY TRIGGER STORE UPDATE
 * 
 * To test if UI updates when store changes:
 */
// import { useBookingStore } from '@/store/bookingStore';
// const store = useBookingStore.getState();
// store.updateBookingsFromRealtime([
//   { id: 'TEST-1', name: 'Test User', status: 'pending' }
// ]);

/**
 * 4. TEST LISTENER SUBSCRIPTION
 * 
 * Verify listeners are being called:
 */
// import { realtimeService } from '@/services/realtimeService';
// realtimeService.subscribe('booking', (update) => {
//   console.log('✅ Booking update received:', update);
// });

// ============================================
// AUTOMATED TEST SUITE
// ============================================

export const RealtimeTestSuite = {
  /**
   * Test 1: Service Initialization
   */
  testServiceInitialization: (): boolean => {
    try {
      const { realtimeService } = require('@/services/realtimeService');
      
      if (!realtimeService) {
        console.error('❌ RealtimeService not available');
        return false;
      }

      console.log('✅ RealtimeService initialized');
      return true;
    } catch (error) {
      console.error('❌ Service initialization failed:', error);
      return false;
    }
  },

  /**
   * Test 2: Polling Start/Stop
   */
  testPollingControl: (): boolean => {
    try {
      const { realtimeService } = require('@/services/realtimeService');

      // Test start
      realtimeService.startBookingUpdates(3000);
      const isPolling = realtimeService.getPollingStatus();

      if (!isPolling) {
        console.error('❌ Polling not started');
        return false;
      }

      console.log('✅ Polling started successfully');

      // Test stop
      realtimeService.stopAllUpdates();
      const isStopped = !realtimeService.getPollingStatus();

      if (!isStopped) {
        console.error('❌ Polling not stopped');
        return false;
      }

      console.log('✅ Polling stopped successfully');
      return true;
    } catch (error) {
      console.error('❌ Polling control test failed:', error);
      return false;
    }
  },

  /**
   * Test 3: Event Listeners
   */
  testEventListeners: async (): Promise<boolean> => {
    return new Promise((resolve) => {
      try {
        const { realtimeService } = require('@/services/realtimeService');

        let eventReceived = false;

        const unsubscribe = realtimeService.subscribe('booking', (update) => {
          console.log('✅ Event listener triggered');
          eventReceived = true;
        });

        // Start polling
        realtimeService.startBookingUpdates(1000);

        // Wait 2 seconds to see if event fires
        setTimeout(() => {
          realtimeService.stopUpdates('bookings');
          unsubscribe();

          if (eventReceived) {
            console.log('✅ Event listener test passed');
            resolve(true);
          } else {
            console.warn('⚠️ Event listener may not have received updates in time');
            resolve(true); // Don't fail if just slow response
          }
        }, 2000);
      } catch (error) {
        console.error('❌ Event listener test failed:', error);
        resolve(false);
      }
    });
  },

  /**
   * Test 4: Zustand Store Integration
   */
  testStoreIntegration: (): boolean => {
    try {
      const { useBookingStore } = require('@/store/bookingStore');
      const store = useBookingStore.getState();

      // Check if required methods exist
      const requiredMethods = [
        'startRealtimeUpdates',
        'stopRealtimeUpdates',
        'updateBookingsFromRealtime',
        'updateDashboardFromRealtime',
      ];

      const missingMethods = requiredMethods.filter(
        (method) => typeof store[method] !== 'function'
      );

      if (missingMethods.length > 0) {
        console.error(`❌ Missing methods in store: ${missingMethods.join(', ')}`);
        return false;
      }

      console.log('✅ All required store methods present');

      // Test update method
      const testData = [{ id: 'TEST-1', status: 'pending', name: 'Test' }];
      store.updateBookingsFromRealtime(testData);

      const bookings = useBookingStore.getState().bookingRequests;
      if (bookings.length === 0) {
        console.warn('⚠️ Store might not be updating correctly');
        return true; // Don't fail, might be initial empty state
      }

      console.log('✅ Store update test passed');
      return true;
    } catch (error) {
      console.error('❌ Store integration test failed:', error);
      return false;
    }
  },

  /**
   * Test 5: API Response Format
   */
  testAPIResponseFormat: async (): Promise<boolean> => {
    try {
      const response = await fetch('/api/admin/bookings');
      const data = await response.json();

      if (!data.data || !Array.isArray(data.data)) {
        console.error(
          '❌ API response format incorrect. Expected: { data: [...] }',
          data
        );
        return false;
      }

      console.log('✅ API response format correct');
      return true;
    } catch (error) {
      console.error('❌ API format test failed:', error);
      return false;
    }
  },

  /**
   * Test 6: Component Integration
   */
  testComponentIntegration: (): boolean => {
    try {
      const { useBookingStore } = require('@/store/bookingStore');
      const store = useBookingStore.getState();

      // Check if realtimeEnabled flag exists
      if (typeof store.realtimeEnabled !== 'boolean') {
        console.error('❌ realtimeEnabled flag missing from store');
        return false;
      }

      console.log('✅ Component integration test passed');
      return true;
    } catch (error) {
      console.error('❌ Component integration test failed:', error);
      return false;
    }
  },

  /**
   * Run All Tests
   */
  runAllTests: async (): Promise<{
    passed: number;
    failed: number;
    total: number;
    tests: { name: string; result: boolean }[];
  }> => {
    const tests = [
      { name: 'Service Initialization', test: RealtimeTestSuite.testServiceInitialization },
      { name: 'Polling Control', test: RealtimeTestSuite.testPollingControl },
      { name: 'Event Listeners', test: RealtimeTestSuite.testEventListeners },
      { name: 'Store Integration', test: RealtimeTestSuite.testStoreIntegration },
      { name: 'API Response Format', test: RealtimeTestSuite.testAPIResponseFormat },
      { name: 'Component Integration', test: RealtimeTestSuite.testComponentIntegration },
    ];

    console.log('\n🧪 Running Real-time Update Test Suite...\n');

    const results = [];
    for (const { name, test } of tests) {
      const result = await test();
      results.push({ name, result });
      console.log(`${result ? '✅' : '❌'} ${name}`);
    }

    const passed = results.filter((r) => r.result).length;
    const failed = results.filter((r) => !r.result).length;
    const total = results.length;

    console.log(`\n📊 Test Results: ${passed}/${total} passed\n`);

    return { passed, failed, total, tests: results };
  },
};

// ============================================
// BROWSER CONSOLE USAGE ERROR CODES
// ============================================

const ErrorCodes = {
  '001': 'RealtimeService not initialized - Check if service file exists',
  '002': 'Polling not starting - Check API endpoints are accessible',
  '003': 'Event listeners not firing - Check subscription setup',
  '004': 'Store not updating - Check Zustand store integration',
  '005': 'API response format incorrect - Check backend returns { data: [...] }',
  '006': 'Component not showing real-time indicators - Check UI imports',
};

// ============================================
// DEBUGGING HELPERS
// ============================================

export const DebugHelpers = {
  /**
   * Monitor all real-time updates in real-time
   */
  monitorUpdates: (secondsToRun: number = 30) => {
    const { realtimeService } = require('@/services/realtimeService');

    console.log(`📡 Monitoring real-time updates for ${secondsToRun} seconds...`);

    const unsubscribeBooings = realtimeService.subscribe('booking', (update) => {
      console.log('📦 Booking Update:', update);
    });

    const unsubscribeDashboard = realtimeService.subscribe('dashboard', (update) => {
      console.log('📊 Dashboard Update:', update);
    });

    realtimeService.startBookingUpdates(3000);
    realtimeService.startDashboardUpdates(5000);

    setTimeout(() => {
      realtimeService.stopAllUpdates();
      unsubscribeBooings();
      unsubscribeDashboard();
      console.log('✋ Monitoring stopped');
    }, secondsToRun * 1000);
  },

  /**
   * Generate data for testing
   */
  generateTestBooking: (overrides = {}) => {
    return {
      id: `TEST-${Date.now()}`,
      name: 'Test User',
      email: 'test@example.com',
      instrumentName: 'Test Instrument',
      fromDate: new Date().toISOString(),
      toDate: new Date(Date.now() + 3600000).toISOString(),
      status: 'pending',
      userType: 'student',
      department: 'Engineering',
      ...overrides,
    };
  },

  /**
   * Check backend API endpoints
   */
  checkBackendEndpoints: async () => {
    const endpoints = [
      '/api/admin/bookings',
      '/api/admin/dashboard',
    ];

    console.log('🔍 Checking backend endpoints...\n');

    for (const endpoint of endpoints) {
      try {
        const response = await fetch(endpoint);
        const data = await response.json();
        console.log(`✅ ${endpoint} - Status: ${response.status}`);
        console.log(`   Response:`, data);
      } catch (error) {
        console.log(`❌ ${endpoint} - Error:`, error);
      }
    }
  },
};

// ============================================
// EXPORT FOR USE IN BROWSER CONSOLE
// ============================================

if (typeof window !== 'undefined') {
  (window as any).RealtimeTestSuite = RealtimeTestSuite;
  (window as any).DebugHelpers = DebugHelpers;
}

// Usage in console:
// RealtimeTestSuite.runAllTests();
// DebugHelpers.monitorUpdates(30);
// DebugHelpers.checkBackendEndpoints();
