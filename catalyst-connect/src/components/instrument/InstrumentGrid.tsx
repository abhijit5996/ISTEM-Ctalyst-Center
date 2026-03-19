import { useState, useMemo, useEffect } from "react";
import { useBookingStore } from "@/store/bookingStore";
import { InstrumentCard } from "@/components/instrument/InstrumentCard";
import { FilterBar } from "@/components/instrument/FilterBar";

const ITEMS_PER_PAGE = Number.MAX_SAFE_INTEGER;

export function InstrumentGrid() {
  const instruments = useBookingStore((s) => s.instruments);
  const loading = useBookingStore((s) => s.loadingInstruments);
  const fetchInstruments = useBookingStore((s) => s.fetchInstruments);

  const [search, setSearch] = useState("");
  const [category, setCategory] = useState("all");
  const [status, setStatus] = useState("all");

  // ✅ IMPORTANT: FETCH DATA ON LOAD
  useEffect(() => {
    fetchInstruments();
  }, []);

  const filtered = useMemo(() => {
    return instruments.filter((i) => {
      const matchSearch =
        !search ||
        i.name.toLowerCase().includes(search.toLowerCase()) ||
        i.description.toLowerCase().includes(search.toLowerCase());

      const matchCat = category === "all" || i.category === category;
      const matchStatus = status === "all" || i.status === status;

      return matchSearch && matchCat && matchStatus;
    });
  }, [instruments, search, category, status]);

  const paginated = filtered.slice(0, ITEMS_PER_PAGE);

  if (loading) {
    return <div className="text-center p-10">Loading instruments...</div>;
  }

  if (!loading && instruments.length === 0) {
    return <div className="text-center p-10">No instruments available.</div>;
  }

  return (
    <div className="space-y-6">
      <FilterBar
        search={search}
        onSearchChange={(v) => setSearch(v)}
        category={category}
        onCategoryChange={(v) => setCategory(v)}
        status={status}
        onStatusChange={(v) => setStatus(v)}
      />

      <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
        {paginated.map((inst, idx) => (
          <InstrumentCard key={inst.id} instrument={inst} index={idx} />
        ))}
      </div>

    </div>
  );
}