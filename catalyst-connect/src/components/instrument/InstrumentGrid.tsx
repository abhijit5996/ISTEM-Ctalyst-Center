import { useState, useMemo, useEffect } from "react";
import { useBookingStore } from "@/store/bookingStore";
import { InstrumentCard } from "@/components/instrument/InstrumentCard";
import { FilterBar } from "@/components/instrument/FilterBar";
import { Button } from "@/components/ui/button";
import { ChevronLeft, ChevronRight } from "lucide-react";

const ITEMS_PER_PAGE = 12;

export function InstrumentGrid() {
  const instruments = useBookingStore((s) => s.instruments);
  const loading = useBookingStore((s) => s.loadingInstruments);
  const fetchInstruments = useBookingStore((s) => s.fetchInstruments);

  const [search, setSearch] = useState("");
  const [category, setCategory] = useState("all");
  const [status, setStatus] = useState("all");
  const [page, setPage] = useState(1);

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

  const totalPages = Math.ceil(filtered.length / ITEMS_PER_PAGE);
  const paginated = filtered.slice(
    (page - 1) * ITEMS_PER_PAGE,
    page * ITEMS_PER_PAGE
  );

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
        onSearchChange={(v) => {
          setSearch(v);
          setPage(1);
        }}
        category={category}
        onCategoryChange={(v) => {
          setCategory(v);
          setPage(1);
        }}
        status={status}
        onStatusChange={(v) => {
          setStatus(v);
          setPage(1);
        }}
      />

      <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
        {paginated.map((inst, idx) => (
          <InstrumentCard key={inst.id} instrument={inst} index={idx} />
        ))}
      </div>

      {totalPages > 1 && (
        <div className="flex items-center justify-center gap-2 pt-4">
          <Button
            variant="outline"
            size="sm"
            disabled={page === 1}
            onClick={() => setPage(page - 1)}
          >
            <ChevronLeft className="h-4 w-4" />
          </Button>

          {Array.from({ length: totalPages }, (_, i) => (
            <Button
              key={i}
              variant={page === i + 1 ? "default" : "outline"}
              size="sm"
              onClick={() => setPage(i + 1)}
            >
              {i + 1}
            </Button>
          ))}

          <Button
            variant="outline"
            size="sm"
            disabled={page === totalPages}
            onClick={() => setPage(page + 1)}
          >
            <ChevronRight className="h-4 w-4" />
          </Button>
        </div>
      )}
    </div>
  );
}