export type InstrumentStatus = "available" | "booked" | "blocked" | "limited";

export interface BookedSlot {
  user: string;
  from: string;
  to: string;
}

export interface QueueEntry {
  user: string;
  position: number;
}

export interface Instrument {
  id: string;
  name: string;
  category: string;
  location: string;
  status: InstrumentStatus;
  usageCost: string;
  image: string;
  description: string;
  bookedSlots: BookedSlot[];
  waitingQueue: QueueEntry[];
}

export interface BagItem {
  instrument: Instrument;
  fromDate: string;
  toDate: string;
}

export type UserType = "student" | "employee";

export interface StudentBooking {
  userType: "student";
  name: string;
  enrollmentNumber: string;
  department: string;
  program: string;
  instrumentId: string;
  fromDate: string;
  toDate: string;
  projectTitle: string;
  isConfidential: boolean;
}

export interface EmployeeBooking {
  userType: "employee";
  name: string;
  employeeId: string;
  department: string;
  school: string;
  instrumentId: string;
  fromDate: string;
  toDate: string;
}

export type BookingRequest = (StudentBooking | EmployeeBooking) & {
  id: string;
  status: "pending" | "approved" | "rejected";
  instrumentName: string;
  submittedAt: string;
};
