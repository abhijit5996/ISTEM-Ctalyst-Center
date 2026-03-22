import { Link, useLocation, useNavigate } from "react-router-dom";
import { ShoppingBag, Menu, X, Microscope } from "lucide-react";
import { useState } from "react";
import { useBookingStore } from "@/store/bookingStore";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { ThemeToggle } from "@/components/ThemeToggle";
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuLabel, DropdownMenuSeparator, DropdownMenuTrigger } from "@/components/ui/dropdown-menu";

export function Navbar() {
  const [mobileOpen, setMobileOpen] = useState(false);
  const bag = useBookingStore((s) => s.bag);
   const user = useBookingStore((s) => s.user);
   const isAuthenticated = useBookingStore((s) => s.isAuthenticated);
   const isAdmin = useBookingStore((s) => s.isAdmin);
   const logout = useBookingStore((s) => s.logout);
  const location = useLocation();
   const navigate = useNavigate();

  const links = [
    { to: "/", label: "Instruments" },
    { to: "/bag", label: "Booking Bag" },
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
            <DropdownMenu>
              <DropdownMenuTrigger asChild>
                <Button variant="ghost" size="icon" className="ml-1 rounded-full border">
                  {user?.profile_picture ? (
                    <img
                      src={user.profile_picture}
                      alt={user.name}
                      className="h-7 w-7 rounded-full object-cover"
                    />
                  ) : (
                    <span className="h-7 w-7 rounded-full bg-accent/20 flex items-center justify-center text-xs font-semibold">
                      {(user?.name || (isAdmin ? "A" : "U")).charAt(0).toUpperCase()}
                    </span>
                  )}
                </Button>
              </DropdownMenuTrigger>
              <DropdownMenuContent align="end" className="w-48">
                <DropdownMenuLabel>
                  {isAdmin ? "Admin" : user?.name || "Guest"}
                </DropdownMenuLabel>
                <DropdownMenuSeparator />
                {!isAuthenticated && !isAdmin && (
                  <>
                    <DropdownMenuItem onClick={() => navigate("/login")}>Login</DropdownMenuItem>
                    <DropdownMenuItem onClick={() => navigate("/signup")}>Sign up</DropdownMenuItem>
                    <DropdownMenuSeparator />
                  </>
                )}
                {isAuthenticated && !isAdmin && (
                  <>
                    <DropdownMenuItem onClick={() => navigate("/profile")}>My Profile</DropdownMenuItem>
                    <DropdownMenuItem onClick={() => navigate("/my-bookings")}>My Bookings</DropdownMenuItem>
                    <DropdownMenuItem onClick={() => navigate("/queue-status")}>Queue Status</DropdownMenuItem>
                    <DropdownMenuSeparator />
                  </>
                )}
                {isAdmin && (
                  <>
                    <DropdownMenuItem onClick={() => navigate("/admin")}>Admin Dashboard</DropdownMenuItem>
                    <DropdownMenuSeparator />
                  </>
                )}
                {(isAuthenticated || isAdmin) && (
                  <DropdownMenuItem
                    onClick={() => {
                      logout();
                      navigate("/");
                    }}
                  >
                    Logout
                  </DropdownMenuItem>
                )}
              </DropdownMenuContent>
            </DropdownMenu>
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
