import { Instrument } from "@/types/instrument";
import { Link, useNavigate } from "react-router-dom";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { useBookingStore } from "@/store/bookingStore";
import { toast } from "sonner";
import { MapPin, Clock } from "lucide-react";
import { motion } from "framer-motion";

// ✅ SAFE STATUS MAP (supports backend values)
const statusConfig: Record<string, { label: string; className: string }> = {
  available: { label: "Available", className: "bg-green-100 text-green-600 border-green-200" },
  active: { label: "Available", className: "bg-green-100 text-green-600 border-green-200" }, // 🔥 backend fix
  booked: { label: "Booked", className: "bg-red-100 text-red-600 border-red-200" },
  blocked: { label: "Blocked", className: "bg-gray-100 text-gray-600 border-gray-200" },
  limited: { label: "Limited Slots", className: "bg-yellow-100 text-yellow-600 border-yellow-200" },
};

type ExtendedInstrument = Instrument & {
  image_url?: string;
  usage_cost?: string;
  is_available?: boolean;
};

export function InstrumentCard({ instrument, index = 0 }: { instrument: ExtendedInstrument; index?: number }) {
  const addToBag = useBookingStore((s) => s.addToBag);
  const bag = useBookingStore((s) => s.bag);
  const navigate = useNavigate();

  const inBag = bag.some((b) => b.instrument.id === instrument.id);

  // ✅ SAFE CONFIG
  const config = statusConfig[instrument?.status] || {
    label: instrument?.status || "Unknown",
    className: "bg-gray-100 text-gray-500 border-gray-200",
  };

  // ✅ SAFE DATA
  const safe = {
    name: instrument?.name || "Unknown",
    description: instrument?.description || "No description",
    category: instrument?.category || "General",
    location: instrument?.location || "N/A",
    usageCost: instrument?.usageCost || instrument?.usage_cost || "N/A",
    image: instrument?.image_url || instrument?.image || "/placeholder.svg",
    status: instrument?.status || "unknown",
    isAvailable: instrument?.is_available ?? instrument?.status !== "booked",
  };

  const handleAddToBag = () => {
    if (!safe.isAvailable || safe.status === "blocked") {
      toast.error("This instrument is not available for booking.");
      return;
    }

    const today = new Date();
    const tomorrow = new Date(today);
    tomorrow.setDate(tomorrow.getDate() + 1);

    addToBag({
      instrument,
      fromDate: today.toISOString().split("T")[0],
      toDate: tomorrow.toISOString().split("T")[0],
    });

    toast.success(`${safe.name} added to booking bag.`);
  };

  return (
    <motion.div
      initial={{ opacity: 0, y: 20 }}
      animate={{ opacity: 1, y: 0 }}
      transition={{ duration: 0.35, delay: index * 0.04 }}
      whileHover={{ y: -4 }}
      className="group bg-card rounded-lg shadow-md hover:shadow-lg transition overflow-hidden"
    >
      <div className="aspect-[4/3] bg-muted relative overflow-hidden">
        <img
          src={safe.image}
          alt={safe.name}
          className="w-full h-full object-cover transition-transform duration-500 group-hover:scale-105"
          onError={(e) => {
            (e.target as HTMLImageElement).src = "/placeholder.svg";
          }}
        />

        <Badge className={`absolute top-3 right-3 text-xs ${config.className}`}>
          <span className="mr-1.5 inline-block h-1.5 w-1.5 rounded-full bg-current" />
          {config.label}
        </Badge>
      </div>

      <div className="p-4 space-y-3">
        <div>
          <p className="text-xs text-muted-foreground uppercase">{safe.category}</p>
          <h3 className="font-semibold text-sm mt-1 line-clamp-2">{safe.name}</h3>
          <Badge className={`inline-flex items-center text-[10px] mt-1 ${safe.isAvailable ? 'bg-green-100 text-green-600 border-green-200' : 'bg-red-100 text-red-600 border-red-200'}`}>
            {safe.isAvailable ? 'Available' : 'Booked'}
          </Badge>
        </div>

        <p className="text-xs text-muted-foreground line-clamp-2">
          {safe.description}
        </p>

        <div className="flex items-center gap-3 text-xs text-muted-foreground">
          <span className="flex items-center gap-1">
            <MapPin className="h-3 w-3" />
            <span className="truncate max-w-[120px]">{safe.location}</span>
          </span>

          <span className="flex items-center gap-1">
            <Clock className="h-3 w-3" />
            {safe.usageCost}
          </span>
        </div>

        <div className="flex gap-2 pt-1">
          <Link to={`/instrument/${instrument.id}`} className="flex-1">
            <Button variant="outline" size="sm" className="w-full text-xs">
              View Details
            </Button>
          </Link>

          <Button
            size="sm"
            className="flex-1 text-xs"
            disabled={inBag || safe.status === "blocked"}
            onClick={handleAddToBag}
          >
            {inBag ? "In Bag" : "Add to Bag"}
          </Button>
        </div>

        {safe.status === 'booked' && (
          <div className="pt-2 space-y-2">
            <div className="text-xs text-red-600 font-medium">Already Booked</div>
            <Button
              size="sm"
              variant="ghost"
              className="w-full text-xs"
              onClick={async () => {
                const success = await useBookingStore.getState().joinQueue(
                  instrument.id,
                  "Current User",
                  "current.user@example.com"
                );
                if (success) {
                  toast.success("Joined queue");
                } else {
                  toast.error("Failed to join queue. Please try again later.");
                }
              }}
            >
              Join Queue
            </Button>
          </div>
        )}
      </div>
    </motion.div>
  );
}