import { useBookingStore } from "@/store/bookingStore";
import { toast } from "sonner";
import { useState, useEffect } from "react";
import { Radio } from "lucide-react";
import { motion } from "framer-motion";
import { fadeInUp, staggerContainer } from "@/components/PageTransition";
import BookingApprovalCard, { AdminBooking } from "@/components/admin/BookingApprovalCard";

const AdminBookings = () => {
  const bookingRequests = useBookingStore((s) => s.bookingRequests) as AdminBooking[];
  const realtimeEnabled = useBookingStore((s) => s.realtimeEnabled);
  const approveBooking = useBookingStore((s) => s.approveBooking);
  const rejectBooking = useBookingStore((s) => s.rejectBooking);
  const startRealtimeUpdates = useBookingStore((s) => s.startRealtimeUpdates);
  const stopRealtimeUpdates = useBookingStore((s) => s.stopRealtimeUpdates);
  const [loadingBookingId, setLoadingBookingId] = useState<string | null>(null);

  // Initialize real-time updates on mount
  useEffect(() => {
    startRealtimeUpdates();

    return () => {
      stopRealtimeUpdates();
    };
  }, [startRealtimeUpdates, stopRealtimeUpdates]);

  const handleApprove = async (id: string) => {
    setLoadingBookingId(id);
    try {
      await approveBooking(id);
      toast.success("Booking approved and confirmation email sent.");
    } catch (err) {
      console.error("approveBooking failed", err);
      toast.error(`Failed to approve ${id}.`);
    } finally {
      setLoadingBookingId(null);
    }
  };

  const handleReject = async (id: string) => {
    setLoadingBookingId(id);
    try {
      await rejectBooking(id);
      toast.success("Booking rejected and requester notified.");
    } catch (err) {
      console.error("rejectBooking failed", err);
      toast.error(`Failed to reject ${id}.`);
    } finally {
      setLoadingBookingId(null);
    }
  };

  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between gap-4">
        <div>
          <h1 className="text-lg font-semibold tracking-tight">Booking Approvals</h1>
          <p className="text-sm text-muted-foreground">
            Review and manage instrument booking requests in real time.
          </p>
        </div>
        <div className="flex items-center gap-2 text-xs text-muted-foreground">
          <Radio
            className={`h-3 w-3 ${
              realtimeEnabled ? "text-emerald-500" : "text-muted-foreground/50"
            }`}
            fill={realtimeEnabled ? "currentColor" : "none"}
          />
          <span>{realtimeEnabled ? "Live Updates Active" : "Live Updates Paused"}</span>
        </div>
      </div>

      {bookingRequests.length === 0 ? (
        <p className="text-sm text-muted-foreground">
          No booking requests at the moment.
        </p>
      ) : (
        <motion.div
          className="space-y-4"
          variants={staggerContainer}
          initial="initial"
          animate="animate"
        >
          {bookingRequests.map((booking) => (
            <motion.div key={booking.id} variants={fadeInUp}>
              <BookingApprovalCard
                booking={booking}
                onApprove={handleApprove}
                onReject={handleReject}
                isProcessing={loadingBookingId === booking.id}
              />
            </motion.div>
          ))}
        </motion.div>
      )}
    </div>
  );
};

export default AdminBookings;
