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