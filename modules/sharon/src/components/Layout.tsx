import { useState } from "react";
import logoSrc from "../assets/logo.png";
import type { UserInfo } from "../types/user";

type Module = "emergency" | "outpatient" | "inpatient" | "billing";

interface NavItem {
  id: Module;
  label: string;
  icon: string;
  subItems: string[];
}

const navItems: NavItem[] = [
  {
    id: "emergency",
    label: "IGD / Emergency",
    icon: "🚨",
    subItems: ["Triase Pasien", "Pemeriksaan Darurat", "Observasi Pasien", "Finalisasi ICD"],
  },
  {
    id: "outpatient",
    label: "Rawat Jalan",
    icon: "🏥",
    subItems: ["Daftar Kunjungan", "Pelayanan Pasien", "Tindak Lanjut", "Finalisasi"],
  },
  {
    id: "inpatient",
    label: "Rawat Inap",
    icon: "🛏",
    subItems: ["Manajemen Kamar", "Admisi Pasien", "Transfer Ruangan", "Finalisasi"],
  },
  {
    id: "billing",
    label: "Billing & Asuransi",
    icon: "💳",
    subItems: ["Verifikasi Penjamin", "Kelola Billing", "Pembayaran & Klaim", "Finalisasi Transaksi"],
  },
];

interface LayoutProps {
  activeModule: Module;
  onModuleChange: (m: Module) => void;
  onLogout: () => void;
  user: UserInfo;
  children: React.ReactNode;
}

export default function Layout({ activeModule, onModuleChange, onLogout, user, children }: LayoutProps) {
  const [collapsed, setCollapsed] = useState(false);
  const [notifications] = useState(3);

  return (
    <div className="flex flex-col h-full">
      {/* Header */}
      <header
        className="flex items-center justify-between px-5 h-13 shrink-0 border-b"
        style={{ background: "#0D6E6E", borderColor: "#0A4D68" }}
      >
        <div className="flex items-center gap-3">
          <button
            onClick={() => setCollapsed(!collapsed)}
            className="text-white/70 hover:text-white transition-colors p-1"
          >
            <svg width="18" height="18" viewBox="0 0 18 18" fill="none">
              <rect y="3" width="18" height="1.5" rx="0.75" fill="currentColor" />
              <rect y="8.25" width="18" height="1.5" rx="0.75" fill="currentColor" />
              <rect y="13.5" width="18" height="1.5" rx="0.75" fill="currentColor" />
            </svg>
          </button>
          <div className="flex items-center gap-2">
            <img src={logoSrc} alt="RS Suka Sehat" className="w-8 h-8 rounded-full" style={{ background: "#fff" }} />
            <span className="text-white font-bold text-sm tracking-wide">RS SUKA SEHAT</span>
          </div>
        </div>

        <div className="flex items-center gap-3">
          <button className="relative text-white/70 hover:text-white transition-colors">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
              <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9" />
              <path d="M13.73 21a2 2 0 0 1-3.46 0" />
            </svg>
            {notifications > 0 && (
              <span
                className="absolute -top-1 -right-1 w-3.5 h-3.5 rounded-full flex items-center justify-center text-white"
                style={{ background: "#C0362C", fontSize: "9px" }}
              >
                {notifications}
              </span>
            )}
          </button>
          <div className="flex items-center gap-2">
            <div
              className="w-7 h-7 rounded-full flex items-center justify-center text-xs font-bold text-white"
              style={{ background: "rgba(255,255,255,0.2)" }}
            >
              {user.initials}
            </div>
            <div className="hidden sm:block">
              <p className="text-white text-xs font-semibold leading-tight">{user.fullName}</p>
              <p className="text-white/60 text-xs leading-tight">{user.role}</p>
            </div>
          </div>
          <button
            onClick={onLogout}
            className="text-xs px-2.5 py-1 rounded font-medium transition-colors"
            style={{ background: "#C0362C", color: "white" }}
          >
            Keluar
          </button>
        </div>
      </header>

      <div className="flex flex-1 overflow-hidden">
        {/* Sidebar */}
        <aside
          className="flex flex-col shrink-0 border-r overflow-y-auto transition-all duration-200"
          style={{
            width: collapsed ? 52 : 220,
            background: "#fff",
            borderColor: "#D6E8EE",
          }}
        >
          <div className="p-3 space-y-0.5 flex-1">
            {navItems.map((item) => {
              const isActive = activeModule === item.id;
              return (
                <div key={item.id}>
                  <button
                    onClick={() => onModuleChange(item.id)}
                    className="w-full flex items-center gap-2.5 rounded-md px-2.5 py-2 text-left transition-all duration-150"
                    style={{
                      background: isActive ? "#EAF6F8" : "transparent",
                      color: isActive ? "#0D6E6E" : "#315E6E",
                    }}
                  >
                    <span className="text-base shrink-0">{item.icon}</span>
                    {!collapsed && (
                      <span className="text-xs font-semibold truncate">{item.label}</span>
                    )}
                  </button>
                  {isActive && !collapsed && (
                    <div className="ml-8 mt-0.5 space-y-0.5">
                      {item.subItems.map((sub) => (
                        <div
                          key={sub}
                          className="text-xs py-1 px-2 rounded text-slate-500 hover:text-slate-700 cursor-default"
                        >
                          {sub}
                        </div>
                      ))}
                    </div>
                  )}
                </div>
              );
            })}
          </div>
          {!collapsed && (
            <div className="p-3 border-t" style={{ borderColor: "#D6E8EE" }}>
              <p className="text-xs text-slate-400">SIMRS v2.1.0</p>
              <p
                className="text-xs font-mono mt-0.5"
                style={{ fontFamily: "var(--font-mono)", color: "#8CA5B0", fontSize: "10px" }}
              >
                {new Date().toLocaleDateString("id-ID", { day: "2-digit", month: "short", year: "numeric" })}
              </p>
            </div>
          )}
        </aside>

        {/* Main */}
        <main className="flex-1 overflow-auto" style={{ background: "#F3FAFC" }}>
          {children}
        </main>
      </div>
    </div>
  );
}
