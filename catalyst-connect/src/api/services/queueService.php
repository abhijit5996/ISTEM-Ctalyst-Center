import API from "../axios";

// User-side join queue (modern endpoint)
export const joinQueue = (data) =>
  API.post("/queue/join", data);

// Legacy / per-instrument queue list
export const getQueue = (instrumentId) =>
  API.get(`/queue/${instrumentId}`);

// Admin queue endpoints
export const getAdminQueue = () =>
  API.get("/admin/queue");

export const approveQueueEntry = (id) =>
  API.post("/admin/queue/approve", { id });

export const rejectQueueEntry = (id) =>
  API.post("/admin/queue/reject", { id });