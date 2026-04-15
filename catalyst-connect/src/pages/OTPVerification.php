import { useEffect, useState } from "react";
import { useLocation, useNavigate } from "react-router-dom";
import { MainLayout } from "@/components/layout/MainLayout";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { toast } from "sonner";
import { sendOtp, verifyOtp } from "@/api/services/authService";
import { useBookingStore } from "@/store/bookingStore";

const OTPVerification = () => {
  const location = useLocation();
  const navigate = useNavigate();
  const params = new URLSearchParams(location.search);
  const emailFromQuery = params.get("email") || "";
  const [email] = useState(emailFromQuery);
  const [otp, setOtp] = useState("");
  const [secondsLeft, setSecondsLeft] = useState(5 * 60);
  const [loading, setLoading] = useState(false);
  const setAuthState = useBookingStore((s) => s.setAuthState);

  useEffect(() => {
    const timer = setInterval(() => {
      setSecondsLeft((s) => (s > 0 ? s - 1 : 0));
    }, 1000);
    return () => clearInterval(timer);
  }, []);

  const handleVerify = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!email || !otp) {
      toast.error("Email and OTP are required");
      return;
    }

    try {
      setLoading(true);
      const res = await verifyOtp({ email, otp });
      const data = res?.data;
      if (data?.token && data?.user) {
        setAuthState({ user: data.user, token: data.token, isAdmin: false, otpVerified: true });
      }
      toast.success("Email verified successfully");
      navigate("/");
    } catch (err: any) {
      const code = err?.response?.data?.code;
      if (code === "otp_expired") {
        toast.error("OTP expired. Please resend.");
      } else {
        toast.error("Invalid OTP");
      }
    } finally {
      setLoading(false);
    }
  };

  const handleResend = async () => {
    if (!email) return;
    try {
      await sendOtp({ email });
      setSecondsLeft(5 * 60);
      toast.success("OTP resent to your email");
    } catch {
      toast.error("Failed to resend OTP");
    }
  };

  const minutes = Math.floor(secondsLeft / 60);
  const seconds = secondsLeft % 60;

  return (
    <MainLayout>
      <div className="container max-w-md py-10">
        <h1 className="text-2xl font-bold mb-4">Verify Email</h1>
        <p className="text-sm text-muted-foreground mb-4">We have sent a 6-digit OTP to {email}.</p>
        <form onSubmit={handleVerify} className="space-y-4">
          <div>
            <Label htmlFor="otp">OTP</Label>
            <Input
              id="otp"
              value={otp}
              onChange={(e) => setOtp(e.target.value.replace(/[^0-9]/g, ""))}
              maxLength={6}
            />
          </div>
          <Button type="submit" className="w-full" disabled={loading || secondsLeft === 0}>
            {loading ? "Verifying..." : "Verify"}
          </Button>
        </form>
        <div className="mt-3 text-xs flex items-center justify-between">
          <span>
            Expires in {minutes}:{seconds.toString().padStart(2, "0")}
          </span>
          <Button variant="link" size="sm" onClick={handleResend} disabled={secondsLeft > 0}>
            Resend OTP
          </Button>
        </div>
      </div>
    </MainLayout>
  );
};

export default OTPVerification;
