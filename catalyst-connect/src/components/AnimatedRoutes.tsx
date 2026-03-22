import { Route, Routes, useLocation } from "react-router-dom";
import { AnimatePresence } from "framer-motion";
import Index from "@/pages/Index";
import NotFound from "@/pages/NotFound";
import InstrumentDetails from "@/pages/InstrumentDetails";
import BookingBag from "@/pages/BookingBag";
import BookingForm from "@/pages/BookingForm";
import BookingConfirmation from "@/pages/BookingConfirmation";
import Login from "@/pages/Login";
import Signup from "@/pages/Signup";
import OTPVerification from "@/pages/OTPVerification";
import ForgotPassword from "@/pages/ForgotPassword";
import ResetPassword from "@/pages/ResetPassword";
import Profile from "@/pages/Profile";
import MyBookings from "@/pages/MyBookings";
import QueueStatus from "@/pages/QueueStatus";
import VerifyResetOTP from "@/pages/VerifyResetOTP";
import AdminLogin from "@/pages/AdminLogin";
import AdminSignup from "@/pages/AdminSignup";
import AdminLayout from "@/pages/admin/AdminLayout";
import AdminDashboard from "@/pages/admin/AdminDashboard";
import AdminInstruments from "@/pages/admin/AdminInstruments";
import AdminBookings from "@/pages/admin/AdminBookings";
import AdminQueue from "@/pages/admin/AdminQueue";
import AdminAnalytics from "@/pages/admin/AdminAnalytics";
import { RequireAdminAuth, RequireUserAuth } from "@/components/RequireAuth";

export function AnimatedRoutes() {
  const location = useLocation();

  return (
    <AnimatePresence mode="wait">
      <Routes location={location} key={location.pathname}>
        <Route path="/" element={<Index />} />
        <Route path="/instrument/:id" element={<InstrumentDetails />} />
        <Route
          path="/bag"
          element={
            <RequireUserAuth>
              <BookingBag />
            </RequireUserAuth>
          }
        />
        <Route
          path="/booking-form"
          element={
            <RequireUserAuth>
              <BookingForm />
            </RequireUserAuth>
          }
        />
        <Route path="/booking-confirmation/:id" element={<BookingConfirmation />} />
        <Route path="/login" element={<Login />} />
        <Route path="/signup" element={<Signup />} />
        <Route path="/verify-otp" element={<OTPVerification />} />
        <Route path="/forgot-password" element={<ForgotPassword />} />
        <Route path="/verify-reset-otp" element={<VerifyResetOTP />} />
        <Route path="/reset-password" element={<ResetPassword />} />
        <Route
          path="/profile"
          element={
            <RequireUserAuth>
              <Profile />
            </RequireUserAuth>
          }
        />
        <Route
          path="/my-bookings"
          element={
            <RequireUserAuth>
              <MyBookings />
            </RequireUserAuth>
          }
        />
        <Route
          path="/queue-status"
          element={
            <RequireUserAuth>
              <QueueStatus />
            </RequireUserAuth>
          }
        />
        <Route path="/admin/login" element={<AdminLogin />} />
        <Route path="/admin/signup" element={<AdminSignup />} />
        <Route
          path="/admin"
          element={
            <RequireAdminAuth>
              <AdminLayout />
            </RequireAdminAuth>
          }
        >
          <Route index element={<AdminDashboard />} />
          <Route path="instruments" element={<AdminInstruments />} />
          <Route path="bookings" element={<AdminBookings />} />
          <Route path="queue" element={<AdminQueue />} />
          <Route path="analytics" element={<AdminAnalytics />} />
        </Route>
        <Route path="*" element={<NotFound />} />
      </Routes>
    </AnimatePresence>
  );
}
