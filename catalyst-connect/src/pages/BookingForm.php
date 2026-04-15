import { useState, useEffect } from "react";
import { useNavigate } from "react-router-dom";
import { MainLayout } from "@/components/layout/MainLayout";
import { useBookingStore } from "@/store/bookingStore";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import { Checkbox } from "@/components/ui/checkbox";
import { Calendar } from "@/components/ui/calendar";
import { Popover, PopoverContent, PopoverTrigger } from "@/components/ui/popover";
import { cn } from "@/lib/utils";
import { toast } from "sonner";
import { AlertTriangle, CalendarIcon } from "lucide-react";
import { UserType } from "@/types/instrument";
import { PageTransition, fadeInUp, staggerContainer } from "@/components/PageTransition";
import { motion, AnimatePresence } from "framer-motion";
import { format, parse, isValid } from "date-fns";
import { checkAvailability } from "@/api/services/bookingService";

const BookingForm = () => {
  const navigate = useNavigate();
  const bag = useBookingStore((s) => s.bag);
  const submitBooking = useBookingStore((s) => s.submitBooking);
  const joinQueue = useBookingStore((s) => s.joinQueue);
  const [userType, setUserType] = useState<UserType>("student");
  const [isConfidential, setIsConfidential] = useState(false);

  const [name, setName] = useState("");
  const [email, setEmail] = useState("");
  const [enrollment, setEnrollment] = useState("");
  const [employeeId, setEmployeeId] = useState("");
  const [department, setDepartment] = useState("");
  const [program, setProgram] = useState("");
  const [school, setSchool] = useState("");
  const [projectTitle, setProjectTitle] = useState("");
  const [fromDate, setFromDate] = useState<Date | undefined>();
  const [toDate, setToDate] = useState<Date | undefined>();
  const [fromInput, setFromInput] = useState("");
  const [toInput, setToInput] = useState("");
  const [fromOpen, setFromOpen] = useState(false);
  const [toOpen, setToOpen] = useState(false);
  const [isAvailable, setIsAvailable] = useState<boolean | null>(null);
  const [isLoading, setIsLoading] = useState(false);
  const [slotConflict, setSlotConflict] = useState(false);
  const [queueMode, setQueueMode] = useState(false);

  useEffect(() => {
    if (bag.length === 0) {
      navigate("/bag");
      return;
    }

    // Initialize date range from the first bag item, if present
    const firstItem = bag[0];
    if (firstItem) {
      if (firstItem.fromDate) {
        const parsedFrom = parse(firstItem.fromDate, "yyyy-MM-dd", new Date());
        if (isValid(parsedFrom)) {
          setFromDate(parsedFrom);
          setFromInput(firstItem.fromDate);
        }
      }
      if (firstItem.toDate) {
        const parsedTo = parse(firstItem.toDate, "yyyy-MM-dd", new Date());
        if (isValid(parsedTo)) {
          setToDate(parsedTo);
          setToInput(firstItem.toDate);
        }
      }
    }
  }, [bag, navigate]);

  if (bag.length === 0) {
    return null;
  }

  const firstItem = bag[0];

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

  const canSubmit = !!firstItem && !!fromDate && !!toDate && !isLoading;

  const formatDateForSummary = (date: Date) => format(date, "yyyy-MM-dd");

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();

    if (!name || !department || !email) {
      toast.error("Please fill in all required fields.");
      return;
    }

    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!emailRegex.test(email)) {
      toast.error("Please enter a valid email address.");
      return;
    }

    if (!firstItem || !fromDate || !toDate) {
      toast.error("Please select a date range.");
      return;
    }

    const fromDateStr = format(fromDate, "yyyy-MM-dd");
    const toDateStr = format(toDate, "yyyy-MM-dd");

    try {
      setIsLoading(true);
      setSlotConflict(false);
      setQueueMode(false);
      setIsAvailable(null);

      // Check availability before submitting
      try {
        const res = await checkAvailability({
          instrument_id: firstItem.instrument.id,
          start_date: fromDateStr,
          end_date: toDateStr,
        });

        const available = res?.data?.available !== false;
        if (!available) {
          setIsAvailable(false);
          setSlotConflict(true);
          setQueueMode(true);
          toast.error("Selected slot is already booked. You can join the queue.");
          return;
        }
        setIsAvailable(true);
      } catch (err: any) {
        const status = err?.response?.status;
        if (status === 409) {
          setIsAvailable(false);
          setSlotConflict(true);
          setQueueMode(true);
          toast.error("Selected slot is already booked. You can join the queue.");
          return;
        }
        console.error("Availability check failed", err);
      }

      const payload = userType === "student"
        ? {
            userType: "student",
            name,
            email,
            enrollmentNumber: enrollment,
            department,
            program,
            instrumentId: firstItem.instrument.id,
            instrumentName: firstItem.instrument.name,
            fromDate: fromDateStr,
            toDate: toDateStr,
            projectTitle: projectTitle != null ? String(projectTitle) : "",
            isConfidential,
          }
        : {
            userType: "employee",
            name,
            email,
            employeeId,
            department,
            school,
            instrumentId: firstItem.instrument.id,
            instrumentName: firstItem.instrument.name,
            fromDate: fromDateStr,
            toDate: toDateStr,
            projectTitle: "",
            isConfidential,
          };

      const requestId = await submitBooking(payload);

      toast.success("Booking requested successfully.");
      navigate(`/booking-confirmation/${requestId}`);
    } catch (err: any) {
      if (err.message === "slot_unavailable") {
        setSlotConflict(true);
        setQueueMode(true);
        toast.error("Selected slot is already booked. You can join the queue.");
      } else {
        toast.error("Something went wrong.");
      }
    } finally {
      setIsLoading(false);
    }
  };

  return (
    <MainLayout>
      <PageTransition>
        <div className="container py-8 max-w-2xl">
          <motion.h1 variants={fadeInUp} initial="initial" animate="animate" className="text-2xl font-bold mb-2">Booking Request</motion.h1>
          <motion.p variants={fadeInUp} initial="initial" animate="animate" className="text-sm text-muted-foreground mb-8">Complete the form to submit your instrument booking request.</motion.p>

          <motion.form
            onSubmit={handleSubmit}
            className="space-y-6"
            variants={staggerContainer}
            initial="initial"
            animate="animate"
          >
            {/* Instrument & Date Selection (for the bag's instrument) */}
            <motion.div
              variants={fadeInUp}
              className="bg-card rounded-xl border shadow-sm p-4 space-y-4"
            >
              <div className="grid gap-4 md:grid-cols-[minmax(0,2fr)_minmax(0,1.5fr)_minmax(0,1.5fr)] md:items-end">
                <div className="space-y-2">
                  <Label className="text-xs text-muted-foreground">Instrument</Label>
                  <div className="text-sm font-medium">
                    {firstItem.instrument.name}
                  </div>
                  <div className="text-[11px] text-muted-foreground font-mono tabular-nums">
                    {firstItem.instrument.id}
                  </div>
                </div>

                <div className="space-y-2">
                  <Label className="text-xs text-muted-foreground">From Date *</Label>
                  <Popover open={fromOpen} onOpenChange={setFromOpen}>
                    <div className="flex gap-2">
                      <Input
                        placeholder="YYYY-MM-DD"
                        value={fromInput}
                        onChange={(e) => handleFromInputChange(e.target.value)}
                        className="bg-card text-sm font-mono"
                      />
                      <PopoverTrigger asChild>
                        <Button
                          variant="outline"
                          size="icon"
                          className="shrink-0 transition-transform active:scale-95"
                          type="button"
                        >
                          <CalendarIcon className="h-4 w-4" />
                        </Button>
                      </PopoverTrigger>
                    </div>
                    <PopoverContent className="w-auto p-0" align="start">
                      <Calendar
                        mode="single"
                        selected={fromDate}
                        onSelect={handleFromDateSelect}
                        disabled={[{ before: new Date() }]}
                        className={cn("p-3 pointer-events-auto")}
                      />
                    </PopoverContent>
                  </Popover>
                </div>

                <div className="space-y-2">
                  <Label className="text-xs text-muted-foreground">To Date *</Label>
                  <Popover open={toOpen} onOpenChange={setToOpen}>
                    <div className="flex gap-2">
                      <Input
                        placeholder="YYYY-MM-DD"
                        value={toInput}
                        onChange={(e) => handleToInputChange(e.target.value)}
                        className="bg-card text-sm font-mono"
                      />
                      <PopoverTrigger asChild>
                        <Button
                          variant="outline"
                          size="icon"
                          className="shrink-0 transition-transform active:scale-95"
                          type="button"
                        >
                          <CalendarIcon className="h-4 w-4" />
                        </Button>
                      </PopoverTrigger>
                    </div>
                    <PopoverContent className="w-auto p-0" align="start">
                      <Calendar
                        mode="single"
                        selected={toDate}
                        onSelect={handleToDateSelect}
                        disabled={[{ before: fromDate || new Date() }]}
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

              {isAvailable === false && (
                <p className="text-xs text-status-booked">
                  Selected slot is not available. You can join the queue below.
                </p>
              )}
            </motion.div>
            {/* User Type */}
            <motion.div variants={fadeInUp} className="space-y-2">
              <Label>User Type</Label>
              <div className="flex gap-3">
                <Button type="button" variant={userType === "student" ? "default" : "outline"} size="sm" onClick={() => setUserType("student")} className="transition-transform active:scale-95">Student</Button>
                <Button type="button" variant={userType === "employee" ? "default" : "outline"} size="sm" onClick={() => setUserType("employee")} className="transition-transform active:scale-95">Employee</Button>
              </div>
            </motion.div>

            <motion.div variants={fadeInUp} className="space-y-2">
              <Label htmlFor="name">{userType === "student" ? "Student Name" : "Employee Name"} *</Label>
              <Input id="name" value={name} onChange={(e) => setName(e.target.value)} required className="bg-card" />
            </motion.div>

            <motion.div variants={fadeInUp} className="space-y-2">
              <Label htmlFor="email">Email Address *</Label>
              <Input id="email" type="email" value={email} onChange={(e) => setEmail(e.target.value)} required className="bg-card" />
            </motion.div>

            <AnimatePresence mode="wait">
              {userType === "student" ? (
                <motion.div
                  key="student"
                  initial={{ opacity: 0, x: -20 }}
                  animate={{ opacity: 1, x: 0 }}
                  exit={{ opacity: 0, x: 20 }}
                  transition={{ duration: 0.25 }}
                  className="space-y-6"
                >
                  <div className="space-y-2">
                    <Label htmlFor="enrollment">Enrollment Number *</Label>
                    <Input id="enrollment" value={enrollment} onChange={(e) => setEnrollment(e.target.value)} required className="bg-card" />
                  </div>
                  <div className="space-y-2">
                    <Label htmlFor="dept">Department *</Label>
                    <Input id="dept" value={department} onChange={(e) => setDepartment(e.target.value)} required className="bg-card" />
                  </div>
                  <div className="space-y-2">
                    <Label>Program</Label>
                    <Select value={program} onValueChange={setProgram}>
                      <SelectTrigger className="bg-card"><SelectValue placeholder="Select Program" /></SelectTrigger>
                      <SelectContent>
                        <SelectItem value="B.Tech">B.Tech</SelectItem>
                        <SelectItem value="M.Tech">M.Tech</SelectItem>
                        <SelectItem value="PhD">PhD</SelectItem>
                        <SelectItem value="MSc">MSc</SelectItem>
                      </SelectContent>
                    </Select>
                  </div>
                  <div className="space-y-2">
                    <Label htmlFor="project">Project Title</Label>
                    <Input id="project" value={projectTitle} onChange={(e) => setProjectTitle(e.target.value)} className="bg-card" />
                  </div>
                  <div className="flex items-start gap-3">
                    <Checkbox id="confidential" checked={isConfidential} onCheckedChange={(v) => setIsConfidential(v === true)} />
                    <div>
                      <Label htmlFor="confidential" className="text-sm cursor-pointer">This is a confidential project</Label>
                      <AnimatePresence>
                        {isConfidential && (
                          <motion.div
                            initial={{ opacity: 0, height: 0 }}
                            animate={{ opacity: 1, height: "auto" }}
                            exit={{ opacity: 0, height: 0 }}
                            className="flex items-center gap-2 mt-2 text-xs text-status-limited bg-status-limited/10 p-2 rounded"
                          >
                            <AlertTriangle className="h-3 w-3 shrink-0" />
                            Confidential projects require admin approval before slot confirmation.
                          </motion.div>
                        )}
                      </AnimatePresence>
                    </div>
                  </div>
                </motion.div>
              ) : (
                <motion.div
                  key="employee"
                  initial={{ opacity: 0, x: -20 }}
                  animate={{ opacity: 1, x: 0 }}
                  exit={{ opacity: 0, x: 20 }}
                  transition={{ duration: 0.25 }}
                  className="space-y-6"
                >
                  <div className="space-y-2">
                    <Label htmlFor="empId">Employee ID *</Label>
                    <Input id="empId" value={employeeId} onChange={(e) => setEmployeeId(e.target.value)} required className="bg-card" />
                  </div>
                  <div className="space-y-2">
                    <Label htmlFor="dept">Department *</Label>
                    <Input id="dept" value={department} onChange={(e) => setDepartment(e.target.value)} required className="bg-card" />
                  </div>
                  <div className="space-y-2">
                    <Label>School</Label>
                    <Select value={school} onValueChange={setSchool}>
                      <SelectTrigger className="bg-card"><SelectValue placeholder="Select School" /></SelectTrigger>
                      <SelectContent>
                        <SelectItem value="School of Engineering">School of Engineering</SelectItem>
                        <SelectItem value="School of Sciences">School of Sciences</SelectItem>
                        <SelectItem value="School of Management">School of Management</SelectItem>
                        <SelectItem value="School of Biotechnology">School of Biotechnology</SelectItem>
                      </SelectContent>
                    </Select>
                  </div>
                </motion.div>
              )}
            </AnimatePresence>

            <motion.div variants={fadeInUp} className="bg-muted/50 rounded-lg p-4 space-y-2">
              <h3 className="text-sm font-medium">Booking Summary</h3>
              <div className="text-xs text-muted-foreground flex justify-between">
                <span>{firstItem.instrument.name}</span>
                {fromDate && toDate && (
                  <span className="font-mono tabular-nums">
                    {formatDateForSummary(fromDate)} → {formatDateForSummary(toDate)}
                  </span>
                )}
              </div>
            </motion.div>

            {slotConflict && (
              <motion.div variants={fadeInUp} className="bg-red-50 border border-red-200 text-red-700 rounded-lg p-3">
                <p className="text-sm font-medium">Already Booked</p>
                <p className="text-xs">This slot is already occupied by an approved booking. You can join the queue to wait for availability.</p>
                <Button
                  type="button"
                  variant="outline"
                  className="mt-2 w-full"
                  disabled={!canSubmit || isLoading}
                  onClick={async () => {
                    if (!firstItem || !fromDate || !toDate) return;
                    const queued = await joinQueue(
                      firstItem.instrument.id,
                      name,
                      email,
                      format(fromDate, "yyyy-MM-dd"),
                      format(toDate, "yyyy-MM-dd"),
                    );
                    if (queued) {
                      toast.success("Added to queue.");
                      navigate("/booking-confirmation/queue");
                    } else {
                      toast.error("Unable to join queue. Please try again later.");
                    }
                  }}
                >
                  Join Queue
                </Button>
              </motion.div>
            )}

            <motion.div variants={fadeInUp}>
              {queueMode ? (
                <Button
                  type="button"
                  className="w-full bg-status-booked text-white hover:bg-status-booked/90 transition-transform active:scale-[0.98]"
                  disabled={!canSubmit || isLoading}
                  onClick={async () => {
                    if (!firstItem || !fromDate || !toDate) return;
                    const queued = await joinQueue(
                      firstItem.instrument.id,
                      name,
                      email,
                      format(fromDate, "yyyy-MM-dd"),
                      format(toDate, "yyyy-MM-dd"),
                    );
                    if (queued) {
                      toast.success("Added to queue.");
                      navigate("/booking-confirmation/queue");
                    } else {
                      toast.error("Unable to join queue. Please try again.");
                    }
                  }}
                >
                  Join Queue
                </Button>
              ) : (
                <Button
                  type="submit"
                  className="w-full transition-transform active:scale-[0.98]"
                  size="lg"
                  disabled={!canSubmit}
                >
                  {isLoading ? "Checking availability..." : "Submit Booking Request"}
                </Button>
              )}
            </motion.div>
          </motion.form>
        </div>
      </PageTransition>
    </MainLayout>
  );
};

export default BookingForm;
