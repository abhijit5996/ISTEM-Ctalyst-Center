import { useState, useEffect } from "react";
import { useLocation, useNavigate } from "react-router-dom";
import { MainLayout } from "@/components/layout/MainLayout";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { toast } from "sonner";
import { adminSignup, getAdminExists } from "@/api/services/authService";
import { useBookingStore } from "@/store/bookingStore";

const AdminSignup = () => {
  const [username, setUsername] = useState("");
  const [password, setPassword] = useState("");
  const [loading, setLoading] = useState(false);
  const [checking, setChecking] = useState(true);
  const navigate = useNavigate();
  const location = useLocation() as any;
  const setAuthState = useBookingStore((s) => s.setAuthState);

  useEffect(() => {
    const checkAdmin = async () => {
      try {
        const res = await getAdminExists();
        if (res?.data?.exists) {
          navigate("/admin/login", { replace: true });
        }
      } catch {
        // On error, be safe and send to login
        navigate("/admin/login", { replace: true });
      } finally {
        setChecking(false);
      }
    };

    checkAdmin();
  }, [navigate]);

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!username || !password) {
      toast.error("Username and password are required");
      return;
    }

    try {
      setLoading(true);
      const res = await adminSignup({ username, password });
      const data = res?.data;
      if (data?.token && data?.admin) {
        setAuthState({
          user: { id: data.admin.id, name: data.admin.username, email: "admin", phone: null, profile_picture: null },
          token: data.token,
          isAdmin: true,
          otpVerified: true,
        });
        toast.success("Admin account created");
        const redirectTo = location.state?.from?.pathname || "/admin";
        navigate(redirectTo, { replace: true });
      }
    } catch (err: any) {
      if (err?.response?.status === 403) {
        toast.error("Admin already exists. Please login instead.");
        navigate("/admin/login", { replace: true });
      } else {
        toast.error("Failed to create admin account");
      }
    } finally {
      setLoading(false);
    }
  };

  return (
    <MainLayout>
      <div className="container max-w-md py-10">
        <h1 className="text-2xl font-bold mb-4">Admin Signup</h1>
        {checking ? (
          <p className="text-sm text-muted-foreground">Checking admin status...</p>
        ) : (
          <form onSubmit={handleSubmit} className="space-y-4">
            <div>
              <Label htmlFor="username">Username</Label>
              <Input id="username" value={username} onChange={(e) => setUsername(e.target.value)} required />
            </div>
            <div>
              <Label htmlFor="password">Password</Label>
              <Input id="password" type="password" value={password} onChange={(e) => setPassword(e.target.value)} required />
            </div>
            <Button type="submit" className="w-full" disabled={loading}>
              {loading ? "Creating admin..." : "Create Admin Account"}
            </Button>
          </form>
        )}
      </div>
    </MainLayout>
  );
};

export default AdminSignup;
