import { create } from "zustand";
import { BagItem, BookingRequest, Instrument } from "@/types/instrument";
import { getInstruments, createInstrument, updateInstrument as updateInstrumentAPI, deleteInstrument as deleteInstrumentAPI } from "@/api/services/instrumentService";
import {
  getBookings,
  createBooking,
  approveBooking as approveBookingAPI,
  rejectBooking as rejectBookingAPI,
  getAdminBookings,
  getAdminDashboard,
  lockSlot as lockSlotAPI,
  releaseLock as releaseLockAPI,
} from "@/api/services/bookingService";
import { joinQueue as joinQueueAPI } from "@/api/services/queueService";
import { realtimeService } from "@/services/realtimeService";

interface AuthUser {
  id: number | string;
  name: string;
  email: string;
  phone?: string | null;
  profile_picture?: string | null;
  google_id?: string | null;
}

interface BookingStore {
  instruments: Instrument[];
  bag: BagItem[];
  bookingRequests: BookingRequest[];
  dashboardData: {
    instruments: Instrument[];
    bookings: BookingRequest[];
    stats: Record<string, number>;
  } | null;
  loadingInstruments: boolean;
  loadingBookings: boolean;
  loadingDashboard: boolean;
  instrumentsLoaded: boolean;
  realtimeEnabled: boolean;

  // Auth state
  user: AuthUser | null;
  token: string | null;
  isAuthenticated: boolean;
  isAdmin: boolean;
  otpVerified: boolean;

  fetchInstruments: () => Promise<void>;
  fetchBookings: () => Promise<void>;
  fetchDashboard: () => Promise<void>;

  addToBag: (item: BagItem) => void;
  removeFromBag: (instrumentId: string) => void;
  clearBag: () => void;

  submitBooking: (request: Record<string, unknown>) => Promise<string>;

  approveBooking: (id: string) => Promise<void>;
  rejectBooking: (id: string) => Promise<void>;

  joinQueue: (instrumentId: string, userName: string, email: string, fromDate: string, toDate: string) => Promise<boolean>;
  lockSlot: (data: { instrument_id: string; start_date: string; end_date: string; email: string; }) => Promise<boolean>;
  releaseLock: (data: { instrument_id: string; email: string; }) => Promise<void>;

  addInstrument: (instrument: Instrument | FormData) => Promise<Instrument>;
  deleteInstrument: (id: string) => void;
  updateInstrument: (id: string, updates: Partial<Instrument> | FormData) => Promise<Instrument | null>;

  // Real-time update methods
  startRealtimeUpdates: () => void;
  stopRealtimeUpdates: () => void;
  updateBookingsFromRealtime: (bookings: BookingRequest[]) => void;
  updateDashboardFromRealtime: (data: any) => void;

  // Auth actions
  setAuthState: (data: { user: AuthUser | null; token: string | null; isAdmin?: boolean; otpVerified?: boolean }) => void;
  logout: () => void;
  markAdmin: (isAdmin: boolean) => void;
}

const getInitialAuthState = (): Pick<BookingStore, "user" | "token" | "isAuthenticated" | "isAdmin" | "otpVerified"> => {
  if (typeof window === "undefined") {
    return { user: null, token: null, isAuthenticated: false, isAdmin: false, otpVerified: false };
  }

  try {
    const rawUser = window.localStorage.getItem("auth_user");
    const token = window.localStorage.getItem("auth_token");
    const isAdmin = window.localStorage.getItem("is_admin") === "true";
    const otpVerified = window.localStorage.getItem("otp_verified") === "true";
    const user = rawUser ? (JSON.parse(rawUser) as AuthUser) : null;

    return {
      user,
      token: token || null,
      isAuthenticated: !!token,
      isAdmin,
      otpVerified,
    };
  } catch {
    return { user: null, token: null, isAuthenticated: false, isAdmin: false, otpVerified: false };
  }
};

