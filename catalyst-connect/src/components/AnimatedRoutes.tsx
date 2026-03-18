import { Route, Routes, useLocation } from "react-router-dom";
import { AnimatePresence } from "framer-motion";
import Index from "@/pages/Index";
import NotFound from "@/pages/NotFound";
import InstrumentDetails from "@/pages/InstrumentDetails";
import BookingBag from "@/pages/BookingBag";
import BookingForm from "@/pages/BookingForm";
import BookingConfirmation from "@/pages/BookingConfirmation";
import AdminLayout from "@/pages/admin/AdminLayout";
import AdminDashboard from "@/pages/admin/AdminDashboard";
import AdminInstruments from "@/pages/admin/AdminInstruments";
import AdminBookings from "@/pages/admin/AdminBookings";
import AdminQueue from "@/pages/admin/AdminQueue";
import AdminAnalytics from "@/pages/admin/AdminAnalytics";

export function AnimatedRoutes() {
  const location = useLocation();

  return (
    <AnimatePresence mode="wait">
      <Routes location={location} key={location.pathname}>
        <Route path="/" element={<Index />} />
        <Route path="/instrument/:id" element={<InstrumentDetails />} />
        <Route path="/bag" element={<BookingBag />} />
        <Route path="/booking-form" element={<BookingForm />} />
        <Route path="/booking-confirmation/:id" element={<BookingConfirmation />} />
        <Route path="/admin" element={<AdminLayout />}>
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
