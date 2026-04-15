import axios from "axios";

// Determine backend URL based on environment
// For localhost development, use local API URL
// For production, use the deployed API URL
const isDevelopment = window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1';
const BACKEND_URL = isDevelopment 
  ? (import.meta.env.VITE_LOCAL_API_URL || "http://localhost:8000/api")
  : (import.meta.env.VITE_API_URL || "https://istem-ctalyst-center.onrender.com/api");

console.log("🔧 [axios.js] Environment Detection:");
console.log(`  Hostname: ${window.location.hostname}`);
console.log(`  Is Development: ${isDevelopment}`);
console.log(`  Backend URL: ${BACKEND_URL}`);

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
    console.log("🔵 [axios.js] HTTP Request:", config.method?.toUpperCase(), config.url);
    console.log("🔵 [axios.js] Request data:", config.data);
    console.log("🔵 [axios.js] Request headers:", config.headers);
    
    if (typeof window !== "undefined") {
      const token = window.localStorage.getItem("auth_token");
      if (token) {
        config.headers = config.headers || {};
        config.headers.Authorization = `Bearer ${token}`;
        console.log("🔵 [axios.js] Auth token added to headers");
      }
    }
    return config;
  },
  (error) => {
    console.log("🔴 [axios.js] Request error:", error);
    return Promise.reject(error);
  }
);

API.interceptors.response.use(
  (response) => {
    console.log("🟢 [axios.js] HTTP Response:", response.status, response.statusText);
    console.log("🟢 [axios.js] Response data:", response.data);
    return response;
  },
  (error) => {
    console.log("🔴 [axios.js] HTTP Error:", error.response?.status, error.response?.statusText);
    console.log("🔴 [axios.js] Error response data:", error.response?.data);
    console.log("🔴 [axios.js] Error message:", error.message);
    console.log("🔴 [axios.js] Error code:", error.code);
    
    if (error.code === 'ECONNABORTED') {
      console.error("🔴 [axios.js] Request timeout - Server did not respond within 30000ms");
    }
    if (!error.response) {
      console.error("🔴 [axios.js] Network error - possibly CORS issue or server unreachable");
      console.error("🔴 [axios.js] Target URL:", error.config?.url);
    }
    return Promise.reject(error);
  }
);

export default API;