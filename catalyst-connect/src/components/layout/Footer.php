import { useEffect, useState } from "react";
import { Microscope } from "lucide-react";
import { Link } from "react-router-dom";
import { getAdminExists } from "@/api/services/authService";

export function Footer() {
  const [adminExists, setAdminExists] = useState<boolean | null>(null);

  useEffect(() => {
    let isMounted = true;
    getAdminExists()
      .then((res) => {
        if (!isMounted) return;
        const exists = !!res?.data?.exists;
        setAdminExists(exists);
      })
      .catch(() => {
        if (!isMounted) return;
        // On error, assume admin exists so we don't expose signup incorrectly
        setAdminExists(true);
      });

    return () => {
      isMounted = false;
    };
  }, []);

  const adminLink = adminExists === false ? "/admin/signup" : "/admin/login";

  return (
    <footer className="border-t bg-card mt-auto">
      <div className="container py-8 flex flex-col sm:flex-row items-center justify-between gap-4 text-sm text-muted-foreground">
        <div className="flex items-center gap-2">
          <Microscope className="h-4 w-4 text-accent" />
          <span>ISTEM Catalyst Center</span>
        </div>
        <div className="flex items-center gap-4">
          <p>Precision Instrumentation on Demand.</p>
          <Link to={adminLink} className="text-xs underline text-muted-foreground hover:text-foreground">
            Admin Access
          </Link>
        </div>
      </div>
    </footer>
  );
}
