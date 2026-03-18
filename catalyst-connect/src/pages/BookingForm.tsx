import { useState, useEffect } from "react";
import { useNavigate } from "react-router-dom";
import { MainLayout } from "@/components/layout/MainLayout";
import { useBookingStore } from "@/store/bookingStore";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import { Checkbox } from "@/components/ui/checkbox";
import { toast } from "sonner";
import { AlertTriangle } from "lucide-react";
import { UserType } from "@/types/instrument";
import { PageTransition, fadeInUp, staggerContainer } from "@/components/PageTransition";
import { motion, AnimatePresence } from "framer-motion";

const BookingForm = () => {
  const navigate = useNavigate();
  const bag = useBookingStore((s) => s.bag);
  const submitBooking = useBookingStore((s) => s.submitBooking);
  const joinQueue = useBookingStore((s) => s.joinQueue);
  const [userType, setUserType] = useState<UserType>("student");
  const [isConfidential, setIsConfidential] = useState(false);

  const [name, setName] = useState("");
  const [enrollment, setEnrollment] = useState("");
  const [employeeId, setEmployeeId] = useState("");
  const [department, setDepartment] = useState("");
  const [program, setProgram] = useState("");
  const [school, setSchool] = useState("");
  const [projectTitle, setProjectTitle] = useState("");

  useEffect(() => {
    if (bag.length === 0) {
      navigate("/bag");
    }
  }, [bag.length, navigate]);

  if (bag.length === 0) {
    return null;
  }

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!name || !department) {
      toast.error("Please fill in all required fields.");
      return;
    }

    const firstItem = bag[0];

    try {
      const requestId = await submitBooking(
        userType === "student"
          ? {
              userType: "student",
              name,
              enrollmentNumber: enrollment,
              department,
              program,
              instrumentId: firstItem.instrument.id,
              instrumentName: firstItem.instrument.name,
              fromDate: firstItem.fromDate,
              toDate: firstItem.toDate,
              projectTitle,
              isConfidential,
            }
          : {
              userType: "employee",
              name,
              employeeId,
              department,
              school,
              instrumentId: firstItem.instrument.id,
              instrumentName: firstItem.instrument.name,
              fromDate: firstItem.fromDate,
              toDate: firstItem.toDate,
            }
      );

      toast.success("Booking request submitted!");
      navigate(`/booking-confirmation/${requestId}`);
    } catch (err: any) {
      if (err.message === "slot_unavailable") {
        const confirmQueue = window.confirm("Slot unavailable. Join queue?");
        if (confirmQueue) {
          const queued = await joinQueue(firstItem.instrument.id, name);
          if (queued) {
            toast.success("Added to queue");
            navigate(`/booking-confirmation/queue`);
          } else {
            toast.error("Unable to join queue. Please try again later.");
          }
        }
      } else {
        toast.error("Failed to submit booking request. Please try again.");
      }
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
              {bag.map((item) => (
                <div key={item.instrument.id} className="text-xs text-muted-foreground flex justify-between">
                  <span>{item.instrument.name}</span>
                  <span className="font-mono tabular-nums">{item.fromDate} → {item.toDate}</span>
                </div>
              ))}
            </motion.div>

            <motion.div variants={fadeInUp}>
              <Button type="submit" className="w-full transition-transform active:scale-[0.98]" size="lg">
                Submit Booking Request
              </Button>
            </motion.div>
          </motion.form>
        </div>
      </PageTransition>
    </MainLayout>
  );
};

export default BookingForm;
