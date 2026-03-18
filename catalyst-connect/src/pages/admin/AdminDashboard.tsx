import { useEffect } from "react";
import { useBookingStore } from "@/store/bookingStore";
import { Calendar, Microscope, Clock, Users } from "lucide-react";
import { motion } from "framer-motion";
import { fadeInUp, staggerContainer } from "@/components/PageTransition";

const AdminDashboard = () => {
  const instruments = useBookingStore((s) => s.instruments);
  const bookingRequests = useBookingStore((s) => s.bookingRequests ?? []);
  const loadingBookings = useBookingStore((s) => s.loadingBookings);
  const fetchBookings = useBookingStore((s) => s.fetchBookings);

  useEffect(() => {
    fetchBookings();
  }, [fetchBookings]);

  const stats = {
    total: instruments.length,

    available: instruments.filter((i) => i.status === "available").length,

    booked: instruments.filter((i) => i.status === "booked").length,

    blocked: instruments.filter((i) => i.status === "blocked").length,

    totalBookings: instruments.reduce((acc, inst) => {
      return acc + ((inst.bookedSlots || []).length);
    }, 0),

    totalQueue: instruments.reduce((acc, inst) => {
      return acc + ((inst.waitingQueue || []).length);
    }, 0),
  };

  const statCards = [
    { label: "Total Instruments", value: stats.total, icon: Microscope, color: "text-accent" },
    { label: "Available Instruments", value: stats.available, icon: Calendar, color: "text-status-available" },
    { label: "Booked Instruments", value: stats.booked, icon: Clock, color: "text-status-booked" },
    { label: "Blocked Instruments", value: stats.blocked, icon: Users, color: "text-status-blocked" },
    { label: "Total Bookings", value: stats.totalBookings, icon: Calendar, color: "text-status-available" },
    { label: "Queue Entries", value: stats.totalQueue, icon: Users, color: "text-primary" },
  ];

  return (
    <div className="p-4 sm:p-6 space-y-6">
      <motion.h1 variants={fadeInUp} initial="initial" animate="animate" className="text-lg sm:text-xl font-bold">Admin Dashboard</motion.h1>

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
            bookingRequests.slice(0, 10).map((r) => (
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
                bookingRequests.slice(0, 10).map((r) => (
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
