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
  const [confirmPassword, setConfirmPassword] = useState("");
  const [loading, setLoading] = useState(false);
  const navigate = useNavigate();

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    console.log("🔵 [Signup.tsx] Step 1: Form submission started");
    console.log("🔵 [Signup.tsx] Form data:", { name, email, phone, password: "***", confirmPassword: "***" });
    
    if (!name || !email || !password || !confirmPassword) {
      console.log("🔴 [Signup.tsx] Step 2: Validation failed - missing fields");
      toast.error("All fields are required");
      return;
    }

    if (password !== confirmPassword) {
      console.log("🔴 [Signup.tsx] Step 2: Validation failed - passwords do not match");
      toast.error("Passwords do not match");
      return;
    }

    if (password.length < 8) {
      console.log("🔴 [Signup.tsx] Step 2: Validation failed - password too short");
      toast.error("Password must be at least 8 characters");
      return;
    }

    try {
      setLoading(true);
      console.log("🔵 [Signup.tsx] Step 2: Validation passed, calling signup API");
      
      const res = await signup({ name, email, phone, password, password_confirmation: confirmPassword });
      
      console.log("🟢 [Signup.tsx] Step 3: API response received");
      console.log("🟢 [Signup.tsx] Response:", res?.data);
      
      const targetEmail = res?.data?.email || email;
      console.log("🟢 [Signup.tsx] Step 4: OTP sent successfully to", targetEmail);
      
      toast.success("OTP sent to your email. Please verify.");
      navigate(`/verify-otp?email=${encodeURIComponent(targetEmail)}`);
    } catch (err: any) {
      console.log("🔴 [Signup.tsx] Step 3: API error caught");
      console.log("🔴 [Signup.tsx] Error status:", err?.response?.status);
      console.log("🔴 [Signup.tsx] Error data:", err?.response?.data);
      console.log("🔴 [Signup.tsx] Full error:", err);
      
      if (err?.response?.status === 422) {
        // Show detailed validation errors
        const errors = err?.response?.data?.errors || err?.response?.data?.message;
        if (errors && typeof errors === 'object') {
          const errorMessages = Object.values(errors).flat().join(", ");
          console.log("🔴 [Signup.tsx] Validation errors:", errorMessages);
          toast.error(`Validation error: ${errorMessages}`);
        } else if (typeof errors === 'string') {
          console.log("🔴 [Signup.tsx] Validation error:", errors);
          toast.error(errors);
        } else {
          console.log("🔴 [Signup.tsx] Validation failed");
          toast.error("Validation failed. Please check your input.");
        }
      } else if (err?.response?.status === 409) {
        console.log("🔴 [Signup.tsx] Email already in use");
        toast.error("Email already in use");
      } else if (err?.response?.status === 500) {
        const serverMessage = err?.response?.data?.message;
        console.log("🔴 [Signup.tsx] Server error:", serverMessage);
        toast.error(serverMessage || "Server error. Please try again later.");
      } else if (err?.code === 'ECONNABORTED') {
        console.log("🔴 [Signup.tsx] Request timeout");
        toast.error("Request timeout - API server may be down");
      } else {
        console.log("🔴 [Signup.tsx] Other error:", err?.message);
        console.log("🔴 [Signup.tsx] Error status:", err?.response?.status);
        toast.error(err?.message || "Signup failed");
      }
    } finally {
      setLoading(false);
      console.log("🔵 [Signup.tsx] Form submission completed");
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
            <p className="text-xs text-muted-foreground mt-1">Minimum 8 characters</p>
          </div>
          <div>
            <Label htmlFor="confirmPassword">Confirm Password</Label>
            <Input id="confirmPassword" type="password" value={confirmPassword} onChange={(e) => setConfirmPassword(e.target.value)} required />
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
