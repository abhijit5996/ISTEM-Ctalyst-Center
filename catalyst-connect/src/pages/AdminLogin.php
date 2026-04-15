import { useState } from "react";
import { useLocation, useNavigate } from "react-router-dom";
import { MainLayout } from "@/components/layout/MainLayout";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { toast } from "sonner";
import { adminLogin } from "@/api/services/authService";
import { useBookingStore } from "@/store/bookingStore";

const AdminLogin = () => {
  const [username, setUsername] = useState("");
  const [password, setPassword] = useState("");
  const [loading, setLoading] = useState(false);
  const navigate = useNavigate();
  const location = useLocation() as any;
  const setAuthState = useBookingStore((s) => s.setAuthState);

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!username || !password) {
      toast.error("Username and password are required");
      return;
    }

    try {
      setLoading(true);
      const res = await adminLogin({ username, password });
      const data = res?.data;
      if (data?.token && data?.admin) {
        // For admin, we only track minimal identity
        setAuthState({
          user: { id: data.admin.id, name: data.admin.username, email: "admin", phone: null, profile_picture: null },
          token: data.token,
          isAdmin: true,
          otpVerified: true,
        });
        toast.success("Admin login successful");
        const redirectTo = location.state?.from?.pathname || "/admin";
        navigate(redirectTo, { replace: true });
      }
    } catch {
      toast.error("Invalid admin credentials");
    } finally {
      setLoading(false);
    }
  };

  return (
    <MainLayout>
      <div className="container max-w-md py-10">
        <h1 className="text-2xl font-bold mb-4">Admin Login</h1>
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
            {loading ? "Logging in..." : "Login as Admin"}
          </Button>
        </form>
      </div>
    </MainLayout>
  );
};

export default AdminLogin;
