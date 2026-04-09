import axios from "axios";

// Determine backend URL based on environment
const BACKEND_URL = import.meta.env.VITE_API_URL || "http://localhost:8000/api";

const API = axios.create({
  baseURL: BACKEND_URL,
  headers: {
    Accept: "application/json",
  },
  timeout: 30000,
  withCredentials: false,
});

API.interceptors.request.use(
  (config) => {
    if (typeof window !== "undefined") {
      const token = window.localStorage.getItem("auth_token");
      if (token) {
        config.headers = config.headers || {};
        config.headers.Authorization = `Bearer ${token}`;
      }
    }
    return config;
  },
  (error) => Promise.reject(error),
);

API.interceptors.response.use(
  (response) => response,
  (error) => {
    console.error("API response error:", error.response?.status, error.response?.data || error.message);
    return Promise.reject(error);
  }
);

export default API;