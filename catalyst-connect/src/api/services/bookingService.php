import API from "../axios";

// GET all bookings
export const getBookings = async () => {
  const res = await API.get("/bookings");

  // Backend response shape: { success: true, data: [...] }
  if (res?.data && Array.isArray(res.data.data)) {
    return res.data.data;
  }

  return [];
};

// CREATE booking
export const createBooking = (data) =>
  API.post("/bookings", data);

export const approveBooking = (id) =>
  API.put(`/bookings/${id}/approve`);

export const rejectBooking = (id) =>
  API.put(`/bookings/${id}/reject`);

export const getAdminBookings = async () => {
  const res = await API.get('/admin/bookings');
  if (res?.data?.data && Array.isArray(res.data.data)) {
    return res.data.data;
  }
  return [];
};

export const getAdminDashboard = () =>
  API.get('/admin/dashboard');

export const lockSlot = (data) =>
  API.post('/lock-slot', data);

export const releaseLock = (data) =>
  API.post('/release-lock', data);

// Check availability for a given instrument and date range
export const checkAvailability = (params) =>
  API.get('/check-availability', { params });