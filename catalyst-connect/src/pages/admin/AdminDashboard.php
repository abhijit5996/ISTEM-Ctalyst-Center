import { useEffect } from "react";
import { useBookingStore } from "@/store/bookingStore";
import { useRealtimePolling } from "@/hooks/useRealtimeUpdates";
import { Calendar, Microscope, Clock, Users, Radio } from "lucide-react";
import { motion } from "framer-motion";
import { fadeInUp, staggerContainer } from "@/components/PageTransition";

const AdminDashboard = () => {
  const instruments = useBookingStore((s) => s.instruments);
  const bookingRequests = useBookingStore((s) => s.bookingRequests ?? []);
  const dashboardData = useBookingStore((s) => s.dashboardData);
  const loadingBookings = useBookingStore((s) => s.loadingBookings);
  const loadingDashboard = useBookingStore((s) => s.loadingDashboard);
  const realtimeEnabled = useBookingStore((s) => s.realtimeEnabled);
  const fetchBookings = useBookingStore((s) => s.fetchBookings);
  const fetchDashboard = useBookingStore((s) => s.fetchDashboard);
  const startRealtimeUpdates = useBookingStore((s) => s.startRealtimeUpdates);
  const stopRealtimeUpdates = useBookingStore((s) => s.stopRealtimeUpdates);

  useEffect(() => {
    fetchBookings();
    fetchDashboard();
    
    // Start real-time updates
    startRealtimeUpdates();

    // Cleanup on unmount
    return () => {
      stopRealtimeUpdates();
    };
  }, [fetchBookings, fetchDashboard, startRealtimeUpdates, stopRealtimeUpdates]);

  const knownInstruments = dashboardData?.instruments ?? instruments;
  const knownBookings = dashboardData?.bookings ?? bookingRequests ?? [];

  const computedStats = {
    total_instruments: knownInstruments.length,
    available: knownInstruments.filter((i) => i.status === "available").length,
    booked: knownInstruments.filter((i) => i.status === "booked").length,
    blocked: knownInstruments.filter((i) => i.status === "blocked").length,
    total_bookings: knownBookings.length,
    pending: knownBookings.filter((b) => b.status === "pending").length,
    approved: knownBookings.filter((b) => b.status === "approved").length,
    rejected: knownBookings.filter((b) => b.status === "rejected").length,
    totalQueue: knownInstruments.reduce((acc, inst) => acc + (Array.isArray(inst.waitingQueue) ? inst.waitingQueue.length : 0), 0),
  };

  const stats = dashboardData?.stats
    ? {
        ...computedStats,
        ...dashboardData.stats,
      }
    : computedStats;

  const statCards = [
    { label: "Total Instruments", value: stats.total_instruments ?? 0, icon: Microscope, color: "text-accent" },
    { label: "Total Bookings", value: stats.total_bookings ?? 0, icon: Calendar, color: "text-status-available" },
    { label: "Pending Requests", value: stats.pending ?? 0, icon: Clock, color: "text-status-limited" },
    { label: "Approved", value: stats.approved ?? 0, icon: Users, color: "text-status-available" },
    { label: "Rejected", value: stats.rejected ?? 0, icon: Users, color: "text-status-booked" },
    { label: "Queue Entries", value: stats.totalQueue ?? 0, icon: Users, color: "text-primary" },
  ];

  return (
    <div className="p-4 sm:p-6 space-y-6">
      <motion.div className="flex items-center justify-between" variants={fadeInUp} initial="initial" animate="animate">
        <h1 className="text-lg sm:text-xl font-bold">Admin Dashboard</h1>
        {realtimeEnabled && (
          <div className="flex items-center gap-2 text-xs sm:text-sm text-green-600 bg-green-50 dark:bg-green-950 px-3 py-1.5 rounded-full">
            <Radio className="h-3 w-3 animate-pulse" />
            <span>Live Updates Active</span>
          </div>
        )}
      </motion.div>

      <motion.div className="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4" variants={staggerContainer} initial="initial" animate="animate">
        {statCards.map((s) => (
          <motion.div key={s.label} variants={fadeInUp} whileHover={{ y: -2, transition: { duration: 0.15 } }} className="bg-card rounded-lg card-shadow p-3 sm:p-4 space-y-2">
            <div className="flex items-center justify-between">
              <p className="text-[11px] sm:text-xs text-muted-foreground">{s.label}</p>
              <s.icon className={`h-4 w-4 ${s.color}`} />
            </div>
            <p className="text-xl sm:text-2xl font-bold font-mono tabular-nums">{s.value}</p>
          </motion.div>
        ))}
      </motion.div>

      {/* Recent Bookings */}
      <motion.div initial={{ opacity: 0, y: 20 }} animate={{ opacity: 1, y: 0 }} transition={{ delay: 0.2 }} className="bg-card rounded-lg card-shadow">
        <div className="p-3 sm:p-4 border-b">
          <h2 className="text-sm font-semibold">Recent Booking Requests</h2>
        </div>

        {/* Mobile card view */}
        <div className="sm:hidden divide-y">
          {loadingBookings ? (
            <div className="p-4 text-center text-sm text-muted-foreground">Loading booking requests...</div>
          ) : !bookingRequests?.length ? (
            <div className="p-4 text-center text-sm text-muted-foreground">No booking requests found.</div>
          ) : (
            bookingRequests.map((r) => (
              <div key={r.id} className="p-3 space-y-1.5">
                <div className="flex items-center justify-between">
                  <span className="font-mono tabular-nums text-xs text-muted-foreground">{r.id}</span>
                  <span className={`inline-flex items-center gap-1 text-[10px] px-2 py-0.5 rounded-full ${
                    r.status === "approved" ? "bg-status-available/10 text-status-available" :
                    r.status === "rejected" ? "bg-status-booked/10 text-status-booked" :
                    "bg-status-limited/10 text-status-limited"
                  }`}>
                    <span className="h-1 w-1 rounded-full bg-current" />
                    {r.status}
                  </span>
                </div>
                <p className="text-sm font-medium truncate">{r.name}</p>
                <p className="text-xs text-muted-foreground truncate">{r.instrumentName}</p>
                <p className="text-[10px] font-mono tabular-nums text-muted-foreground">{r.fromDate} → {r.toDate}</p>
              </div>
            ))
          )}
        </div>

        {/* Desktop table */}
        <div className="hidden sm:block overflow-x-auto">
          <table className="w-full text-[13px]">
            <thead>
              <tr className="border-b text-left text-muted-foreground">
                <th className="p-3 font-medium">ID</th>
                <th className="p-3 font-medium">User</th>
                <th className="p-3 font-medium">Instrument</th>
                <th className="p-3 font-medium">Period</th>
                <th className="p-3 font-medium">Status</th>
              </tr>
            </thead>
            <tbody>
              {loadingBookings ? (
                <tr><td className="p-4 text-center" colSpan={5}>Loading booking requests...</td></tr>
              ) : !bookingRequests?.length ? (
                <tr><td className="p-4 text-center" colSpan={5}>No booking requests found.</td></tr>
              ) : (
                bookingRequests.map((r) => (
                  <tr key={r.id} className="border-b last:border-0 hover:bg-muted/50 transition-colors">
                    <td className="p-3 font-mono tabular-nums">{r.id}</td>
                    <td className="p-3">{r.name}</td>
                    <td className="p-3 max-w-[200px] truncate">{r.instrumentName}</td>
                    <td className="p-3 font-mono tabular-nums text-xs">{r.fromDate} → {r.toDate}</td>
                    <td className="p-3">
                      <span className={`inline-flex items-center gap-1 text-xs px-2 py-0.5 rounded-full ${
                        r.status === "approved" ? "bg-status-available/10 text-status-available" :
                        r.status === "rejected" ? "bg-status-booked/10 text-status-booked" :
                        "bg-status-limited/10 text-status-limited"
                      }`}>
                        <span className="h-1.5 w-1.5 rounded-full bg-current" />
                        {r.status}
                      </span>
                    </td>
                  </tr>
                ))
              )}
            </tbody>
          </table>
        </div>
      </motion.div>
    </div>
  );
};

export default AdminDashboard;
