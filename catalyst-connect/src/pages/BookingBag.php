import { MainLayout } from "@/components/layout/MainLayout";
import { useBookingStore } from "@/store/bookingStore";
import { Button } from "@/components/ui/button";
import { Link } from "react-router-dom";
import { Trash2, ShoppingBag, ArrowRight, AlertTriangle } from "lucide-react";
import { toast } from "sonner";
import { PageTransition, fadeInUp, staggerContainer } from "@/components/PageTransition";
import { motion, AnimatePresence } from "framer-motion";

const BookingBag = () => {
  const bag = useBookingStore((s) => s.bag);
  const removeFromBag = useBookingStore((s) => s.removeFromBag);

  if (bag.length === 0) {
    return (
      <MainLayout>
        <PageTransition>
          <div className="container py-20 text-center max-w-md mx-auto space-y-4">
            <motion.div initial={{ scale: 0.8, opacity: 0 }} animate={{ scale: 1, opacity: 1 }} transition={{ duration: 0.4 }}>
              <ShoppingBag className="h-12 w-12 text-muted-foreground mx-auto" />
            </motion.div>
            <h2 className="text-xl font-semibold">Your booking bag is empty</h2>
            <p className="text-sm text-muted-foreground">Browse instruments and add them to your bag to start a booking request.</p>
            <Button asChild className="transition-transform active:scale-95">
              <Link to="/">Browse Instruments</Link>
            </Button>
          </div>
        </PageTransition>
      </MainLayout>
    );
  }

  return (
    <MainLayout>
      <PageTransition>
        <div className="container py-8 max-w-3xl">
          <motion.h1 variants={fadeInUp} initial="initial" animate="animate" className="text-2xl font-bold mb-6">Booking Bag</motion.h1>

          <motion.div className="space-y-3" variants={staggerContainer} initial="initial" animate="animate">
            <AnimatePresence>
              {bag.map((item) => {
                const isBlocked = item.instrument.status === "blocked";
                return (
                  <motion.div
                    key={item.instrument.id}
                    variants={fadeInUp}
                    exit={{ opacity: 0, x: -50, transition: { duration: 0.25 } }}
                    layout
                    className="bg-card rounded-lg card-shadow p-4 flex gap-4 items-center"
                  >
                    <div className="h-16 w-16 rounded-md bg-muted overflow-hidden shrink-0">
                      <img
                        src={item.instrument.image}
                        alt={item.instrument.name}
                        className="h-full w-full object-cover"
                        onError={(e) => { (e.target as HTMLImageElement).src = "/placeholder.svg"; }}
                      />
                    </div>
                    <div className="flex-1 min-w-0">
                      <h3 className="text-sm font-semibold truncate">{item.instrument.name}</h3>
                      <p className="text-xs text-muted-foreground">{item.instrument.category}</p>
                      <p className="text-xs font-mono tabular-nums text-muted-foreground mt-1">
                        {item.fromDate} → {item.toDate}
                      </p>
                      {isBlocked && (
                        <div className="flex items-center gap-1 text-status-booked text-xs mt-1">
                          <AlertTriangle className="h-3 w-3" /> This instrument is currently blocked
                        </div>
                      )}
                    </div>
                    <Button
                      variant="ghost"
                      size="icon"
                      className="shrink-0 text-muted-foreground hover:text-destructive transition-transform active:scale-90"
                      onClick={() => {
                        removeFromBag(item.instrument.id);
                        toast.info("Removed from bag.");
                      }}
                    >
                      <Trash2 className="h-4 w-4" />
                    </Button>
                  </motion.div>
                );
              })}
            </AnimatePresence>
          </motion.div>

          <motion.div variants={fadeInUp} initial="initial" animate="animate" className="flex gap-3 mt-8 justify-end">
            <Button variant="outline" asChild className="transition-transform active:scale-95">
              <Link to="/">Continue Browsing</Link>
            </Button>
            <Button asChild className="transition-transform active:scale-95">
              <Link to="/booking-form">
                Proceed to Booking <ArrowRight className="ml-2 h-4 w-4" />
              </Link>
            </Button>
          </motion.div>
        </div>
      </PageTransition>
    </MainLayout>
  );
};

export default BookingBag;
