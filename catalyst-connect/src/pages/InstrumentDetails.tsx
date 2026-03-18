import { useParams, Link, useNavigate } from "react-router-dom";
import { MainLayout } from "@/components/layout/MainLayout";
import { useBookingStore } from "@/store/bookingStore";
import { Button } from "@/components/ui/button";
import { Badge } from "@/components/ui/badge";
import { Calendar } from "@/components/ui/calendar";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Popover, PopoverContent, PopoverTrigger } from "@/components/ui/popover";
import { cn } from "@/lib/utils";
import { toast } from "sonner";
import { useState, useMemo } from "react";
import { ArrowLeft, MapPin, Clock, AlertTriangle, Users, CalendarIcon } from "lucide-react";
import { format, parse, isValid } from "date-fns";
import { PageTransition, fadeInUp, staggerContainer } from "@/components/PageTransition";
import { motion } from "framer-motion";

const InstrumentDetails = () => {
  const { id } = useParams<{ id: string }>();
  const navigate = useNavigate();
  const instruments = useBookingStore((s) => s.instruments);
  const addToBag = useBookingStore((s) => s.addToBag);
  const joinQueue = useBookingStore((s) => s.joinQueue);
  const bag = useBookingStore((s) => s.bag);
  const instrument = instruments.find((i) => i.id === id);

  const safeInstrument = {
    ...instrument,
    bookedSlots: instrument?.bookedSlots || [],
    waitingQueue: instrument?.waitingQueue || [],
  };

  const [fromDate, setFromDate] = useState<Date | undefined>();
  const [toDate, setToDate] = useState<Date | undefined>();
  const [fromInput, setFromInput] = useState("");
  const [toInput, setToInput] = useState("");
  const [fromOpen, setFromOpen] = useState(false);
  const [toOpen, setToOpen] = useState(false);

  const inBag = bag.some((b) => b.instrument.id === id);

  const bookedDates = useMemo(() => {
    if (!instrument) return [];

    const slots = safeInstrument.bookedSlots || [];
    const dates: Date[] = [];

    slots.forEach((slot) => {
      const start = new Date(slot.from);
      const end = new Date(slot.to);

      for (let d = new Date(start); d <= end; d.setDate(d.getDate() + 1)) {
        dates.push(new Date(d));
      }
    });

    return dates;
  }, [instrument]);

  if (!instrument) {
    return (
      <MainLayout>
        <div className="container py-20 text-center">
          <p className="text-muted-foreground">Instrument not found.</p>
          <Link to="/" className="text-accent underline mt-2 inline-block">Back to instruments</Link>
        </div>
      </MainLayout>
    );
  }

  const handleFromDateSelect = (date: Date | undefined) => {
    setFromDate(date);
    setFromInput(date ? format(date, "yyyy-MM-dd") : "");
    setFromOpen(false);
    if (date && toDate && date > toDate) {
      setToDate(undefined);
      setToInput("");
    }
  };

  const handleToDateSelect = (date: Date | undefined) => {
    setToDate(date);
    setToInput(date ? format(date, "yyyy-MM-dd") : "");
    setToOpen(false);
  };

  const handleFromInputChange = (val: string) => {
    setFromInput(val);
    const parsed = parse(val, "yyyy-MM-dd", new Date());
    if (isValid(parsed) && parsed >= new Date(new Date().toDateString())) {
      setFromDate(parsed);
      if (toDate && parsed > toDate) {
        setToDate(undefined);
        setToInput("");
      }
    }
  };

  const handleToInputChange = (val: string) => {
    setToInput(val);
    const parsed = parse(val, "yyyy-MM-dd", new Date());
    if (isValid(parsed) && (!fromDate || parsed >= fromDate)) {
      setToDate(parsed);
    }
  };

  const handleAddToBag = () => {
    if (!fromDate || !toDate) {
      toast.error("Please select a booking period.");
      return;
    }
    addToBag({
      instrument,
      fromDate: format(fromDate, "yyyy-MM-dd"),
      toDate: format(toDate, "yyyy-MM-dd"),
    });
    toast.success(`${instrument.name} added to booking bag.`);
  };

  const handleJoinQueue = async () => {
    // Fill with the logged-in user email as available (static placeholder used here)
    const success = await joinQueue(instrument.id, "Current User", "current.user@example.com");

    if (success) {
      toast.success("Joined waiting queue successfully.");
    } else {
      toast.error("Could not join queue. Please try again.");
    }
  };

  const isBooked = instrument.status === "booked";

  return (
    <MainLayout>
      <PageTransition>
        <div className="container py-8 max-w-5xl">
          <motion.div variants={fadeInUp} initial="initial" animate="animate">
            <Button variant="ghost" size="sm" onClick={() => navigate(-1)} className="mb-6 text-muted-foreground transition-transform active:scale-95">
              <ArrowLeft className="mr-2 h-4 w-4" /> Back
            </Button>
          </motion.div>

          <div className="grid md:grid-cols-2 gap-8">
            {/* Image */}
            <motion.div
              initial={{ opacity: 0, x: -30 }}
              animate={{ opacity: 1, x: 0 }}
              transition={{ duration: 0.5, ease: "easeOut" }}
              className="space-y-4"
            >
              <div className="aspect-[4/3] bg-muted rounded-lg overflow-hidden card-shadow">
                <img
                  src={instrument.image}
                  alt={instrument.name}
                  className="w-full h-full object-cover image-outline"
                  onError={(e) => { (e.target as HTMLImageElement).src = "/placeholder.svg"; }}
                />
              </div>
            </motion.div>

            {/* Details */}
            <motion.div
              className="space-y-6"
              variants={staggerContainer}
              initial="initial"
              animate="animate"
            >
              <motion.div variants={fadeInUp}>
                <p className="text-xs font-medium uppercase tracking-wider text-muted-foreground">{instrument.category}</p>
                <h1 className="text-2xl font-bold mt-1">{instrument.name}</h1>
                <p className="font-mono tabular-nums text-sm text-muted-foreground mt-1">{instrument.id}</p>
              </motion.div>

              <motion.div variants={fadeInUp}>
                <Badge variant="outline" className={cn(
                  "text-xs",
                  instrument.status === "available" && "bg-status-available/10 text-status-available border-status-available/20",
                  instrument.status === "booked" && "bg-status-booked/10 text-status-booked border-status-booked/20",
                  instrument.status === "blocked" && "bg-status-blocked/10 text-status-blocked border-status-blocked/20",
                )}>
                  <span className="mr-1.5 inline-block h-1.5 w-1.5 rounded-full bg-current" />
                  Status: {instrument.status === "available" ? "Available for immediate booking" : `Currently ${instrument.status}`}
                </Badge>
              </motion.div>

              <motion.p variants={fadeInUp} className="text-sm text-muted-foreground leading-relaxed">{instrument.description}</motion.p>

              <motion.div variants={fadeInUp} className="grid grid-cols-2 gap-3 text-sm">
                <div className="flex items-center gap-2 text-muted-foreground">
                  <MapPin className="h-4 w-4 shrink-0" />
                  {instrument.location}
                </div>
                <div className="flex items-center gap-2 font-mono tabular-nums text-muted-foreground">
                  <Clock className="h-4 w-4 shrink-0" />
                  {instrument.usageCost}
                </div>
              </motion.div>

              {/* Booked slots info */}
              {isBooked && (safeInstrument.bookedSlots || []).length > 0 && (
                <motion.div variants={fadeInUp} className="bg-status-booked/5 border border-status-booked/20 rounded-lg p-4 space-y-2">
                  <div className="flex items-center gap-2 text-status-booked text-sm font-medium">
                    <AlertTriangle className="h-4 w-4" />
                    Currently Booked
                  </div>
                  {safeInstrument.bookedSlots.map((slot, i) => (
                    <p key={i} className="text-xs text-muted-foreground">
                      Booked by {slot.user} from <span className="font-mono tabular-nums">{slot.from}</span> to <span className="font-mono tabular-nums">{slot.to}</span>
                    </p>
                  ))}
                  <Button variant="outline" size="sm" className="mt-2 text-xs transition-transform active:scale-95" onClick={handleJoinQueue}>
                    <Users className="mr-2 h-3 w-3" /> Join Waiting Queue
                  </Button>
                  {(safeInstrument.waitingQueue || []).length > 0 && (
                    <p className="text-xs text-muted-foreground">
                      Queue: {safeInstrument.waitingQueue.length} waiting
                    </p>
                  )}
                </motion.div>
              )}

              {/* Date Pickers - From and To */}
              <motion.div variants={fadeInUp} className="space-y-4">
                <h3 className="text-sm font-medium">Select Booking Period</h3>
                <div className="grid grid-cols-2 gap-4">
                  {/* FROM date */}
                  <div className="space-y-2">
                    <Label className="text-xs text-muted-foreground">From Date</Label>
                    <Popover open={fromOpen} onOpenChange={setFromOpen}>
                      <div className="flex gap-2">
                        <Input
                          placeholder="YYYY-MM-DD"
                          value={fromInput}
                          onChange={(e) => handleFromInputChange(e.target.value)}
                          className="bg-card text-sm font-mono"
                        />
                        <PopoverTrigger asChild>
                          <Button variant="outline" size="icon" className="shrink-0 transition-transform active:scale-95">
                            <CalendarIcon className="h-4 w-4" />
                          </Button>
                        </PopoverTrigger>
                      </div>
                      <PopoverContent className="w-auto p-0" align="start">
                        <Calendar
                          mode="single"
                          selected={fromDate}
                          onSelect={handleFromDateSelect}
                          disabled={[{ before: new Date() }, ...bookedDates]}
                          modifiers={{ booked: bookedDates }}
                          modifiersClassNames={{ booked: "text-status-booked line-through" }}
                          className={cn("p-3 pointer-events-auto")}
                        />
                      </PopoverContent>
                    </Popover>
                  </div>

                  {/* TO date */}
                  <div className="space-y-2">
                    <Label className="text-xs text-muted-foreground">To Date</Label>
                    <Popover open={toOpen} onOpenChange={setToOpen}>
                      <div className="flex gap-2">
                        <Input
                          placeholder="YYYY-MM-DD"
                          value={toInput}
                          onChange={(e) => handleToInputChange(e.target.value)}
                          className="bg-card text-sm font-mono"
                        />
                        <PopoverTrigger asChild>
                          <Button variant="outline" size="icon" className="shrink-0 transition-transform active:scale-95">
                            <CalendarIcon className="h-4 w-4" />
                          </Button>
                        </PopoverTrigger>
                      </div>
                      <PopoverContent className="w-auto p-0" align="start">
                        <Calendar
                          mode="single"
                          selected={toDate}
                          onSelect={handleToDateSelect}
                          disabled={[{ before: fromDate || new Date() }, ...bookedDates]}
                          modifiers={{ booked: bookedDates }}
                          modifiersClassNames={{ booked: "text-status-booked line-through" }}
                          className={cn("p-3 pointer-events-auto")}
                        />
                      </PopoverContent>
                    </Popover>
                  </div>
                </div>

                {fromDate && toDate && (
                  <motion.div
                    initial={{ opacity: 0, height: 0 }}
                    animate={{ opacity: 1, height: "auto" }}
                    className="bg-accent/10 text-accent rounded-lg px-3 py-2 text-xs font-medium"
                  >
                    Selected: {format(fromDate, "dd MMM yyyy")} → {format(toDate, "dd MMM yyyy")}
                  </motion.div>
                )}

                <div className="flex items-center gap-4 text-xs text-muted-foreground">
                  <span className="flex items-center gap-1"><span className="h-2 w-2 rounded-full bg-status-available" /> Available</span>
                  <span className="flex items-center gap-1"><span className="h-2 w-2 rounded-full bg-status-booked" /> Booked</span>
                  <span className="flex items-center gap-1"><span className="h-2 w-2 rounded-full bg-muted-foreground" /> Past</span>
                </div>
              </motion.div>

              {/* Actions */}
              <motion.div variants={fadeInUp} className="flex gap-3 pt-2">
                <Button
                  className="flex-1 transition-transform active:scale-95"
                  disabled={inBag || instrument.status === "blocked"}
                  onClick={handleAddToBag}
                >
                  {inBag ? "Already in Bag" : "Add to Booking Bag"}
                </Button>
                <Button
                  variant="outline"
                  className="flex-1 transition-transform active:scale-95"
                  disabled={!fromDate || !toDate}
                  onClick={() => {
                    handleAddToBag();
                    navigate("/bag");
                  }}
                >
                  Book Now
                </Button>
              </motion.div>
            </motion.div>
          </div>
        </div>
      </PageTransition>
    </MainLayout>
  );
};

export default InstrumentDetails;
