import { motion, type Variants, type Easing } from "framer-motion";
import { ReactNode } from "react";

const ease: Easing = [0.25, 0.1, 0.25, 1];

const pageVariants: Variants = {
  initial: { opacity: 0, y: 20 },
  animate: { opacity: 1, y: 0 },
  exit: { opacity: 0, y: -10 },
};

export function PageTransition({ children }: { children: ReactNode }) {
  return (
    <motion.div
      initial="initial"
      animate="animate"
      exit="exit"
      variants={pageVariants}
      transition={{ duration: 0.35, ease }}
    >
      {children}
    </motion.div>
  );
}

export const staggerContainer: Variants = {
  animate: {
    transition: { staggerChildren: 0.06 },
  },
};

export const fadeInUp: Variants = {
  initial: { opacity: 0, y: 16 },
  animate: { opacity: 1, y: 0, transition: { duration: 0.4, ease } },
};

export const scaleIn: Variants = {
  initial: { opacity: 0, scale: 0.95 },
  animate: { opacity: 1, scale: 1, transition: { duration: 0.3, ease } },
};
