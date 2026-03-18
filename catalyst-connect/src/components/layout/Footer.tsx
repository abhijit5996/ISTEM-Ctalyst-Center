import { Microscope } from "lucide-react";

export function Footer() {
  return (
    <footer className="border-t bg-card mt-auto">
      <div className="container py-8 flex flex-col sm:flex-row items-center justify-between gap-4 text-sm text-muted-foreground">
        <div className="flex items-center gap-2">
          <Microscope className="h-4 w-4 text-accent" />
          <span>ISTEM Catalyst Center</span>
        </div>
        <p>Precision Instrumentation on Demand.</p>
      </div>
    </footer>
  );
}
