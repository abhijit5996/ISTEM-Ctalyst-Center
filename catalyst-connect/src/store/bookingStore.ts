import { create } from "zustand";
import { BagItem, BookingRequest, Instrument } from "@/types/instrument";
import { getInstruments, createInstrument, updateInstrument as updateInstrumentAPI, deleteInstrument as deleteInstrumentAPI } from "@/api/services/instrumentService";
import {
  getBookings,
  createBooking,
  approveBooking as approveBookingAPI,
  rejectBooking as rejectBookingAPI,
  getAdminBookings,
} from "@/api/services/bookingService";
import { joinQueue as joinQueueAPI } from "@/api/services/queueService";

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

  fetchInstruments: () => Promise<void>;
  fetchBookings: () => Promise<void>;
  fetchDashboard: () => Promise<void>;

  addToBag: (item: BagItem) => void;
  removeFromBag: (instrumentId: string) => void;
  clearBag: () => void;

  submitBooking: (request: Record<string, unknown>) => Promise<string>;

  approveBooking: (id: string) => Promise<void>;
  rejectBooking: (id: string) => Promise<void>;

  joinQueue: (instrumentId: string, userName: string, email: string) => Promise<boolean>;

  addInstrument: (instrument: Instrument | FormData) => Promise<Instrument>;
  deleteInstrument: (id: string) => void;
  updateInstrument: (id: string, updates: Partial<Instrument> | FormData) => Promise<Instrument | null>;
}

export const useBookingStore = create<BookingStore>((set, get) => ({
  instruments: [],
  bag: [],
  bookingRequests: [],
  dashboardData: null,
  loadingInstruments: false,
  loadingBookings: false,
  loadingDashboard: false,
  instrumentsLoaded: false,

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
      const instruments = Array.isArray(data.instruments) ? data.instruments : [];
      const bookings = Array.isArray(data.bookings) ? data.bookings : [];
      const stats = data.stats || {
        total_instruments: instruments.length,
        total_bookings: bookings.length,
        pending_requests: bookings.filter((b) => b.status === "pending").length,
        approved_bookings: bookings.filter((b) => b.status === "approved").length,
      };

      set({ dashboardData: { instruments, bookings, stats } });
    } catch (err) {
      console.error("Error fetching dashboard", err);
      set({ dashboardData: null });
    } finally {
      set({ loadingDashboard: false });
    }
  },

  // ✅ FIXED FETCH
  fetchInstruments: async () => {
    set({ loadingInstruments: true });

    try {
      const res = await getInstruments();

      console.log("API RESPONSE:", res.data); // DEBUG

      set({
        instruments: Array.isArray(res.data) ? res.data : [],
        instrumentsLoaded: true,
      });
    } catch (err) {
      console.error("Error fetching instruments", err);
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
      const res = await createBooking({
        instrument_id: data.instrumentId,
        name: data.name,
        email: data.email,
        start_date: data.fromDate,
        end_date: data.toDate,
        user_type: data.userType,
        identifier: data.enrollmentNumber || data.employeeId || null,
        department: data.department,
        program_or_school: data.program || data.school || null,
        project_title: data.projectTitle || null,
        confidential_project: data.isConfidential || false,
      });

      if (res?.data?.data) {
        set((state) => ({
          bookingRequests: [...state.bookingRequests, res.data.data],
        }));
      }

      return res.data?.data?.id || 'success';
    } catch (err: unknown) {
      const axiosError = err as { response?: { status?: number; data?: { message?: string } } };
      const conflictMessage = axiosError.response?.data?.message;
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

  joinQueue: async (instrumentId, userName, email) => {
    try {
      await joinQueueAPI({
        instrument_id: instrumentId,
        user_name: userName,
        email: email,
      });

      return true;
    } catch (err) {
      console.error(err);
      return false;
    }
  },

  addInstrument: async (instrument) => {
    try {
      const res = await createInstrument(instrument);
      const created: Instrument = res?.data || (instrument as Instrument);
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
        const updated = res?.data;
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

}));