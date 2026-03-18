import { Link, Outlet, useLocation } from "react-router-dom";
import { useEffect, useState } from "react";
import { LayoutDashboard, Microscope as MicroscopeIcon, Calendar, ListOrdered, ArrowLeft, BarChart3, Menu, X } from "lucide-react";
import { cn } from "@/lib/utils";
import { useBookingStore } from "@/store/bookingStore";
import { Button } from "@/components/ui/button";

const navItems = [
  { to: "/admin", label: "Dashboard", icon: LayoutDashboard },
  { to: "/admin/instruments", label: "Instruments", icon: MicroscopeIcon },
  { to: "/admin/bookings", label: "Bookings", icon: Calendar },
  { to: "/admin/queue", label: "Queue List", icon: ListOrdered },
  { to: "/admin/analytics", label: "Analytics", icon: BarChart3 },
];

const AdminLayout = () => {
  const location = useLocation();
  const [mobileOpen, setMobileOpen] = useState(false);

  const fetchBookings = useBookingStore((s) => s.fetchBookings);

  useEffect(() => {
    fetchBookings();
  }, [fetchBookings]);

  return (
    <div className="flex min-h-screen">
      {/* Desktop Sidebar */}
      <aside className="hidden md:flex w-60 flex-col bg-sidebar border-r border-sidebar-border">
        <div className="p-4 border-b border-sidebar-border">
          <Link to="/" className="flex items-center gap-2 text-sidebar-foreground text-sm">
            <ArrowLeft className="h-4 w-4" />
            Back to Portal
          </Link>
        </div>
        <div className="p-3">
          <p className="px-3 py-1 text-[10px] font-medium uppercase tracking-widest text-sidebar-foreground/50">Administration</p>
        </div>
        <nav className="flex-1 px-3 space-y-0.5">
          {navItems.map((item) => {
            const active = location.pathname === item.to;
            return (
              <Link
                key={item.to}
                to={item.to}
                className={cn(
                  "flex items-center gap-3 px-3 py-2 text-[13px] rounded-md transition-colors",
                  active
                    ? "bg-sidebar-accent text-sidebar-primary font-medium"
                    : "text-sidebar-foreground/70 hover:text-sidebar-foreground hover:bg-sidebar-accent/50"
                )}
              >
                <item.icon className="h-4 w-4" />
                {item.label}
              </Link>
            );
          })}
        </nav>
      </aside>

      {/* Mobile Overlay Sidebar */}
      {mobileOpen && (
        <div className="fixed inset-0 z-50 md:hidden">
          <div className="absolute inset-0 bg-foreground/40 backdrop-blur-sm" onClick={() => setMobileOpen(false)} />
          <aside className="absolute left-0 top-0 bottom-0 w-64 bg-sidebar border-r border-sidebar-border flex flex-col animate-slide-in-right">
            <div className="p-4 border-b border-sidebar-border flex items-center justify-between">
              <Link to="/" className="flex items-center gap-2 text-sidebar-foreground text-sm">
                <ArrowLeft className="h-4 w-4" />
                Back to Portal
              </Link>
              <Button variant="ghost" size="icon" className="text-sidebar-foreground h-8 w-8" onClick={() => setMobileOpen(false)}>
                <X className="h-4 w-4" />
              </Button>
            </div>
            <div className="p-3">
              <p className="px-3 py-1 text-[10px] font-medium uppercase tracking-widest text-sidebar-foreground/50">Administration</p>
            </div>
            <nav className="flex-1 px-3 space-y-0.5">
              {navItems.map((item) => {
                const active = location.pathname === item.to;
                return (
                  <Link
                    key={item.to}
                    to={item.to}
                    onClick={() => setMobileOpen(false)}
                    className={cn(
                      "flex items-center gap-3 px-3 py-2.5 text-sm rounded-md transition-colors",
                      active
                        ? "bg-sidebar-accent text-sidebar-primary font-medium"
                        : "text-sidebar-foreground/70 hover:text-sidebar-foreground hover:bg-sidebar-accent/50"
                    )}
                  >
                    <item.icon className="h-4 w-4" />
                    {item.label}
                  </Link>
                );
              })}
            </nav>
          </aside>
        </div>
      )}

      {/* Content */}
      <div className="flex-1 flex flex-col min-w-0">
        {/* Mobile header */}
        <header className="md:hidden flex items-center gap-3 p-3 border-b bg-card sticky top-0 z-40">
          <Button variant="ghost" size="icon" className="h-8 w-8" onClick={() => setMobileOpen(true)}>
            <Menu className="h-4 w-4" />
          </Button>
          <span className="text-sm font-semibold truncate">
            {navItems.find((n) => n.to === location.pathname)?.label || "Admin"}
          </span>
        </header>
        <main className="flex-1 bg-background overflow-x-hidden">
          <Outlet />
        </main>
      </div>
    </div>
  );
};

export default AdminLayout;
