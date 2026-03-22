import { useEffect, useState } from "react";
import { MainLayout } from "@/components/layout/MainLayout";
import { getUserProfile } from "@/api/services/authService";
import { toast } from "sonner";

interface UserProfile {
  name: string;
  email: string;
  phone?: string | null;
  profile_picture?: string | null;
}

const Profile = () => {
  const [profile, setProfile] = useState<UserProfile | null>(null);
  const [loading, setLoading] = useState(false);

  useEffect(() => {
    const loadProfile = async () => {
      setLoading(true);
      try {
        const res = await getUserProfile();
        setProfile(res?.data?.user ?? null);
      } catch (err) {
        console.error("Failed to load profile", err);
        toast.error("Unable to load profile.");
      } finally {
        setLoading(false);
      }
    };

    loadProfile();
  }, []);

  return (
    <MainLayout>
      <div className="container max-w-xl py-8 space-y-4">
        <h1 className="text-2xl font-bold">My Profile</h1>
        {loading ? (
          <p className="text-sm text-muted-foreground">Loading profile...</p>
        ) : !profile ? (
          <p className="text-sm text-muted-foreground">No profile data available.</p>
        ) : (
          <div className="bg-card rounded-lg card-shadow p-4 flex items-center gap-4">
            {profile.profile_picture ? (
              <img
                src={profile.profile_picture}
                alt={profile.name}
                className="h-14 w-14 rounded-full object-cover"
              />
            ) : (
              <div className="h-14 w-14 rounded-full bg-accent/20 flex items-center justify-center text-lg font-semibold">
                {profile.name.charAt(0).toUpperCase()}
              </div>
            )}
            <div className="space-y-1 text-sm">
              <p className="font-semibold">{profile.name}</p>
              <p className="text-muted-foreground">{profile.email}</p>
              {profile.phone && <p className="text-muted-foreground">{profile.phone}</p>}
            </div>
          </div>
        )}
      </div>
    </MainLayout>
  );
};

export default Profile;
