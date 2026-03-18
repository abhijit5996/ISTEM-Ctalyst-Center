import API from "../axios";

// GET all instruments
export const getInstruments = (page = 1, limit = 10) =>
  API.get(`/instruments?page=${page}&limit=${limit}`);

// GET single instrument
export const getInstrumentById = (id) =>
  API.get(`/instruments/${id}`);

// CREATE instrument
export const createInstrument = (data) =>
  API.post("/instruments", data);

// UPDATE instrument
export const updateInstrument = (id, data) =>
  API.put(`/instruments/${id}`, data);

// DELETE instrument
export const deleteInstrument = (id) =>
  API.delete(`/instruments/${id}`);