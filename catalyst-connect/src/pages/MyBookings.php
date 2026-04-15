import { useEffect, useState } from "react";
import { MainLayout } from "@/components/layout/MainLayout";
import { getUserBookings } from "@/api/services/authService";
import { toast } from "sonner";

interface UserBooking {
  id: string;
  instrument_name?: string;
  instrument_id?: string;
  status: string;
  start_date?: string;
  end_date?: string;
}

const MyBookings = () => {
  const [bookings, setBookings] = useState<UserBooking[]>([]);
  const [loading, setLoading] = useState(false);

  useEffect(() => {
    const loadBookings = async () => {
      setLoading(true);
      try {
        const res = await getUserBookings();
        const data = res?.data?.data ?? res?.data ?? [];
        setBookings(Array.isArray(data) ? data : []);
      } catch (err) {
        console.error("Failed to load bookings", err);
        toast.error("Unable to load your bookings.");
      } finally {
        setLoading(false);
      }
    };

    loadBookings();
  }, []);

  return (
    <MainLayout>
      <div className="container py-8 space-y-4">
        <h1 className="text-2xl font-bold">My Bookings</h1>
        {loading ? (
          <p className="text-sm text-muted-foreground">Loading bookings...</p>
        ) : bookings.length === 0 ? (
          <p className="text-sm text-muted-foreground">You have no bookings yet.</p>
        ) : (
          <div className="space-y-3">
            {bookings.map((b) => (
              <div key={b.id} className="bg-card rounded-lg card-shadow p-4 space-y-1 text-sm">
                <div className="flex justify-between items-center gap-2">
                  <div>
                    <p className="font-semibold">{b.instrument_name ?? b.instrument_id ?? "Instrument"}</p>
                    <p className="text-xs text-muted-foreground">ID: {b.id}</p>
                  </div>
                  <span className="text-xs bg-secondary px-2 py-0.5 rounded-full capitalize">
                    {b.status}
                  </span>
                </div>
                <div className="text-xs text-muted-foreground flex gap-4 flex-wrap">
                  {b.start_date && <span>From: {b.start_date}</span>}
                  {b.end_date && <span>To: {b.end_date}</span>}
                </div>
              </div>
            ))}
          </div>
        )}
      </div>
    </MainLayout>
  );
};

export default MyBookings;
