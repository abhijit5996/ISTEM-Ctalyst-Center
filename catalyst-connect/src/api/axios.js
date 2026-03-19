import axios from "axios";

const API = axios.create({
  baseURL: "http://localhost:8000/api",
  headers: {
    Accept: "application/json",
  },
  timeout: 30000,
});

API.interceptors.response.use(
  (response) => response,
  (error) => {
    console.error("API response error:", error.response?.status, error.response?.data || error.message);
    return Promise.reject(error);
  }
);

export default API;