import { useBookingStore } from "@/store/bookingStore";
import { motion } from "framer-motion";
import { fadeInUp, staggerContainer } from "@/components/PageTransition";

const AdminQueue = () => {
  const instruments = useBookingStore((s) => s.instruments);
  const withQueue = instruments.filter((i) => i.waitingQueue.length > 0);

  return (
    <div className="p-4 sm:p-6 space-y-6">
      <motion.h1 variants={fadeInUp} initial="initial" animate="animate" className="text-lg sm:text-xl font-bold">Waiting Queue</motion.h1>

      {withQueue.length === 0 ? (
        <p className="text-sm text-muted-foreground">No instruments currently have a waiting queue.</p>
      ) : (
        <motion.div className="space-y-4" variants={staggerContainer} initial="initial" animate="animate">
          {withQueue.map((inst) => (
            <motion.div key={inst.id} variants={fadeInUp} className="bg-card rounded-lg card-shadow p-3 sm:p-4 space-y-3">
              <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                <div>
                  <h3 className="text-sm font-semibold">{inst.name}</h3>
                  <p className="text-xs text-muted-foreground font-mono tabular-nums">{inst.id}</p>
                </div>
                <span className="text-xs bg-status-booked/10 text-status-booked px-2 py-0.5 rounded-full self-start sm:self-auto">
                  {inst.bookedSlots[0]?.from} → {inst.bookedSlots[0]?.to}
                </span>
              </div>
              <div className="space-y-1">
                {inst.waitingQueue.map((entry) => (
                  <div key={entry.position} className="flex items-center justify-between text-xs py-1.5 px-3 bg-muted/50 rounded gap-2">
                    <span className="text-muted-foreground font-mono">#{entry.position}</span>
                    <span className="flex-1 truncate">{entry.user}</span>
                    <span className="text-muted-foreground hidden sm:inline">Will be notified when available</span>
                  </div>
                ))}
              </div>
            </motion.div>
          ))}
        </motion.div>
      )}
    </div>
  );
};

export default AdminQueue;
