import API from "../axios";

export const joinQueue = (data) =>
  API.post("/queue", data);

export const getQueue = (instrumentId) =>
  API.get(`/queue/${instrumentId}`);