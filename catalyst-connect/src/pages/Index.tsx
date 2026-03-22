import { Link } from "react-router-dom";
import { MainLayout } from "@/components/layout/MainLayout";
import { InstrumentGrid } from "@/components/instrument/InstrumentGrid";
import { Button } from "@/components/ui/button";
import { ArrowRight, Microscope, Beaker, Cpu, Zap } from "lucide-react";
import { PageTransition, fadeInUp, staggerContainer } from "@/components/PageTransition";
import { motion } from "framer-motion";
import heroImage from "@/assets/hero-lab.jpg";
import { useBookingStore } from "@/store/bookingStore";

const Index = () => {
  const instruments = useBookingStore((s) => s.instruments);
  const categories = [...new Set(instruments.map((i) => i.category))];

  return (
    <MainLayout>
      <PageTransition>
        {/* Hero */}
        <section className="border-b">
          <div className="max-w-7xl mx-auto px-6 py-12 sm:py-16 md:py-24">
            <div className="flex flex-col-reverse gap-10 items-center justify-between lg:flex-row">
              <div className="w-full lg:w-1/2">
                <motion.div
                  className="max-w-xl space-y-4 sm:space-y-6"
                  variants={staggerContainer}
                  initial="initial"
                  animate="animate"
                >
                  <motion.div variants={fadeInUp} className="inline-flex flex-wrap items-center gap-2 text-xs font-medium">
                    <span className="inline-flex items-center gap-2 bg-accent/10 text-accent rounded-full px-3 py-1">
                      <Microscope className="h-3 w-3" />
                      ISTEM Catalyst Center
                    </span>
                    <span className="inline-flex items-center gap-2 bg-secondary/10 text-secondary rounded-full px-3 py-1">
                      <Zap className="h-3 w-3" />
                      Adamas University
                    </span>
                  </motion.div>
                  <motion.h1 variants={fadeInUp} className="text-3xl sm:text-4xl md:text-5xl font-bold tracking-tight text-foreground leading-tight">
                    Precision Instrumentation
                    <br />
                    <span className="text-accent">on Demand</span>
                  </motion.h1>
                  <motion.p variants={fadeInUp} className="text-muted-foreground text-base sm:text-lg leading-relaxed">
                    Browse, book, and manage access to 50+ research instruments.
                  </motion.p>
                  <motion.div variants={fadeInUp} className="flex flex-wrap gap-3">
                    <Button size="lg" asChild className="transition-transform active:scale-95">
                      <a href="#instruments" className="flex items-center gap-2">
                        Browse Instruments <ArrowRight className="h-4 w-4" />
                      </a>
                    </Button>
                  </motion.div>
                </motion.div>
              </div>

              <div className="w-full lg:w-1/2 flex justify-center">
                <div className="w-full max-w-md lg:max-w-lg">
                  <img
                    src={heroImage}
                    alt="Lab illustration for ISTEM Catalyst Center"
                    className="w-full h-auto object-contain rounded-xl shadow-lg"
                  />
                </div>
              </div>
            </div>
          </div>

          <motion.div
            className="grid grid-cols-3 gap-4 sm:gap-6 mt-8 sm:mt-12 max-w-lg mx-auto"
            variants={staggerContainer}
            initial="initial"
            animate="animate"
          >
              {[
                { val: "50+", label: "Instruments" },
                { val: `${categories.length}+`, label: "Categories" },
                { val: "24/7", label: "Booking Access" },
              ].map((s) => (
                <motion.div key={s.label} variants={fadeInUp} className="space-y-1">
                  <p className="text-xl sm:text-2xl font-bold font-mono tabular-nums text-foreground">{s.val}</p>
                  <p className="text-xs text-muted-foreground">{s.label}</p>
                </motion.div>
              ))}
            </motion.div>
        </section>

        {/* Bento Feature Cards */}
        <section className="container py-8 sm:py-12">
          <motion.div
            className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4"
            variants={staggerContainer}
            initial="initial"
            whileInView="animate"
            viewport={{ once: true, margin: "-50px" }}
          >
            {[
              {
                icon: Microscope,
                title: "Advanced Microscopy",
                desc: "SEM, TEM, AFM, Confocal — high-resolution imaging from nano to atomic scale.",
                accent: "from-primary to-accent",
              },
              {
                icon: Beaker,
                title: "Chemical & Bio Analysis",
                desc: "Chromatography, spectroscopy, PCR, and DNA sequencing for comprehensive analysis.",
                accent: "from-accent to-primary",
              },
              {
                icon: Cpu,
                title: "HPC & AI Infrastructure",
                desc: "Supercomputer clusters and GPU deep learning servers for computation-heavy research.",
                accent: "from-primary to-accent",
              },
            ].map((card) => (
              <motion.div
                key={card.title}
                variants={fadeInUp}
                whileHover={{ y: -4, transition: { duration: 0.2 } }}
                className="group relative bg-card rounded-xl card-shadow hover:card-shadow-hover p-6 space-y-3 overflow-hidden transition-shadow"
              >
                <div className={`absolute top-0 left-0 right-0 h-1 bg-gradient-to-r ${card.accent} opacity-0 group-hover:opacity-100 transition-opacity`} />
                <div className="h-10 w-10 rounded-lg bg-accent/10 flex items-center justify-center">
                  <card.icon className="h-5 w-5 text-accent" />
                </div>
                <h3 className="font-semibold text-sm">{card.title}</h3>
                <p className="text-xs text-muted-foreground leading-relaxed">{card.desc}</p>
              </motion.div>
            ))}
          </motion.div>
        </section>

        {/* Stats Row */}
        <section className="border-y bg-card/50">
          <div className="container py-8 sm:py-10">
            <motion.div
              className="grid grid-cols-2 md:grid-cols-4 gap-6 text-center"
              variants={staggerContainer}
              initial="initial"
              whileInView="animate"
              viewport={{ once: true, margin: "-50px" }}
            >
              {[
                { val: "12,000+", label: "Hours of Usage Annually" },
                { val: "92%", label: "Utilization Rate" },
                { val: "1,200+", label: "Active Researchers" },
                { val: "150+", label: "Projects Supported" },
              ].map((s) => (
                <motion.div key={s.label} variants={fadeInUp} className="space-y-1">
                  <p className="text-xl sm:text-2xl md:text-3xl font-bold font-mono tabular-nums text-foreground">{s.val}</p>
                  <p className="text-xs text-muted-foreground">{s.label}</p>
                </motion.div>
              ))}
            </motion.div>
          </div>
        </section>

        {/* Instruments Grid */}
        <section id="instruments" className="container py-8 sm:py-10">
          <motion.div
            initial={{ opacity: 0, y: 20 }}
            whileInView={{ opacity: 1, y: 0 }}
            viewport={{ once: true, margin: "-50px" }}
            transition={{ duration: 0.4 }}
          >
            <InstrumentGrid />
          </motion.div>
        </section>
      </PageTransition>
    </MainLayout>
  );
};

export default Index;