export const useBookingStore = create<BookingStore>((set, get) => ({
  instruments: [],
  bag: [],
  bookingRequests: [],
  dashboardData: null,
  loadingInstruments: false,
  loadingBookings: false,
  loadingDashboard: false,
  instrumentsLoaded: false,
  realtimeEnabled: false,
  ...getInitialAuthState(),

  fetchBookings: async () => {
    set({ loadingBookings: true });

    try {
      const bookings = await getAdminBookings();
      console.log("BOOKINGS API:", bookings);

      set({
        bookingRequests: Array.isArray(bookings) ? bookings : [],
      });
    } catch (err) {
      console.error("Error fetching bookings", err);
      set({ bookingRequests: [] });
    } finally {
      set({ loadingBookings: false });
    }
  },

  fetchDashboard: async () => {
    set({ loadingDashboard: true });

    try {
      const res = await getAdminDashboard();
      const root = res?.data || {};
      const data = root?.data || {};
      const instruments = Array.isArray(data.instruments) ? data.instruments : get().instruments;
      const bookings = Array.isArray(data.bookings) ? data.bookings : get().bookingRequests;
      const stats = data.stats || {
        total_instruments: instruments.length,
        total_bookings: bookings.length,
        pending: bookings.filter((b) => b.status === "pending").length,
        approved: bookings.filter((b) => b.status === "approved").length,
        rejected: bookings.filter((b) => b.status === "rejected").length,
      };

      set({ dashboardData: { instruments, bookings, stats } });
    } catch (err) {
      console.error("Error fetching dashboard", err);
      const instruments = get().instruments;
      const bookings = get().bookingRequests;
      const stats = {
        total_instruments: instruments.length,
        total_bookings: bookings.length,
        pending: bookings.filter((b) => b.status === "pending").length,
        approved: bookings.filter((b) => b.status === "approved").length,
        rejected: bookings.filter((b) => b.status === "rejected").length,
      };
      set({ dashboardData: { instruments, bookings, stats } });
    } finally {
      set({ loadingDashboard: false });
    }
  },

  // ✅ FIXED FETCH
  fetchInstruments: async () => {
    set({ loadingInstruments: true });

    try {
      const res = await getInstruments();

      console.log("API RESPONSE:", res); // DEBUG

      // API returns { success, data: [...] } format
      const instrumentsData = res?.data?.data || res?.data || [];
      const validInstruments = Array.isArray(instrumentsData) ? instrumentsData : [];

      set({
        instruments: validInstruments,
        instrumentsLoaded: true,
      });

      console.log("Instruments loaded:", validInstruments.length);
    } catch (err) {
      console.error("Error fetching instruments", err);
      set({ instrumentsLoaded: true });
    } finally {
      set({ loadingInstruments: false });
    }
  },

  addToBag: (item) =>
    set((state) => {
      if (state.bag.find((b) => b.instrument.id === item.instrument.id)) return state;
      return { bag: [...state.bag, item] };
    }),

  removeFromBag: (instrumentId) =>
    set((state) => ({
      bag: state.bag.filter((b) => b.instrument.id !== instrumentId),
    })),

  clearBag: () => set({ bag: [] }),

  submitBooking: async (data) => {
    try {
      const payload = {
        instrument_id: data.instrumentId,
        name: data.name,
        email: data.email,
        start_date: data.fromDate,
        end_date: data.toDate,
        user_type: data.userType,
        identifier: data.enrollmentNumber || data.employeeId || "N/A",
        department: data.department || "N/A",
        program_or_school: data.program || data.school || "N/A",
        project_title: data.projectTitle != null ? String(data.projectTitle) : "N/A",
        confidential_project: Boolean(data.isConfidential),
      };
      
      console.log("🔵 [bookingStore] submitBooking payload:", payload);
      const res = await createBooking(payload);
      console.log("🟢 [bookingStore] submitBooking response:", res?.data);
      console.log("🟢 [bookingStore] response.data.data:", res?.data?.data);
      console.log("🟢 [bookingStore] response.data.data.id:", res?.data?.data?.id);

      if (res?.data?.data) {
        set((state) => ({
          bookingRequests: [...state.bookingRequests, res.data.data],
        }));
      }

      const requestId = res.data?.data?.id;
      console.log("🟢 [bookingStore] Extracted requestId:", requestId);
      return requestId || 'success';
    } catch (err: unknown) {
      console.error("🔴 [bookingStore] submitBooking error:", err);
      const axiosError = err as { response?: { status?: number; data?: { message?: string } } };
      const conflictMessage = axiosError.response?.data?.message;
      console.error("🔴 [bookingStore] error status:", axiosError.response?.status, "message:", conflictMessage);
      if (axiosError.response?.status === 409 || conflictMessage === 'slot_already_booked' || conflictMessage === 'slot_unavailable') {
        throw new Error('slot_unavailable');
      }
      throw err;
    }
  },

  approveBooking: async (id) => {
    const previous = get().bookingRequests;

    set((state) => ({
      bookingRequests: state.bookingRequests.map((b) =>
        b.id === id ? { ...b, status: 'approved' } : b
      ),
    }));

    try {
      await approveBookingAPI(id);

      // REMOVE FROM BAG AFTER APPROVAL
      set((state) => ({
        bag: state.bag.filter((b) => b.instrument.id !== id),
      }));

      // ✅ PARALLEL FETCH (FAST)
      Promise.all([get().fetchBookings(), get().fetchInstruments(), get().fetchDashboard()]);

    } catch (err) {
      console.error("Error approving booking", err);
      set({ bookingRequests: previous });
      throw err;
    }
  },

  rejectBooking: async (id) => {
    const previous = get().bookingRequests;
    set((state) => ({
      bookingRequests: state.bookingRequests.map((b) =>
        b.id === id ? { ...b, status: 'rejected' } : b
      ),
    }));

    try {
      await rejectBookingAPI(id);
      await get().fetchBookings();
      await get().fetchInstruments();
      await get().fetchDashboard();
    } catch (err) {
      console.error("Error rejecting booking", err);
      set({ bookingRequests: previous });
      throw err;
    }
  },

  joinQueue: async (instrumentId, userName, email, fromDate, toDate) => {
    try {
      await joinQueueAPI({
        instrument_id: instrumentId,
        user_name: userName,
        email: email,
        start_date: fromDate,
        end_date: toDate,
      });

      return true;
    } catch (err: unknown) {
      const axiosError = err as { response?: { status?: number; data?: { message?: string } } };

      // If the user is already in the queue for this slot,
      // treat it as a successful state instead of a hard failure.
      if (
        axiosError.response?.status === 409 &&
        axiosError.response?.data?.message === "already_in_queue"
      ) {
        return true;
      }

      console.error("joinQueue failed", err);
      return false;
    }
  },

  lockSlot: async (data) => {
    try {
      await lockSlotAPI(data);
      return true;
    } catch (err: unknown) {
      const axiosError = err as { response?: { status?: number; data?: { message?: string } } };
      if (axiosError.response?.status === 409) {
        console.warn('Slot already locked or booked', axiosError.response?.data);
        return false;
      }
      console.error('Lock failed', err);
      return false;
    }
  },

  releaseLock: async (data) => {
    try {
      await releaseLockAPI(data);
    } catch (err) {
      console.error('Release lock failed', err);
    }
  },

  addInstrument: async (instrument) => {
    try {
      const res = await createInstrument(instrument);
      const created: Instrument = res?.data?.data || res?.data || (instrument as Instrument);
      set((state) => ({ instruments: [...state.instruments, created] }));
      return created;
    } catch (err) {
      console.error("Failed to persist instrument", err);
      const fallback = instrument instanceof FormData ? null : (instrument as Instrument);
      if (fallback) {
        set((state) => ({ instruments: [...state.instruments, fallback] }));
      }
      return fallback;
    }
  },

  deleteInstrument: async (id) => {
    try {
      await deleteInstrumentAPI(id);
      set((state) => ({ instruments: state.instruments.filter((i) => i.id !== id) }));
    } catch (err) {
      console.error("Failed to delete instrument", err);
      set((state) => ({ instruments: state.instruments.filter((i) => i.id !== id) }));
    }
  },

  updateInstrument: async (id, updates) => {
    if (updates instanceof FormData) {
      try {
        const res = await updateInstrumentAPI(id, updates);
        const updated = res?.data?.data || res?.data;
        if (updated) {
          set((state) => ({
            instruments: state.instruments.map((i) =>
              i.id === id ? { ...i, ...updated } : i
            ),
          }));
          return updated;
        }
      } catch (err) {
        console.error("Failed to update instrument", err);
      }
      return null;
    }

    set((state) => ({
      instruments: state.instruments.map((i) =>
        i.id === id ? { ...i, ...updates } : i
      ),
    }));

    return get().instruments.find((i) => i.id === id) ?? null;
  },

  // ===== Real-time Update Methods =====
  startRealtimeUpdates: () => {
    set({ realtimeEnabled: true });

    // Subscribe to booking updates
    realtimeService.subscribe('booking', (update) => {
      const bookings = Array.isArray(update.data) ? update.data : [];
      get().updateBookingsFromRealtime(bookings);
    });

    // Subscribe to dashboard updates
    realtimeService.subscribe('dashboard', (update) => {
      const data = update.data || {};
      get().updateDashboardFromRealtime(data);
    });

    // Start polling
    realtimeService.startBookingUpdates(3000);
    realtimeService.startDashboardUpdates(5000);
  },

  stopRealtimeUpdates: () => {
    set({ realtimeEnabled: false });
    realtimeService.stopAllUpdates();
    realtimeService.clearAllListeners();
  },

  updateBookingsFromRealtime: (bookings: BookingRequest[]) => {
    if (!Array.isArray(bookings)) return;

    set((state) => {
      // Check if there are new or updated bookings
      const hasChanges = bookings.length !== state.bookingRequests.length ||
        bookings.some((b) => !state.bookingRequests.find((r) => r.id === b.id));

      if (hasChanges) {
        return {
          bookingRequests: bookings,
        };
      }
      return state;
    });
  },

  updateDashboardFromRealtime: (data: any) => {
    if (!data) return;

    set((state) => {
      // Only update instruments if they're provided in the response
      let instruments = state.instruments;
      let bookings = state.bookingRequests;

      if (Array.isArray(data.instruments) && data.instruments.length > 0) {
        instruments = data.instruments;
      } else if (state.dashboardData?.instruments) {
        instruments = state.dashboardData.instruments;
      }

      if (Array.isArray(data.bookings) && data.bookings.length > 0) {
        bookings = data.bookings;
      } else if (state.dashboardData?.bookings) {
        bookings = state.dashboardData.bookings;
      }

      const stats = data.stats || {
        total_instruments: instruments.length,
        total_bookings: bookings.length,
        pending: bookings.filter((b: any) => b.status === "pending").length,
        approved: bookings.filter((b: any) => b.status === "approved").length,
        rejected: bookings.filter((b: any) => b.status === "rejected").length,
      };

      return {
        dashboardData: { instruments, bookings, stats },
      };
    });
  },

  // ===== Auth helpers =====
  setAuthState: ({ user, token, isAdmin, otpVerified }) => {
    const next: Partial<BookingStore> = {
      user,
      token,
      isAuthenticated: !!token,
    };

    if (typeof isAdmin === "boolean") {
      next.isAdmin = isAdmin;
    }
    if (typeof otpVerified === "boolean") {
      next.otpVerified = otpVerified;
    }

    set(next as any);

    if (typeof window !== "undefined") {
      if (token) {
        window.localStorage.setItem("auth_token", token);
      } else {
        window.localStorage.removeItem("auth_token");
      }

      if (user) {
        window.localStorage.setItem("auth_user", JSON.stringify(user));
      } else {
        window.localStorage.removeItem("auth_user");
      }

      if (typeof isAdmin === "boolean") {
        window.localStorage.setItem("is_admin", isAdmin ? "true" : "false");
      }

      if (typeof otpVerified === "boolean") {
        window.localStorage.setItem("otp_verified", otpVerified ? "true" : "false");
      }
    }
  },

  logout: () => {
    set({ user: null, token: null, isAuthenticated: false, isAdmin: false, otpVerified: false });
    if (typeof window !== "undefined") {
      window.localStorage.removeItem("auth_token");
      window.localStorage.removeItem("auth_user");
      window.localStorage.removeItem("is_admin");
      window.localStorage.removeItem("otp_verified");
    }
  },

  markAdmin: (isAdmin) => {
    set({ isAdmin });
    if (typeof window !== "undefined") {
      window.localStorage.setItem("is_admin", isAdmin ? "true" : "false");
    }
  },

}));