import { useEffect, useState } from "react";
import { motion } from "framer-motion";
import { fadeInUp, staggerContainer } from "@/components/PageTransition";
import { getAdminQueue, approveQueueEntry, rejectQueueEntry } from "@/api/services/queueService";
import { Button } from "@/components/ui/button";
import { toast } from "sonner";

interface AdminQueueEntry {
  id: string;
  instrument_id: string;
  user_name: string;
  email: string | null;
  queue_position: number;
  date: string | null;
  time_slot: string | null;
  status: "pending" | "approved" | "rejected";
  instrument?: {
    id: string;
    name: string;
  };
}

const AdminQueue = () => {
  const [entries, setEntries] = useState<AdminQueueEntry[]>([]);
  const [loading, setLoading] = useState(false);

  const loadQueue = async () => {
    setLoading(true);
    try {
      const res = await getAdminQueue();
      const data = res?.data?.data ?? [];
      setEntries(Array.isArray(data) ? data : []);
    } catch (err) {
      console.error("Failed to load admin queue", err);
      toast.error("Unable to load queue list.");
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    loadQueue();
  }, []);

  const handleApprove = async (id: string) => {
    try {
      await approveQueueEntry(id);
      toast.success("Queue entry approved.");
      loadQueue();
    } catch (err) {
      console.error("Approve failed", err);
      toast.error("Failed to approve queue entry.");
    }
  };

  const handleReject = async (id: string) => {
    try {
      await rejectQueueEntry(id);
      toast.success("Queue entry rejected.");
      loadQueue();
    } catch (err) {
      console.error("Reject failed", err);
      toast.error("Failed to reject queue entry.");
    }
  };

  return (
    <div className="p-4 sm:p-6 space-y-6">
      <motion.h1 variants={fadeInUp} initial="initial" animate="animate" className="text-lg sm:text-xl font-bold">Queue Management</motion.h1>

      {loading ? (
        <p className="text-sm text-muted-foreground">Loading queue...</p>
      ) : entries.length === 0 ? (
        <p className="text-sm text-muted-foreground">No users are currently in the queue.</p>
      ) : (
        <motion.div className="space-y-4" variants={staggerContainer} initial="initial" animate="animate">
          {entries.map((entry) => (
            <motion.div key={entry.id} variants={fadeInUp} className="bg-card rounded-lg card-shadow p-3 sm:p-4 space-y-3">
              <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                <div>
                  <h3 className="text-sm font-semibold">{entry.instrument?.name ?? entry.instrument_id}</h3>
                  <p className="text-xs text-muted-foreground font-mono tabular-nums">{entry.instrument_id}</p>
                </div>
                <span className="text-xs bg-status-booked/10 text-status-booked px-2 py-0.5 rounded-full self-start sm:self-auto">
                  #{entry.queue_position} · {entry.date ?? "N/A"} · {entry.time_slot ?? "N/A"}
                </span>
              </div>
              <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-2 text-xs">
                <div className="space-y-0.5">
                  <p className="font-medium">{entry.user_name}</p>
                  <p className="text-muted-foreground">{entry.email}</p>
                  <p className="text-muted-foreground">Status: {entry.status}</p>
                </div>
                <div className="flex gap-2">
                  <Button
                    variant="outline"
                    size="sm"
                    onClick={() => handleApprove(entry.id)}
                    disabled={entry.status !== "pending"}
                  >
                    Approve
                  </Button>
                  <Button
                    variant="destructive"
                    size="sm"
                    onClick={() => handleReject(entry.id)}
                    disabled={entry.status !== "pending"}
                  >
                    Reject
                  </Button>
                </div>
              </div>
            </motion.div>
          ))}
        </motion.div>
      )}
    </div>
  );
};

export default AdminQueue;
