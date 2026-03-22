import { useState } from "react";
import { useLocation, useNavigate } from "react-router-dom";
import { MainLayout } from "@/components/layout/MainLayout";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { toast } from "sonner";
import { verifyResetOtp } from "@/api/services/authService";

const VerifyResetOTP = () => {
  const location = useLocation();
  const params = new URLSearchParams(location.search);
  const email = params.get("email") || "";
  const [otp, setOtp] = useState("");
  const [loading, setLoading] = useState(false);
  const navigate = useNavigate();

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!email || !otp) {
      toast.error("Email or OTP missing");
      return;
    }

    try {
      setLoading(true);
      await verifyResetOtp({ email, otp });
      toast.success("OTP verified. You can now reset password.");
      navigate(`/reset-password?email=${encodeURIComponent(email)}`, { state: { otp } });
    } catch (err: any) {
      const code = err?.response?.data?.code;
      if (code === "otp_expired") {
        toast.error("OTP expired. Please request a new one.");
      } else if (code === "otp_invalid") {
        toast.error("Invalid OTP");
      } else {
        toast.error("Failed to verify OTP");
      }
    } finally {
      setLoading(false);
    }
  };

  return (
    <MainLayout>
      <div className="container max-w-md py-10">
        <h1 className="text-2xl font-bold mb-4">Verify Reset OTP</h1>
        <form onSubmit={handleSubmit} className="space-y-4">
          <div>
            <Label htmlFor="otp">OTP</Label>
            <Input
              id="otp"
              value={otp}
              onChange={(e) => setOtp(e.target.value.replace(/[^0-9]/g, ""))}
              maxLength={6}
            />
          </div>
          <Button type="submit" className="w-full" disabled={loading}>
            {loading ? "Verifying..." : "Verify OTP"}
          </Button>
        </form>
      </div>
    </MainLayout>
  );
};

export default VerifyResetOTP;
