import { useState } from "react";
import { useLocation, useNavigate, Link } from "react-router-dom";
import { MainLayout } from "@/components/layout/MainLayout";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { toast } from "sonner";
import { login } from "@/api/services/authService";
import { useBookingStore } from "@/store/bookingStore";

const Login = () => {
  const [email, setEmail] = useState("");
  const [password, setPassword] = useState("");
  const [loading, setLoading] = useState(false);
  const navigate = useNavigate();
  const location = useLocation() as any;
  const setAuthState = useBookingStore((s) => s.setAuthState);

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!email || !password) {
      toast.error("Please enter email and password");
      return;
    }

    try {
      setLoading(true);
      const res = await login({ email, password });
      const data = res?.data;
      if (data?.token && data?.user) {
        setAuthState({ user: data.user, token: data.token, isAdmin: false, otpVerified: !!data.otpVerified });
        toast.success("Logged in successfully");
        const redirectTo = location.state?.from?.pathname || "/";
        navigate(redirectTo, { replace: true });
      }
    } catch (err: any) {
      const code = err?.response?.data?.code;
      if (code === "email_not_verified") {
        toast.error("Please verify your email via OTP before logging in.");
      } else {
        toast.error("Invalid credentials");
      }
    } finally {
      setLoading(false);
    }
  };

  return (
    <MainLayout>
      <div className="container max-w-md py-10">
        <h1 className="text-2xl font-bold mb-4">Login</h1>
        <form onSubmit={handleSubmit} className="space-y-4">
          <div>
            <Label htmlFor="email">Email</Label>
            <Input id="email" type="email" value={email} onChange={(e) => setEmail(e.target.value)} required />
          </div>
          <div>
            <Label htmlFor="password">Password</Label>
            <Input id="password" type="password" value={password} onChange={(e) => setPassword(e.target.value)} required />
          </div>
          <Button type="submit" className="w-full" disabled={loading}>
            {loading ? "Logging in..." : "Login"}
          </Button>
          <div className="flex justify-between text-xs mt-2">
            <Link to="/signup" className="underline">Create account</Link>
            <Link to="/forgot-password" className="underline">Forgot password?</Link>
          </div>
        </form>
      </div>
    </MainLayout>
  );
};

export default Login;
