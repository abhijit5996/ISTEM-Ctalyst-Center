import API from "../axios";

// GET all instruments
export const getInstruments = () =>
  API.get('/instruments');

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

// BULK IMPORT instruments from CSV with images
export const importInstruments = (csvFile, images) => {
  const formData = new FormData();
  formData.append("file", csvFile);

  images.forEach((img) => {
    formData.append("images[]", img);
  });

  return API.post("/instruments/import", formData, {
    headers: {
      "Content-Type": "multipart/form-data",
    },
  });
};