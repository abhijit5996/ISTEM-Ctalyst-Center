import { useState, useMemo } from "react";
import { useBookingStore } from "@/store/bookingStore";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import { Textarea } from "@/components/ui/textarea";
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogTrigger } from "@/components/ui/dialog";
import { toast } from "sonner";
import { Plus, Trash2 } from "lucide-react";
import { Instrument } from "@/types/instrument";

const AdminInstruments = () => {
  const instruments = useBookingStore((s) => s.instruments);
  const addInstrument = useBookingStore((s) => s.addInstrument);
  const deleteInstrument = useBookingStore((s) => s.deleteInstrument);
  const [open, setOpen] = useState(false);
  const [search, setSearch] = useState("");

  const [name, setName] = useState("");
  const [category, setCategory] = useState("");
  const [location, setLocation] = useState("");
  const [cost, setCost] = useState("");
  const [description, setDescription] = useState("");
  const [status, setStatus] = useState<"available" | "booked" | "blocked">("available");

  const categories = useMemo(() => {
    const unique = Array.from(new Set(instruments.map((i) => i.category).filter(Boolean)));
    return unique;
  }, [instruments]);

  const filtered = instruments.filter((i) =>
    i.name.toLowerCase().includes(search.toLowerCase()) || i.id.toLowerCase().includes(search.toLowerCase())
  );

  const handleAdd = () => {
    if (!name || !category) {
      toast.error("Name and category are required.");
      return;
    }
    const newId = `INS${String(instruments.length + 1).padStart(3, "0")}`;
    const inst: Instrument = {
      id: newId, name, category, location: location || "TBD", status,
      usageCost: cost || "₹0/hour", image: "/placeholder.svg",
      description: description || "", bookedSlots: [], waitingQueue: [],
    };
    addInstrument(inst);
    toast.success(`Instrument ${newId} added.`);
    setOpen(false);
    setName(""); setCategory(""); setLocation(""); setCost(""); setDescription("");
  };

  return (
    <div className="p-4 sm:p-6 space-y-6">
      <div className="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3">
        <h1 className="text-lg sm:text-xl font-bold">Instruments</h1>
        <Dialog open={open} onOpenChange={setOpen}>
          <DialogTrigger asChild>
            <Button size="sm"><Plus className="mr-2 h-4 w-4" /> Add Instrument</Button>
          </DialogTrigger>
          <DialogContent className="max-w-md">
            <DialogHeader><DialogTitle>Add New Instrument</DialogTitle></DialogHeader>
            <div className="space-y-4">
              <div className="space-y-1"><Label>Instrument Name *</Label><Input value={name} onChange={(e) => setName(e.target.value)} className="bg-background" /></div>
              <div className="space-y-1"><Label>Category *</Label>
                <Select value={category} onValueChange={setCategory}>
                  <SelectTrigger className="bg-background"><SelectValue placeholder="Select" /></SelectTrigger>
                  <SelectContent>{categories.map((c) => <SelectItem key={c} value={c}>{c}</SelectItem>)}<SelectItem value="Other">Other</SelectItem></SelectContent>
                </Select>
              </div>
              <div className="space-y-1"><Label>Location</Label><Input value={location} onChange={(e) => setLocation(e.target.value)} className="bg-background" /></div>
              <div className="space-y-1"><Label>Usage Cost</Label><Input value={cost} onChange={(e) => setCost(e.target.value)} placeholder="₹500/hour" className="bg-background" /></div>
              <div className="space-y-1"><Label>Status</Label>
                <Select value={status} onValueChange={(v: "available" | "booked" | "blocked") => setStatus(v)}>
                  <SelectTrigger className="bg-background"><SelectValue /></SelectTrigger>
                  <SelectContent><SelectItem value="available">Available</SelectItem><SelectItem value="booked">Booked</SelectItem><SelectItem value="blocked">Blocked</SelectItem></SelectContent>
                </Select>
              </div>
              <div className="space-y-1"><Label>Description</Label><Textarea value={description} onChange={(e) => setDescription(e.target.value)} className="bg-background" /></div>
              <Button onClick={handleAdd} className="w-full">Add Instrument</Button>
            </div>
          </DialogContent>
        </Dialog>
      </div>

      <Input placeholder="Search by name or ID..." value={search} onChange={(e) => setSearch(e.target.value)} className="max-w-sm bg-card" />

      {/* Mobile card view */}
      <div className="sm:hidden space-y-3">
        {filtered.slice(0, 20).map((inst) => (
          <div key={inst.id} className="bg-card rounded-lg card-shadow p-3 space-y-1.5">
            <div className="flex items-center justify-between">
              <span className="font-mono tabular-nums text-xs text-muted-foreground">{inst.id}</span>
              <span className={`text-[10px] px-2 py-0.5 rounded-full ${
                inst.status === "available" ? "bg-status-available/10 text-status-available" :
                inst.status === "booked" ? "bg-status-booked/10 text-status-booked" :
                "bg-status-blocked/10 text-status-blocked"
              }`}>{inst.status}</span>
            </div>
            <p className="text-sm font-medium truncate">{inst.name}</p>
            <div className="flex items-center justify-between">
              <span className="text-xs text-muted-foreground">{inst.category}</span>
              <Button variant="ghost" size="icon" className="h-7 w-7 text-muted-foreground hover:text-destructive" onClick={() => { deleteInstrument(inst.id); toast.info(`${inst.id} deleted.`); }}>
                <Trash2 className="h-3 w-3" />
              </Button>
            </div>
          </div>
        ))}
      </div>

      {/* Desktop table */}
      <div className="hidden sm:block bg-card rounded-lg card-shadow overflow-x-auto">
        <table className="w-full text-[13px]">
          <thead>
            <tr className="border-b text-left text-muted-foreground">
              <th className="p-3 font-medium">ID</th>
              <th className="p-3 font-medium">Name</th>
              <th className="p-3 font-medium">Category</th>
              <th className="p-3 font-medium">Status</th>
              <th className="p-3 font-medium">Cost</th>
              <th className="p-3 font-medium text-right">Actions</th>
            </tr>
          </thead>
          <tbody>
            {filtered.slice(0, 20).map((inst) => (
              <tr key={inst.id} className="border-b last:border-0 hover:bg-muted/50 transition-colors h-10">
                <td className="p-3 font-mono tabular-nums text-xs">{inst.id}</td>
                <td className="p-3 max-w-[200px] truncate">{inst.name}</td>
                <td className="p-3 text-muted-foreground">{inst.category}</td>
                <td className="p-3">
                  <span className={`text-xs px-2 py-0.5 rounded-full ${
                    inst.status === "available" ? "bg-status-available/10 text-status-available" :
                    inst.status === "booked" ? "bg-status-booked/10 text-status-booked" :
                    "bg-status-blocked/10 text-status-blocked"
                  }`}>{inst.status}</span>
                </td>
                <td className="p-3 font-mono tabular-nums text-xs">{inst.usageCost}</td>
                <td className="p-3 text-right">
                  <Button variant="ghost" size="icon" className="h-7 w-7 text-muted-foreground hover:text-destructive" onClick={() => { deleteInstrument(inst.id); toast.info(`${inst.id} deleted.`); }}>
                    <Trash2 className="h-3 w-3" />
                  </Button>
                </td>
              </tr>
            ))}
          </tbody>
        </table>
      </div>
    </div>
  );
};

export default AdminInstruments;
