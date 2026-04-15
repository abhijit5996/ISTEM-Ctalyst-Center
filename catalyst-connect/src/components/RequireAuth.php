import { Navigate, useLocation } from "react-router-dom";
import { ReactNode } from "react";
import { useBookingStore } from "@/store/bookingStore";

export function RequireUserAuth({ children }: { children: ReactNode }) {
  const isAuthenticated = useBookingStore((s) => s.isAuthenticated);
  const otpVerified = useBookingStore((s) => s.otpVerified);
  const location = useLocation();

  if (!isAuthenticated || !otpVerified) {
    return <Navigate to="/login" state={{ from: location }} replace />;
  }

  return <>{children}</>;
}

export function RequireAdminAuth({ children }: { children: ReactNode }) {
  const isAdmin = useBookingStore((s) => s.isAdmin);
  const location = useLocation();

  if (!isAdmin) {
    return <Navigate to="/admin/login" state={{ from: location }} replace />;
  }

  return <>{children}</>;
}
