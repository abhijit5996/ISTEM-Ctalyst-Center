import { useParams, Link } from "react-router-dom";
import { MainLayout } from "@/components/layout/MainLayout";
import { useBookingStore } from "@/store/bookingStore";
import { Button } from "@/components/ui/button";
import { CheckCircle, Mail } from "lucide-react";
import { PageTransition, fadeInUp, staggerContainer } from "@/components/PageTransition";
import { motion } from "framer-motion";

const BookingConfirmation = () => {
  const { id } = useParams<{ id: string }>();
  const bookingRequests = useBookingStore((s) => s.bookingRequests);
  const request = bookingRequests.find((r) => r.id === id);

  return (
    <MainLayout>
      <PageTransition>
        <motion.div
          className="container py-20 max-w-lg text-center space-y-6"
          variants={staggerContainer}
          initial="initial"
          animate="animate"
        >
          <motion.div
            initial={{ scale: 0, opacity: 0 }}
            animate={{ scale: 1, opacity: 1 }}
            transition={{ type: "spring", stiffness: 200, damping: 15, delay: 0.2 }}
            className="inline-flex items-center justify-center h-16 w-16 rounded-full bg-status-available/10 text-status-available mx-auto"
          >
            <CheckCircle className="h-8 w-8" />
          </motion.div>

          <motion.div variants={fadeInUp} className="space-y-2">
            <h1 className="text-2xl font-bold">Booking Request Submitted</h1>
            <p className="text-sm text-muted-foreground">
              Your booking request has been submitted successfully. Admin approval is required before slot confirmation.
            </p>
          </motion.div>

          {id && (
            <motion.div variants={fadeInUp} className="bg-card card-shadow rounded-lg p-4 text-left space-y-3">
              <div className="flex justify-between text-sm">
                <span className="text-muted-foreground">Reference ID</span>
                <span className="font-mono tabular-nums font-medium">{id}</span>
              </div>
              {request && (
                <>
                  <div className="flex justify-between text-sm">
                    <span className="text-muted-foreground">Instrument</span>
                    <span className="font-medium">{request.instrumentName}</span>
                  </div>
                  <div className="flex justify-between text-sm">
                    <span className="text-muted-foreground">Period</span>
                    <span className="font-mono tabular-nums">{request.fromDate} → {request.toDate}</span>
                  </div>
                  <div className="flex justify-between text-sm">
                    <span className="text-muted-foreground">Status</span>
                    <span className="text-status-limited font-medium capitalize">{request.status}</span>
                  </div>
                </>
              )}
            </motion.div>
          )}

          <motion.div variants={fadeInUp} className="flex items-center justify-center gap-2 text-xs text-muted-foreground bg-muted/50 rounded-lg p-3">
            <Mail className="h-3 w-3" />
            You will receive an email notification once approved.
          </motion.div>

          <motion.div variants={fadeInUp}>
            <Button asChild className="transition-transform active:scale-95">
              <Link to="/">Browse More Instruments</Link>
            </Button>
          </motion.div>
        </motion.div>
      </PageTransition>
    </MainLayout>
  );
};

export default BookingConfirmation;
