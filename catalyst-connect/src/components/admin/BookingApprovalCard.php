import { Button } from "@/components/ui/button";
import type { BookingRequest } from "@/types/instrument";

export type AdminBooking = BookingRequest & {
  instrument_id?: string | null;
};

interface BookingApprovalCardProps {
  booking: AdminBooking;
  onApprove: (id: string) => void | Promise<void>;
  onReject: (id: string) => void | Promise<void>;
  isProcessing?: boolean;
}

export const BookingApprovalCard = ({
  booking,
  onApprove,
  onReject,
  isProcessing = false,
}: BookingApprovalCardProps) => {
  const isPending = booking.status === "pending";

  const statusClasses =
    booking.status === "approved"
      ? "bg-status-available/10 text-status-available"
      : booking.status === "rejected"
      ? "bg-status-booked/10 text-status-booked"
      : "bg-status-limited/10 text-status-limited";

  return (
    <div className="bg-card rounded-lg card-shadow p-3 sm:p-4 space-y-3">
      <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
        <div className="space-y-0.5">
          <h3 className="text-sm font-semibold truncate">
            {booking.instrumentName || "Instrument"}
          </h3>
          <p className="text-xs text-muted-foreground font-mono tabular-nums">
            {booking.instrument_id || "Instrument ID: N/A"}
          </p>
          <p className="text-[11px] text-muted-foreground font-mono tabular-nums">
            Booking: {booking.id}
          </p>
        </div>
        <span className="text-xs bg-status-booked/10 text-status-booked px-2 py-0.5 rounded-full self-start sm:self-auto">
          {booking.fromDate && booking.toDate
            ? `${booking.fromDate} → ${booking.toDate}`
            : "Date range: N/A"}
        </span>
      </div>

      <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-2 text-xs">
        <div className="space-y-0.5">
          <p className="font-medium">
            {booking.name}{" "}
            <span className="text-muted-foreground text-[10px] capitalize">
              ({booking.userType})
            </span>
          </p>
          <p className="text-muted-foreground truncate">
            {booking.department || "Department: N/A"}
          </p>
          <p className="text-muted-foreground">
            Status: <span className="capitalize">{booking.status}</span>
          </p>
        </div>

        <div className="flex items-center gap-2">
          <span
            className={`inline-flex items-center gap-1 text-[11px] px-2 py-0.5 rounded-full ${statusClasses}`}
          >
            <span className="h-1.5 w-1.5 rounded-full bg-current" />
            {booking.status}
          </span>

          <div className="flex gap-2 ml-2">
            <Button
              variant="outline"
              size="sm"
              onClick={() => onApprove(booking.id)}
              disabled={!isPending || isProcessing}
            >
              {isProcessing && isPending ? "Approving..." : "Approve"}
            </Button>
            <Button
              variant="destructive"
              size="sm"
              onClick={() => onReject(booking.id)}
              disabled={!isPending || isProcessing}
            >
              {isProcessing && isPending ? "Rejecting..." : "Reject"}
            </Button>
          </div>
        </div>
      </div>
    </div>
  );
};

export default BookingApprovalCard;
