// API placeholder functions — replace with real endpoints later
import type { Instrument } from "@/types/instrument";

const delay = (ms: number) => new Promise((r) => setTimeout(r, ms));

export const api = {
  getInstruments: async (): Promise<Instrument[]> => {
    await delay(200);
    return [];
  },

  getInstrumentById: async (id: string): Promise<Instrument | undefined> => {
    await delay(100);
    return instruments.find((i) => i.id === id);
  },

  // POST /api/bookings
  submitBooking: async (data: unknown): Promise<{ success: boolean; id: string }> => {
    await delay(300);
    return { success: true, id: `REQ-${Date.now()}` };
  },

  // GET /api/bookings
  getBookings: async () => {
    await delay(200);
    return [];
  },

  // POST /api/admin/instrument
  addInstrument: async (data: unknown): Promise<{ success: boolean }> => {
    await delay(300);
    return { success: true };
  },
};
