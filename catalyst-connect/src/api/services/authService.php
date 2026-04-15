import API from "../axios";

// User auth
export const signup = (data) => {
  console.log("🔵 [authService.js] signup() called with data:", { ...data, password: "***" });
  const promise = API.post("/signup", data);
  promise.then((res) => {
    console.log("🟢 [authService.js] signup() response:", res?.data);
  }).catch((err) => {
    console.log("🔴 [authService.js] signup() error:", err?.response?.data);
  });
  return promise;
};

export const login = (data) => API.post("/login", data);
export const sendOtp = (data) => API.post("/send-otp", data);
export const verifyOtp = (data) => API.post("/verify-otp", data);
export const forgotPassword = (data) => API.post("/forgot-password", data);
export const verifyResetOtp = (data) => API.post("/verify-reset-otp", data);
export const resetPassword = (data) => API.post("/reset-password", data);

export const getUserProfile = () => API.get("/user/profile");
export const getUserBookings = () => API.get("/bookings/user");
export const getUserQueue = () => API.get("/queue/user");

// Admin auth
export const adminSignup = (data) => API.post("/admin/signup", data);
export const adminLogin = (data) => API.post("/admin/login", data);
export const getAdminMe = () => API.get("/admin/me");
export const getAdminExists = () => API.get("/admin/exists");

// Google OAuth helper — opens redirect URL
export const redirectToGoogleLogin = () => {
  window.location.href = `${API.defaults.baseURL}/auth/google/redirect`;
};
