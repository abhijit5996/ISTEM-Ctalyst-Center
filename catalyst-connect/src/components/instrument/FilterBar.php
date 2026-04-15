import { useMemo } from "react";
import { Search } from "lucide-react";
import { useBookingStore } from "@/store/bookingStore";
import { Input } from "@/components/ui/input";
import { Button } from "@/components/ui/button";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";

interface FilterBarProps {
  search: string;
  onSearchChange: (v: string) => void;
  category: string;
  onCategoryChange: (v: string) => void;
  status: string;
  onStatusChange: (v: string) => void;
}

export function FilterBar({ search, onSearchChange, category, onCategoryChange, status, onStatusChange }: FilterBarProps) {
  const instruments = useBookingStore((s) => s.instruments);
  const categories = useMemo(() => {
    return Array.from(new Set(instruments.map((i) => i.category).filter(Boolean)));
  }, [instruments]);

  return (
    <div className="flex flex-col sm:flex-row gap-3">
      <div className="relative flex-1">
        <Search className="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground" />
        <Input
          placeholder="Search instruments..."
          value={search}
          onChange={(e) => onSearchChange(e.target.value)}
          className="pl-9 bg-card"
        />
      </div>
      <Select value={category} onValueChange={onCategoryChange}>
        <SelectTrigger className="w-full sm:w-[200px] bg-card">
          <SelectValue placeholder="All Categories" />
        </SelectTrigger>
        <SelectContent>
          <SelectItem value="all">All Categories</SelectItem>
          {categories.map((c) => (
            <SelectItem key={c} value={c}>{c}</SelectItem>
          ))}
        </SelectContent>
      </Select>
      <Select value={status} onValueChange={onStatusChange}>
        <SelectTrigger className="w-full sm:w-[160px] bg-card">
          <SelectValue placeholder="All Status" />
        </SelectTrigger>
        <SelectContent>
          <SelectItem value="all">All Status</SelectItem>
          <SelectItem value="available">Available</SelectItem>
          <SelectItem value="booked">Booked</SelectItem>
          <SelectItem value="blocked">Blocked</SelectItem>
        </SelectContent>
      </Select>
    </div>
  );
}
