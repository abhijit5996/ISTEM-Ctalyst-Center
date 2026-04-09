import { useState } from "react";
import { useNavigate, Link } from "react-router-dom";
import { MainLayout } from "@/components/layout/MainLayout";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { toast } from "sonner";
import { signup } from "@/api/services/authService";

const Signup = () => {
  const [name, setName] = useState("");
  const [email, setEmail] = useState("");
  const [phone, setPhone] = useState("");
  const [password, setPassword] = useState("");
  const [loading, setLoading] = useState(false);
  const navigate = useNavigate();

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!name || !email || !password) {
      toast.error("Name, email, and password are required");
      return;
    }

    try {
      setLoading(true);
      const res = await signup({ name, email, phone, password });
      const targetEmail = res?.data?.email || email;
      toast.success("OTP sent to your email. Please verify.");
      navigate(`/verify-otp?email=${encodeURIComponent(targetEmail)}`);
    } catch (err: any) {
      if (err?.response?.status === 422) {
        // Show detailed validation errors
        const errors = err?.response?.data?.errors;
        if (errors) {
          const errorMessages = Object.values(errors).flat().join(", ");
          toast.error(`Validation error: ${errorMessages}`);
        } else {
          toast.error("Email already in use or validation failed");
        }
      } else if (err?.code === 'ECONNABORTED') {
        toast.error("Request timeout - API server may be down");
      } else {
        toast.error(err?.message || "Signup failed");
      }
    } finally {
      setLoading(false);
    }
  };

  return (
    <MainLayout>
      <div className="container max-w-md py-10">
        <h1 className="text-2xl font-bold mb-4">Create Account</h1>
        <form onSubmit={handleSubmit} className="space-y-4">
          <div>
            <Label htmlFor="name">Name</Label>
            <Input id="name" value={name} onChange={(e) => setName(e.target.value)} required />
          </div>
          <div>
            <Label htmlFor="email">Email</Label>
            <Input id="email" type="email" value={email} onChange={(e) => setEmail(e.target.value)} required />
          </div>
          <div>
            <Label htmlFor="phone">Phone</Label>
            <Input id="phone" value={phone} onChange={(e) => setPhone(e.target.value)} />
          </div>
          <div>
            <Label htmlFor="password">Password</Label>
            <Input id="password" type="password" value={password} onChange={(e) => setPassword(e.target.value)} required />
          </div>
          <Button type="submit" className="w-full" disabled={loading}>
            {loading ? "Creating..." : "Sign up"}
          </Button>
          <p className="text-xs mt-2 text-center">
            Already have an account? <Link to="/login" className="underline">Login</Link>
          </p>
        </form>
      </div>
    </MainLayout>
  );
};

export default Signup;
