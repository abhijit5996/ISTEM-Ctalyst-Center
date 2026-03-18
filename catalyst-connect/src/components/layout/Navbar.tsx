import { Link, useLocation } from "react-router-dom";
import { ShoppingBag, Menu, X, Microscope } from "lucide-react";
import { useState } from "react";
import { useBookingStore } from "@/store/bookingStore";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { ThemeToggle } from "@/components/ThemeToggle";

export function Navbar() {
  const [mobileOpen, setMobileOpen] = useState(false);
  const bag = useBookingStore((s) => s.bag);
  const location = useLocation();

  const links = [
    { to: "/", label: "Instruments" },
    { to: "/bag", label: "Booking Bag" },
    { to: "/admin", label: "Admin" },
  ];

  return (
    <header className="sticky top-0 z-50 border-b bg-card/80 backdrop-blur-md">
      <div className="container flex h-14 items-center justify-between">
        <Link to="/" className="flex items-center gap-2 font-semibold text-foreground">
          <Microscope className="h-5 w-5 text-accent" />
          <span className="hidden sm:inline">ISTEM Catalyst Center</span>
          <span className="sm:hidden">ISTEM</span>
        </Link>

        <nav className="hidden md:flex items-center gap-1">
          {links.map((l) => (
            <Link
              key={l.to}
              to={l.to}
              className={`px-3 py-1.5 text-sm rounded-md transition-colors ${
                location.pathname === l.to
                  ? "bg-secondary text-foreground font-medium"
                  : "text-muted-foreground hover:text-foreground hover:bg-secondary/50"
              }`}
            >
              {l.label}
            </Link>
          ))}
        </nav>

        <div className="flex items-center gap-1">
          <ThemeToggle />
          <Link to="/bag" className="relative">
            <Button variant="ghost" size="icon" className="relative">
              <ShoppingBag className="h-4 w-4" />
              {bag.length > 0 && (
                <Badge className="absolute -top-1 -right-1 h-4 w-4 p-0 flex items-center justify-center text-[10px] bg-accent text-accent-foreground border-0">
                  {bag.length}
                </Badge>
              )}
            </Button>
          </Link>
          <Button
            variant="ghost"
            size="icon"
            className="md:hidden"
            onClick={() => setMobileOpen(!mobileOpen)}
          >
            {mobileOpen ? <X className="h-4 w-4" /> : <Menu className="h-4 w-4" />}
          </Button>
        </div>
      </div>

      {mobileOpen && (
        <div className="md:hidden border-t bg-card p-4 animate-fade-in">
          {links.map((l) => (
            <Link
              key={l.to}
              to={l.to}
              onClick={() => setMobileOpen(false)}
              className={`block px-3 py-2 text-sm rounded-md mb-1 ${
                location.pathname === l.to
                  ? "bg-secondary text-foreground font-medium"
                  : "text-muted-foreground"
              }`}
            >
              {l.label}
            </Link>
          ))}
        </div>
      )}
    </header>
  );
}
