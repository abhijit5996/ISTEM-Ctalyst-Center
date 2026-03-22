import { Radio } from 'lucide-react';
import { useBookingStore } from '@/store/bookingStore';

interface RealtimeStatusIndicatorProps {
  size?: 'sm' | 'md' | 'lg';
  showText?: boolean;
  onlyIcon?: boolean;
}

/**
 * Reusable real-time status indicator component
 * Shows whether live updates are currently active
 * 
 * Usage:
 * <RealtimeStatusIndicator size="md" showText={true} />
 */
export const RealtimeStatusIndicator = ({
  size = 'md',
  showText = true,
  onlyIcon = false,
}: RealtimeStatusIndicatorProps) => {
  const realtimeEnabled = useBookingStore((s) => s.realtimeEnabled);

  const sizeClasses = {
    sm: 'text-xs',
    md: 'text-sm',
    lg: 'text-base',
  };

  const iconSizes = {
    sm: 'h-2 w-2',
    md: 'h-3 w-3',
    lg: 'h-4 w-4',
  };

  const paddingClasses = {
    sm: onlyIcon ? '' : 'px-2 py-1',
    md: onlyIcon ? '' : 'px-3 py-1.5',
    lg: onlyIcon ? '' : 'px-4 py-2',
  };

  if (realtimeEnabled) {
    return (
      <div 
        className={`flex items-center gap-1.5 ${!onlyIcon ? 'text-green-600 bg-green-50 dark:bg-green-950 rounded-full' : ''} ${paddingClasses[size]} ${sizeClasses[size]}`}
      >
        <Radio className={`${iconSizes[size]} animate-pulse text-green-600`} />
        {showText && !onlyIcon && <span>Live Updates</span>}
      </div>
    );
  }

  return (
    <div 
      className={`flex items-center gap-1.5 ${!onlyIcon ? 'text-gray-400 dark:text-gray-600' : ''} ${paddingClasses[size]} ${sizeClasses[size]}`}
    >
      <Radio className={`${iconSizes[size]} opacity-30`} />
      {showText && !onlyIcon && <span>Updates Offline</span>}
    </div>
  );
};
