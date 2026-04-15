import { useEffect, useState } from "react";
import { MainLayout } from "@/components/layout/MainLayout";
import { getUserQueue } from "@/api/services/authService";
import { toast } from "sonner";

interface UserQueueEntry {
  id: string;
  instrument_name?: string;
  instrument_id?: string;
  queue_position?: number;
  expected_slot?: string | null;
}

const QueueStatus = () => {
  const [entries, setEntries] = useState<UserQueueEntry[]>([]);
  const [loading, setLoading] = useState(false);

  useEffect(() => {
    const loadQueue = async () => {
      setLoading(true);
      try {
        const res = await getUserQueue();
        const data = res?.data?.data ?? res?.data ?? [];
        setEntries(Array.isArray(data) ? data : []);
      } catch (err) {
        console.error("Failed to load queue status", err);
        toast.error("Unable to load queue status.");
      } finally {
        setLoading(false);
      }
    };

    loadQueue();
  }, []);

  return (
    <MainLayout>
      <div className="container py-8 space-y-4">
        <h1 className="text-2xl font-bold">Queue Status</h1>
        {loading ? (
          <p className="text-sm text-muted-foreground">Loading queue...</p>
        ) : entries.length === 0 ? (
          <p className="text-sm text-muted-foreground">You are not currently in any queue.</p>
        ) : (
          <div className="space-y-3">
            {entries.map((e) => (
              <div key={e.id} className="bg-card rounded-lg card-shadow p-4 space-y-1 text-sm">
                <div className="flex justify-between items-center gap-2">
                  <div>
                    <p className="font-semibold">{e.instrument_name ?? e.instrument_id ?? "Instrument"}</p>
                    <p className="text-xs text-muted-foreground">ID: {e.id}</p>
                  </div>
                  {typeof e.queue_position === "number" && (
                    <span className="text-xs bg-status-booked/10 text-status-booked px-2 py-0.5 rounded-full">
                      Position #{e.queue_position}
                    </span>
                  )}
                </div>
                {e.expected_slot && (
                  <p className="text-xs text-muted-foreground">Expected slot: {e.expected_slot}</p>
                )}
              </div>
            ))}
          </div>
        )}
      </div>
    </MainLayout>
  );
};

export default QueueStatus;
