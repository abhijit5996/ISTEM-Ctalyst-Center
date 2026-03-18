import { create } from "zustand";
import { BagItem, BookingRequest, Instrument } from "@/types/instrument";
import { getInstruments } from "@/api/services/instrumentService";
import {
  getBookings,
  createBooking,
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

  approveBooking: (id: string) => void;
  rejectBooking: (id: string) => void;

  joinQueue: (instrumentId: string, userName: string) => Promise<boolean>;

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
      const bookings = await getBookings();
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
        start_date: data.fromDate,
        end_date: data.toDate,
      });

      return res.data?.id || "success";
    } catch (err: any) {
      if (err.response?.data?.message === "slot_unavailable") {
        throw new Error("slot_unavailable");
      }
      throw err;
    }
  },

  approveBooking: (id) =>
    set((state) => ({
      bookingRequests: state.bookingRequests.map((r) =>
        r.id === id ? { ...r, status: "approved" } : r
      ),
    })),

  rejectBooking: (id) =>
    set((state) => ({
      bookingRequests: state.bookingRequests.map((r) =>
        r.id === id ? { ...r, status: "rejected" } : r
      ),
    })),

  joinQueue: async (instrumentId, userName) => {
    try {
      await joinQueueAPI({
        instrument_id: instrumentId,
        user_name: userName,
        email: "user@email.com",
      });

      return true;
    } catch (err) {
      console.error(err);
      return false;
    }
  },

  addInstrument: (instrument) =>
    set((state) => ({ instruments: [...state.instruments, instrument] })),

  deleteInstrument: (id) =>
    set((state) => ({
      instruments: state.instruments.filter((i) => i.id !== id),
    })),

  updateInstrument: (id, updates) =>
    set((state) => ({
      instruments: state.instruments.map((i) =>
        i.id === id ? { ...i, ...updates } : i
      ),
    })),
}));