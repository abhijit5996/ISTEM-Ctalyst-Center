import { create } from "zustand";
import { BagItem, BookingRequest, Instrument } from "@/types/instrument";
import { getInstruments, createInstrument, deleteInstrument as deleteInstrumentAPI } from "@/api/services/instrumentService";
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
  loadingInstruments: boolean;
  loadingBookings: boolean;
  instrumentsLoaded: boolean;

  fetchInstruments: () => Promise<void>;
  fetchBookings: () => Promise<void>;

  addToBag: (item: BagItem) => void;
  removeFromBag: (instrumentId: string) => void;
  clearBag: () => void;

  submitBooking: (request: Record<string, any>) => Promise<string>;

  approveBooking: (id: string) => Promise<void>;
  rejectBooking: (id: string) => Promise<void>;

  joinQueue: (instrumentId: string, userName: string, email: string) => Promise<boolean>;

  addInstrument: (instrument: Instrument) => void;
  deleteInstrument: (id: string) => void;
  updateInstrument: (id: string, updates: Partial<Instrument>) => void;
}

export const useBookingStore = create<BookingStore>((set, get) => ({
  instruments: [],
  bag: [],
  bookingRequests: [],
  loadingInstruments: false,
  loadingBookings: false,
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
    } catch (err: any) {
      const conflictMessage = err.response?.data?.message;
      if (err.response?.status === 409 || conflictMessage === 'slot_already_booked' || conflictMessage === 'slot_unavailable') {
        throw new Error('slot_unavailable');
      }
      throw err;
    }
  },

  approveBooking: async (id) => {
    try {
      await approveBookingAPI(id);
      await get().fetchBookings();
    } catch (err) {
      console.error("Error approving booking", err);
      throw err;
    }
  },

  rejectBooking: async (id) => {
    try {
      await rejectBookingAPI(id);
      await get().fetchBookings();
    } catch (err) {
      console.error("Error rejecting booking", err);
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
      const created = res?.data || instrument;
      set((state) => ({ instruments: [...state.instruments, created] }));
      return created;
    } catch (err) {
      console.error("Failed to persist instrument", err);
      set((state) => ({ instruments: [...state.instruments, instrument] }));
      return instrument;
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

  updateInstrument: (id, updates) =>
    set((state) => ({
      instruments: state.instruments.map((i) =>
        i.id === id ? { ...i, ...updates } : i
      ),
    })),

}));