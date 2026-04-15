import { useState } from "react";
import { useLocation, useNavigate } from "react-router-dom";
import { MainLayout } from "@/components/layout/MainLayout";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { toast } from "sonner";
import { resetPassword, verifyResetOtp } from "@/api/services/authService";

const ResetPassword = () => {
  const location = useLocation();
  const params = new URLSearchParams(location.search);
  const emailFromQuery = params.get("email") || "";
  const [email] = useState(emailFromQuery);
  const otpFromState = (location.state as any)?.otp || "";
  const [password, setPassword] = useState("");
  const [confirmPassword, setConfirmPassword] = useState("");
  const [loading, setLoading] = useState(false);
  const navigate = useNavigate();

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!email || !otpFromState || !password || !confirmPassword) {
      toast.error("Missing data. Please restart the reset process.");
      navigate("/forgot-password");
      return;
    }
    if (password !== confirmPassword) {
      toast.error("Passwords do not match");
      return;
    }

    try {
      setLoading(true);
      await resetPassword({ email, otp: otpFromState, password, password_confirmation: confirmPassword });
      toast.success("Password updated. You can now login.");
      navigate("/login");
    } catch (err: any) {
      const code = err?.response?.data?.code;
      if (code === "otp_expired") {
        toast.error("OTP expired. Please request a new one.");
      } else if (code === "otp_invalid") {
        toast.error("Invalid OTP");
      } else {
        toast.error("Failed to reset password");
      }
    } finally {
      setLoading(false);
    }
  };

  return (
    <MainLayout>
      <div className="container max-w-md py-10">
        <h1 className="text-2xl font-bold mb-4">Reset Password</h1>
        <form onSubmit={handleSubmit} className="space-y-4">
          <div>
            <Label htmlFor="password">New Password</Label>
            <Input id="password" type="password" value={password} onChange={(e) => setPassword(e.target.value)} />
          </div>
          <div>
            <Label htmlFor="password_confirmation">Confirm Password</Label>
            <Input
              id="password_confirmation"
              type="password"
              value={confirmPassword}
              onChange={(e) => setConfirmPassword(e.target.value)}
            />
          </div>
          <Button type="submit" className="w-full" disabled={loading}>
            {loading ? "Updating..." : "Reset Password"}
          </Button>
        </form>
      </div>
    </MainLayout>
  );
};

export default ResetPassword;
