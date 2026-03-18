import { useBookingStore } from "@/store/bookingStore";
import { Button } from "@/components/ui/button";
import { Dialog, DialogContent, DialogHeader, DialogTitle } from "@/components/ui/dialog";
import { toast } from "sonner";
import { useState } from "react";
import { Check, X, Mail } from "lucide-react";

const AdminBookings = () => {
  const bookingRequests = useBookingStore((s) => s.bookingRequests);
  const approveBooking = useBookingStore((s) => s.approveBooking);
  const rejectBooking = useBookingStore((s) => s.rejectBooking);
  const [emailModal, setEmailModal] = useState<string | null>(null);

  const handleApprove = (id: string) => {
    approveBooking(id);
    setEmailModal(id);
  };

  return (
    <div className="p-4 sm:p-6 space-y-6">
      <h1 className="text-lg sm:text-xl font-bold">Booking Requests</h1>

      {/* Mobile card view */}
      <div className="sm:hidden space-y-3">
        {bookingRequests.map((r) => (
          <div key={r.id} className="bg-card rounded-lg card-shadow p-3 space-y-2">
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
            <p className="text-sm font-medium">{r.name} <span className="text-muted-foreground text-xs capitalize">({r.userType})</span></p>
            <p className="text-xs text-muted-foreground truncate">{r.instrumentName}</p>
            <p className="text-[10px] font-mono tabular-nums text-muted-foreground">{r.fromDate} → {r.toDate}</p>
            <p className="text-xs text-muted-foreground">{r.department}</p>
            {r.status === "pending" && (
              <div className="flex gap-2 pt-1">
                <Button size="sm" variant="outline" className="flex-1 h-8 text-xs text-status-available border-status-available/30" onClick={() => handleApprove(r.id)}>
                  <Check className="h-3 w-3 mr-1" /> Approve
                </Button>
                <Button size="sm" variant="outline" className="flex-1 h-8 text-xs text-status-booked border-status-booked/30" onClick={() => { rejectBooking(r.id); toast.info(`${r.id} rejected.`); }}>
                  <X className="h-3 w-3 mr-1" /> Reject
                </Button>
              </div>
            )}
          </div>
        ))}
      </div>

      {/* Desktop table */}
      <div className="hidden sm:block bg-card rounded-lg card-shadow overflow-x-auto">
        <table className="w-full text-[13px]">
          <thead>
            <tr className="border-b text-left text-muted-foreground">
              <th className="p-3 font-medium">ID</th>
              <th className="p-3 font-medium">User</th>
              <th className="p-3 font-medium">Type</th>
              <th className="p-3 font-medium">Instrument</th>
              <th className="p-3 font-medium">Period</th>
              <th className="p-3 font-medium">Dept</th>
              <th className="p-3 font-medium">Status</th>
              <th className="p-3 font-medium text-right">Actions</th>
            </tr>
          </thead>
          <tbody>
            {bookingRequests.map((r) => (
              <tr key={r.id} className="border-b last:border-0 hover:bg-muted/50 transition-colors h-10">
                <td className="p-3 font-mono tabular-nums text-xs">{r.id}</td>
                <td className="p-3">{r.name}</td>
                <td className="p-3 text-xs capitalize text-muted-foreground">{r.userType}</td>
                <td className="p-3 max-w-[180px] truncate">{r.instrumentName}</td>
                <td className="p-3 font-mono tabular-nums text-xs">{r.fromDate} → {r.toDate}</td>
                <td className="p-3 text-muted-foreground">{r.department}</td>
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
                <td className="p-3 text-right">
                  {r.status === "pending" && (
                    <div className="flex gap-1 justify-end">
                      <Button variant="ghost" size="icon" className="h-7 w-7 text-status-available hover:bg-status-available/10" onClick={() => handleApprove(r.id)}>
                        <Check className="h-3 w-3" />
                      </Button>
                      <Button variant="ghost" size="icon" className="h-7 w-7 text-status-booked hover:bg-status-booked/10" onClick={() => { rejectBooking(r.id); toast.info(`${r.id} rejected.`); }}>
                        <X className="h-3 w-3" />
                      </Button>
                    </div>
                  )}
                </td>
              </tr>
            ))}
          </tbody>
        </table>
      </div>

      {/* Email Simulation Modal */}
      <Dialog open={!!emailModal} onOpenChange={() => setEmailModal(null)}>
        <DialogContent>
          <DialogHeader>
            <DialogTitle className="flex items-center gap-2">
              <Mail className="h-4 w-4 text-accent" /> Email Notification
            </DialogTitle>
          </DialogHeader>
          <div className="space-y-4 text-sm">
            <p className="text-muted-foreground">
              Booking <span className="font-mono tabular-nums font-medium text-foreground">{emailModal}</span> has been approved.
            </p>
            <div className="bg-muted/50 rounded-lg p-4 space-y-2">
              <p className="font-medium">Simulated Email:</p>
              <p className="text-xs text-muted-foreground">
                Subject: Your Instrument Booking Has Been Approved<br />
                Body: Your booking request {emailModal} has been approved. Please proceed to the facility at your scheduled time.
              </p>
            </div>
            <Button
              className="w-full"
              onClick={() => {
                setEmailModal(null);
                toast.success("Confirmation email sent (simulated).");
              }}
            >
              Send Confirmation Email
            </Button>
          </div>
        </DialogContent>
      </Dialog>
    </div>
  );
};

export default AdminBookings;
