import { useMemo } from "react";
import { useBookingStore } from "@/store/bookingStore";
import { motion } from "framer-motion";
import { fadeInUp, staggerContainer } from "@/components/PageTransition";
import {
  BarChart, Bar, XAxis, YAxis, CartesianGrid, Tooltip, ResponsiveContainer,
  PieChart, Pie, Cell, Legend,
  LineChart, Line, Area, AreaChart,
} from "recharts";

const CHART_COLORS = [
  "hsl(199, 89%, 48%)",  // accent
  "hsl(221, 83%, 33%)",  // primary
  "hsl(142, 71%, 45%)",  // available
  "hsl(0, 84%, 60%)",    // booked
  "hsl(45, 93%, 47%)",   // limited
  "hsl(215, 16%, 47%)",  // muted
  "hsl(280, 65%, 60%)",
  "hsl(340, 75%, 55%)",
];

const AdminAnalytics = () => {
  const instruments = useBookingStore((s) => s.instruments);
  const bookingRequests = useBookingStore((s) => s.bookingRequests);

  // Category distribution for pie chart
  const categoryData = useMemo(() => {
    const map: Record<string, number> = {};
    instruments.forEach((i) => {
      map[i.category] = (map[i.category] || 0) + 1;
    });
    return Object.entries(map)
      .sort((a, b) => b[1] - a[1])
      .slice(0, 8)
      .map(([name, value]) => ({ name, value }));
  }, [instruments]);

  // Status distribution for pie chart
  const statusData = useMemo(() => {
    const map: Record<string, number> = {};
    instruments.forEach((i) => {
      map[i.status] = (map[i.status] || 0) + 1;
    });
    return Object.entries(map).map(([name, value]) => ({ name: name.charAt(0).toUpperCase() + name.slice(1), value }));
  }, [instruments]);

  const statusColors: Record<string, string> = {
    Available: "hsl(142, 71%, 45%)",
    Booked: "hsl(0, 84%, 60%)",
    Blocked: "hsl(215, 16%, 47%)",
    Limited: "hsl(45, 93%, 47%)",
  };

  // Booking requests by status for bar chart
  const requestStatusData = useMemo(() => {
    const pending = bookingRequests.filter((r) => r.status === "pending").length;
    const approved = bookingRequests.filter((r) => r.status === "approved").length;
    const rejected = bookingRequests.filter((r) => r.status === "rejected").length;
    return [
      { name: "Pending", count: pending, fill: "hsl(45, 93%, 47%)" },
      { name: "Approved", count: approved, fill: "hsl(142, 71%, 45%)" },
      { name: "Rejected", count: rejected, fill: "hsl(0, 84%, 60%)" },
    ];
  }, [bookingRequests]);

  // Simulated monthly booking trends
  const monthlyTrends = useMemo(() => {
    const months = ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"];
    return months.map((m, i) => ({
      month: m,
      bookings: Math.floor(20 + Math.random() * 60 + (i < 6 ? i * 8 : (12 - i) * 6)),
      utilization: Math.floor(50 + Math.random() * 40),
    }));
  }, []);

  // Top instruments by queue length
  const topQueueData = useMemo(() => {
    return instruments
      .filter((i) => i.waitingQueue.length > 0 || i.bookedSlots.length > 0)
      .sort((a, b) => b.waitingQueue.length - a.waitingQueue.length)
      .slice(0, 6)
      .map((i) => ({
        name: i.name.length > 20 ? i.name.slice(0, 18) + "…" : i.name,
        queue: i.waitingQueue.length,
        booked: i.bookedSlots.length,
      }));
  }, [instruments]);

  // Cost distribution
  const costData = useMemo(() => {
    const ranges = [
      { range: "₹0-200", min: 0, max: 200, count: 0 },
      { range: "₹201-400", min: 201, max: 400, count: 0 },
      { range: "₹401-600", min: 401, max: 600, count: 0 },
      { range: "₹601-800", min: 601, max: 800, count: 0 },
      { range: "₹800+", min: 801, max: Infinity, count: 0 },
    ];
    instruments.forEach((i) => {
      const cost = parseInt(i.usageCost.replace(/[^\d]/g, "")) || 0;
      const range = ranges.find((r) => cost >= r.min && cost <= r.max);
      if (range) range.count++;
    });
    return ranges.map((r) => ({ name: r.range, count: r.count }));
  }, [instruments]);

  return (
    <div className="p-4 sm:p-6 space-y-6">
      <motion.h1 variants={fadeInUp} initial="initial" animate="animate" className="text-lg sm:text-xl font-bold">
        Analytics
      </motion.h1>

      <motion.div className="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4" variants={staggerContainer} initial="initial" animate="animate">
        {[
          { label: "Total Instruments", val: instruments.length },
          { label: "Total Requests", val: bookingRequests.length },
          { label: "Categories", val: categoryData.length },
          { label: "Avg Cost/hr", val: `₹${Math.round(instruments.reduce((s, i) => s + (parseInt(i.usageCost.replace(/[^\d]/g, "")) || 0), 0) / instruments.length)}` },
        ].map((s) => (
          <motion.div key={s.label} variants={fadeInUp} className="bg-card rounded-lg card-shadow p-3 sm:p-4">
            <p className="text-[11px] sm:text-xs text-muted-foreground">{s.label}</p>
            <p className="text-lg sm:text-2xl font-bold font-mono tabular-nums mt-1">{s.val}</p>
          </motion.div>
        ))}
      </motion.div>

      {/* Charts Grid */}
      <div className="grid grid-cols-1 lg:grid-cols-2 gap-4 sm:gap-6">
        {/* Monthly Trends - Area Chart */}
        <motion.div initial={{ opacity: 0, y: 20 }} animate={{ opacity: 1, y: 0 }} transition={{ delay: 0.1 }} className="bg-card rounded-lg card-shadow p-4 sm:p-6">
          <h3 className="text-sm font-semibold mb-4">Monthly Booking Trends</h3>
          <div className="h-56 sm:h-64">
            <ResponsiveContainer width="100%" height="100%">
              <AreaChart data={monthlyTrends}>
                <CartesianGrid strokeDasharray="3 3" stroke="hsl(214, 32%, 91%)" />
                <XAxis dataKey="month" tick={{ fontSize: 11 }} stroke="hsl(215, 16%, 47%)" />
                <YAxis tick={{ fontSize: 11 }} stroke="hsl(215, 16%, 47%)" />
                <Tooltip contentStyle={{ fontSize: 12, borderRadius: 8, border: "1px solid hsl(214, 32%, 91%)" }} />
                <Area type="monotone" dataKey="bookings" stroke="hsl(199, 89%, 48%)" fill="hsl(199, 89%, 48%)" fillOpacity={0.15} strokeWidth={2} />
                <Area type="monotone" dataKey="utilization" stroke="hsl(221, 83%, 33%)" fill="hsl(221, 83%, 33%)" fillOpacity={0.1} strokeWidth={2} />
              </AreaChart>
            </ResponsiveContainer>
          </div>
          <div className="flex gap-4 mt-3 text-xs text-muted-foreground">
            <span className="flex items-center gap-1"><span className="h-2 w-2 rounded-full bg-accent" /> Bookings</span>
            <span className="flex items-center gap-1"><span className="h-2 w-2 rounded-full bg-primary" /> Utilization %</span>
          </div>
        </motion.div>

        {/* Category Distribution - Pie Chart */}
        <motion.div initial={{ opacity: 0, y: 20 }} animate={{ opacity: 1, y: 0 }} transition={{ delay: 0.15 }} className="bg-card rounded-lg card-shadow p-4 sm:p-6">
          <h3 className="text-sm font-semibold mb-4">Instruments by Category</h3>
          <div className="h-56 sm:h-64">
            <ResponsiveContainer width="100%" height="100%">
              <PieChart>
                <Pie data={categoryData} cx="50%" cy="50%" innerRadius="40%" outerRadius="70%" paddingAngle={2} dataKey="value" label={({ name, percent }) => `${name.slice(0, 12)} ${(percent * 100).toFixed(0)}%`} labelLine={false} style={{ fontSize: 10 }}>
                  {categoryData.map((_, idx) => (
                    <Cell key={idx} fill={CHART_COLORS[idx % CHART_COLORS.length]} />
                  ))}
                </Pie>
                <Tooltip contentStyle={{ fontSize: 12, borderRadius: 8, border: "1px solid hsl(214, 32%, 91%)" }} />
              </PieChart>
            </ResponsiveContainer>
          </div>
        </motion.div>

        {/* Request Status - Bar Chart */}
        <motion.div initial={{ opacity: 0, y: 20 }} animate={{ opacity: 1, y: 0 }} transition={{ delay: 0.2 }} className="bg-card rounded-lg card-shadow p-4 sm:p-6">
          <h3 className="text-sm font-semibold mb-4">Booking Requests by Status</h3>
          <div className="h-56 sm:h-64">
            <ResponsiveContainer width="100%" height="100%">
              <BarChart data={requestStatusData}>
                <CartesianGrid strokeDasharray="3 3" stroke="hsl(214, 32%, 91%)" />
                <XAxis dataKey="name" tick={{ fontSize: 12 }} stroke="hsl(215, 16%, 47%)" />
                <YAxis tick={{ fontSize: 11 }} stroke="hsl(215, 16%, 47%)" allowDecimals={false} />
                <Tooltip contentStyle={{ fontSize: 12, borderRadius: 8, border: "1px solid hsl(214, 32%, 91%)" }} />
                <Bar dataKey="count" radius={[6, 6, 0, 0]} barSize={48}>
                  {requestStatusData.map((entry, idx) => (
                    <Cell key={idx} fill={entry.fill} />
                  ))}
                </Bar>
              </BarChart>
            </ResponsiveContainer>
          </div>
        </motion.div>

        {/* Instrument Status - Pie */}
        <motion.div initial={{ opacity: 0, y: 20 }} animate={{ opacity: 1, y: 0 }} transition={{ delay: 0.25 }} className="bg-card rounded-lg card-shadow p-4 sm:p-6">
          <h3 className="text-sm font-semibold mb-4">Instrument Availability</h3>
          <div className="h-56 sm:h-64">
            <ResponsiveContainer width="100%" height="100%">
              <PieChart>
                <Pie data={statusData} cx="50%" cy="50%" outerRadius="70%" dataKey="value" label={({ name, value }) => `${name}: ${value}`} style={{ fontSize: 12 }}>
                  {statusData.map((entry) => (
                    <Cell key={entry.name} fill={statusColors[entry.name] || CHART_COLORS[0]} />
                  ))}
                </Pie>
                <Tooltip contentStyle={{ fontSize: 12, borderRadius: 8, border: "1px solid hsl(214, 32%, 91%)" }} />
                <Legend wrapperStyle={{ fontSize: 11 }} />
              </PieChart>
            </ResponsiveContainer>
          </div>
        </motion.div>

        {/* Cost Distribution - Bar */}
        <motion.div initial={{ opacity: 0, y: 20 }} animate={{ opacity: 1, y: 0 }} transition={{ delay: 0.3 }} className="bg-card rounded-lg card-shadow p-4 sm:p-6">
          <h3 className="text-sm font-semibold mb-4">Cost Distribution (per hour)</h3>
          <div className="h-56 sm:h-64">
            <ResponsiveContainer width="100%" height="100%">
              <BarChart data={costData}>
                <CartesianGrid strokeDasharray="3 3" stroke="hsl(214, 32%, 91%)" />
                <XAxis dataKey="name" tick={{ fontSize: 11 }} stroke="hsl(215, 16%, 47%)" />
                <YAxis tick={{ fontSize: 11 }} stroke="hsl(215, 16%, 47%)" allowDecimals={false} />
                <Tooltip contentStyle={{ fontSize: 12, borderRadius: 8, border: "1px solid hsl(214, 32%, 91%)" }} />
                <Bar dataKey="count" fill="hsl(199, 89%, 48%)" radius={[6, 6, 0, 0]} barSize={36} />
              </BarChart>
            </ResponsiveContainer>
          </div>
        </motion.div>

        {/* Top Demand - Horizontal Bar */}
        <motion.div initial={{ opacity: 0, y: 20 }} animate={{ opacity: 1, y: 0 }} transition={{ delay: 0.35 }} className="bg-card rounded-lg card-shadow p-4 sm:p-6">
          <h3 className="text-sm font-semibold mb-4">Most In-Demand Instruments</h3>
          <div className="h-56 sm:h-64">
            <ResponsiveContainer width="100%" height="100%">
              <BarChart data={topQueueData} layout="vertical">
                <CartesianGrid strokeDasharray="3 3" stroke="hsl(214, 32%, 91%)" />
                <XAxis type="number" tick={{ fontSize: 11 }} stroke="hsl(215, 16%, 47%)" allowDecimals={false} />
                <YAxis type="category" dataKey="name" tick={{ fontSize: 10 }} width={120} stroke="hsl(215, 16%, 47%)" />
                <Tooltip contentStyle={{ fontSize: 12, borderRadius: 8, border: "1px solid hsl(214, 32%, 91%)" }} />
                <Bar dataKey="queue" fill="hsl(45, 93%, 47%)" name="Queue" radius={[0, 4, 4, 0]} barSize={14} />
                <Bar dataKey="booked" fill="hsl(0, 84%, 60%)" name="Booked Slots" radius={[0, 4, 4, 0]} barSize={14} />
              </BarChart>
            </ResponsiveContainer>
          </div>
        </motion.div>
      </div>
    </div>
  );
};

export default AdminAnalytics;
