import { useState, useRef } from "react";
import type { UserInfo } from "../types/user";
import logoSrc from "../assets/logo.png";

interface KasirProps {
  user: UserInfo;
  onLogout: () => void;
}

type KasirView = "dashboard" | "rekap" | "riwayat" | "invoice";
type TxStatus = "lunas" | "pending" | "cicilan" | "batal";
type PayMethod = "Tunai" | "Transfer Bank" | "BPJS" | "Asuransi Swasta" | "Kartu Kredit" | "QRIS";

interface Transaction {
  id: string;
  rm: string;
  name: string;
  age: number;
  module: string;
  admitDate: string;
  dischargeDate: string;
  items: { name: string; qty: number; price: number }[];
  total: number;
  covered: number;
  patientShare: number;
  paid: number;
  payMethod?: PayMethod;
  insuranceType: string;
  status: TxStatus;
  invoiceNo: string;
  kasir: string;
  time: string;
}

const TXS: Transaction[] = [
  {
    id: "TRX-2026-0091", rm: "RM-2026-000219", name: "Herman Suryadi", age: 62, module: "Rawat Inap",
    admitDate: "16/07/2026", dischargeDate: "07/09/2026",
    items: [
      { name: "Biaya Kamar VIP (45 hari)", qty: 45, price: 500000 },
      { name: "Visite Dokter Spesialis", qty: 45, price: 150000 },
      { name: "Obat & BHP", qty: 1, price: 3200000 },
      { name: "Tindakan Medis", qty: 1, price: 2100000 },
      { name: "Laboratorium", qty: 1, price: 1450000 },
    ],
    total: 36000000, covered: 30000000, patientShare: 6000000, paid: 6000000,
    payMethod: "Transfer Bank", insuranceType: "BPJS", status: "lunas",
    invoiceNo: "INV-2026-0091", kasir: "Dewi Rahayu", time: "09:14",
  },
  {
    id: "TRX-2026-0092", rm: "RM-2026-000205", name: "Maya Putri", age: 22, module: "Rawat Jalan",
    admitDate: "07/09/2026", dischargeDate: "07/09/2026",
    items: [
      { name: "Pendaftaran & Administrasi", qty: 1, price: 35000 },
      { name: "Konsultasi Dokter Gigi", qty: 1, price: 100000 },
      { name: "Obat", qty: 1, price: 85000 },
    ],
    total: 220000, covered: 0, patientShare: 220000, paid: 0,
    insuranceType: "Mandiri", status: "pending",
    invoiceNo: "INV-2026-0092", kasir: "—", time: "—",
  },
  {
    id: "TRX-2026-0093", rm: "RM-2026-000201", name: "Rina Wulandari", age: 41, module: "Rawat Jalan",
    admitDate: "07/09/2026", dischargeDate: "07/09/2026",
    items: [
      { name: "Pendaftaran & Administrasi", qty: 1, price: 35000 },
      { name: "Konsultasi Spesialis Jantung", qty: 1, price: 250000 },
      { name: "EKG", qty: 1, price: 150000 },
      { name: "Obat", qty: 1, price: 180000 },
    ],
    total: 615000, covered: 615000, patientShare: 0, paid: 615000,
    payMethod: "BPJS", insuranceType: "BPJS", status: "lunas",
    invoiceNo: "INV-2026-0093", kasir: "Dewi Rahayu", time: "10:45",
  },
  {
    id: "TRX-2026-0094", rm: "RM-2026-000221", name: "Bambang Irawan", age: 71, module: "Rawat Inap",
    admitDate: "01/09/2026", dischargeDate: "07/09/2026",
    items: [
      { name: "Biaya Kamar Kelas I (6 hari)", qty: 6, price: 300000 },
      { name: "Visite Dokter Spesialis Paru", qty: 6, price: 120000 },
      { name: "Obat & Infus", qty: 1, price: 1850000 },
      { name: "Laboratorium", qty: 1, price: 650000 },
      { name: "Rontgen Thorax", qty: 2, price: 200000 },
    ],
    total: 5020000, covered: 3500000, patientShare: 1520000, paid: 760000,
    payMethod: "Tunai", insuranceType: "Asuransi Swasta", status: "cicilan",
    invoiceNo: "INV-2026-0094", kasir: "Dewi Rahayu", time: "11:30",
  },
  {
    id: "TRX-2026-0095", rm: "RM-2026-000222", name: "Wati Susanti", age: 35, module: "Rawat Inap",
    admitDate: "05/09/2026", dischargeDate: "07/09/2026",
    items: [
      { name: "Biaya Kamar Kelas II (2 hari)", qty: 2, price: 200000 },
      { name: "Visite Dokter Bedah", qty: 2, price: 150000 },
      { name: "Tindakan Operasi Appendektomi", qty: 1, price: 4500000 },
      { name: "Obat & BHP", qty: 1, price: 1200000 },
    ],
    total: 6500000, covered: 6500000, patientShare: 0, paid: 6500000,
    payMethod: "BPJS", insuranceType: "BPJS", status: "lunas",
    invoiceNo: "INV-2026-0095", kasir: "Dewi Rahayu", time: "13:05",
  },
  {
    id: "TRX-2026-0096", rm: "RM-2026-000224", name: "Ahmad Rizal", age: 48, module: "IGD",
    admitDate: "06/09/2026", dischargeDate: "06/09/2026",
    items: [
      { name: "Tindakan IGD", qty: 1, price: 250000 },
      { name: "EKG 12 Lead", qty: 1, price: 120000 },
      { name: "Obat Emergency", qty: 1, price: 380000 },
    ],
    total: 750000, covered: 0, patientShare: 750000, paid: 750000,
    payMethod: "QRIS", insuranceType: "Mandiri", status: "lunas",
    invoiceNo: "INV-2026-0096", kasir: "Dewi Rahayu", time: "14:20",
  },
];

const STATUS_STYLE: Record<TxStatus, { bg: string; color: string; label: string; dot: string }> = {
  lunas:   { bg: "#D9F3EA", color: "#117A68", label: "Lunas",   dot: "#117A68" },
  pending: { bg: "#FFF0D5", color: "#A66700", label: "Pending", dot: "#A66700" },
  cicilan: { bg: "#E1EEFB", color: "#1D5FA8", label: "Cicilan", dot: "#1D5FA8" },
  batal:   { bg: "#FDE7E7", color: "#C0362C", label: "Batal",   dot: "#C0362C" },
};

const MODULE_ICON: Record<string, string> = {
  "Rawat Inap": "🛏", "Rawat Jalan": "🏥", "IGD": "🚨", "Farmasi": "💊",
};

function rp(n: number) {
  return "Rp " + n.toLocaleString("id-ID");
}

const SIDEBAR_ITEMS: { key: KasirView; label: string; icon: string }[] = [
  { key: "dashboard", label: "Dashboard", icon: "📊" },
  { key: "rekap",     label: "Rekapitulasi Biaya", icon: "📋" },
  { key: "riwayat",  label: "Riwayat Transaksi", icon: "🗂" },
  { key: "invoice",  label: "Cetak Invoice", icon: "🖨" },
];

export default function Kasir({ user, onLogout }: KasirProps) {
  const [view, setView] = useState<KasirView>("dashboard");
  const [collapsed, setSidebarCollapsed] = useState(false);
  const [txs, setTxs] = useState<Transaction[]>(TXS);
  const [selectedTx, setSelectedTx] = useState<Transaction | null>(null);
  const [toast, setToast] = useState<string | null>(null);
  const [notifications] = useState(2);
  const printRef = useRef<HTMLDivElement>(null);

  // Riwayat filters
  const [search, setSearch] = useState("");
  const [filterStatus, setFilterStatus] = useState("Semua");
  const [filterModule, setFilterModule] = useState("Semua");
  const [dateFrom, setDateFrom] = useState("");

  // Rekap filter
  const [rekapModule, setRekapModule] = useState("Semua");
  const [rekapPeriod, setRekapPeriod] = useState("Hari Ini");

  // Payment modal
  const [payModal, setPayModal] = useState(false);
  const [payForm, setPayForm] = useState({ method: "Tunai" as PayMethod, amount: "" });

  function showToast(msg: string) {
    setToast(msg);
    setTimeout(() => setToast(null), 2500);
  }

  function processPayment() {
    if (!selectedTx) return;
    const paid = Number(payForm.amount);
    if (paid < selectedTx.patientShare) { showToast("⚠ Jumlah bayar kurang dari tagihan"); return; }
    setTxs((prev) => prev.map((t) => t.id === selectedTx.id ? {
      ...t, paid: t.patientShare, payMethod: payForm.method, status: "lunas" as TxStatus, kasir: user.fullName, time: new Date().toLocaleTimeString("id-ID", { hour: "2-digit", minute: "2-digit" }),
    } : t));
    setSelectedTx((t) => t ? { ...t, paid: t.patientShare, payMethod: payForm.method, status: "lunas", kasir: user.fullName } : null);
    setPayModal(false);
    showToast("Pembayaran berhasil dicatat. Invoice siap dicetak.");
  }

  function handlePrint() {
    window.print();
  }

  // ─── Computed ─────────────────────────────────────────────────────────────
  const todayTotal = txs.filter((t) => t.status === "lunas").reduce((s, t) => s + t.paid, 0);
  const todayCount = txs.filter((t) => t.status === "lunas").length;
  const pendingCount = txs.filter((t) => t.status === "pending").length;
  const pendingAmount = txs.filter((t) => t.status === "pending").reduce((s, t) => s + t.patientShare, 0);
  const bpjsTotal = txs.filter((t) => t.insuranceType === "BPJS").reduce((s, t) => s + t.covered, 0);

  const filteredTxs = txs.filter((t) => {
    const matchSearch = !search || t.name.toLowerCase().includes(search.toLowerCase()) || t.id.includes(search) || t.rm.includes(search);
    const matchStatus = filterStatus === "Semua" || t.status === filterStatus;
    const matchModule = filterModule === "Semua" || t.module === filterModule;
    return matchSearch && matchStatus && matchModule;
  });

  const rekapData = (() => {
    const modules = ["Rawat Inap", "Rawat Jalan", "IGD", "Farmasi"];
    return modules.map((m) => {
      const filtered = txs.filter((t) => rekapModule === "Semua" || t.module === m);
      const rows = rekapModule === "Semua" ? txs.filter((t) => t.module === m) : txs;
      return {
        module: m,
        count: rows.length,
        total: rows.reduce((s, t) => s + t.total, 0),
        covered: rows.reduce((s, t) => s + t.covered, 0),
        patientShare: rows.reduce((s, t) => s + t.patientShare, 0),
        lunas: rows.filter((t) => t.status === "lunas").length,
      };
    });
  })();

  return (
    <div className="flex flex-col h-full">
      {/* ── Header ── */}
      <header className="flex items-center justify-between px-5 shrink-0 border-b" style={{ height: 52, background: "#0D6E6E", borderColor: "#0A4D68" }}>
        <div className="flex items-center gap-3">
          <button onClick={() => setSidebarCollapsed((c) => !c)} className="text-white/70 hover:text-white p-1">
            <svg width="18" height="18" viewBox="0 0 18 18" fill="none">
              <rect y="3" width="18" height="1.5" rx=".75" fill="currentColor" />
              <rect y="8.25" width="18" height="1.5" rx=".75" fill="currentColor" />
              <rect y="13.5" width="18" height="1.5" rx=".75" fill="currentColor" />
            </svg>
          </button>
          <img src={logoSrc} alt="RS Suka Sehat" className="w-8 h-8 rounded-full" style={{ background: "#fff" }} />
          <span className="text-white font-bold text-sm tracking-wide">RS SUKA SEHAT</span>
          <span className="hidden md:inline text-white/40 text-xs">— Kasir</span>
        </div>
        <div className="flex items-center gap-3">
          <button className="relative text-white/70 hover:text-white">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
              <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9" /><path d="M13.73 21a2 2 0 0 1-3.46 0" />
            </svg>
            {notifications > 0 && (
              <span className="absolute -top-1 -right-1 w-3.5 h-3.5 rounded-full flex items-center justify-center text-white" style={{ background: "#C0362C", fontSize: "9px" }}>{notifications}</span>
            )}
          </button>
          <div className="flex items-center gap-2">
            <div className="w-7 h-7 rounded-full flex items-center justify-center text-xs font-bold text-white" style={{ background: "rgba(255,255,255,0.2)" }}>{user.initials}</div>
            <div className="hidden sm:block">
              <p className="text-white text-xs font-semibold leading-tight">{user.fullName}</p>
              <p className="text-white/60 text-xs leading-tight">Kasir — {user.unit}</p>
            </div>
          </div>
          <button onClick={onLogout} className="text-xs px-2.5 py-1 rounded font-medium" style={{ background: "#C0362C", color: "white" }}>Keluar</button>
        </div>
      </header>

      <div className="flex flex-1 overflow-hidden">
        {/* ── Sidebar ── */}
        <aside className="flex flex-col shrink-0 border-r transition-all duration-200" style={{ width: collapsed ? 52 : 216, background: "#fff", borderColor: "#D6E8EE" }}>
          {!collapsed && (
            <div className="px-3 pt-4 pb-2">
              <p className="text-xs font-semibold uppercase tracking-widest" style={{ color: "#8CA5B0" }}>Menu Kasir</p>
            </div>
          )}
          <nav className="flex-1 p-2 space-y-0.5">
            {SIDEBAR_ITEMS.map((item) => {
              const isActive = view === item.key;
              return (
                <button key={item.key} onClick={() => setView(item.key)}
                  className="w-full flex items-center gap-2.5 rounded-lg px-2.5 py-2.5 text-left transition-all"
                  style={{ background: isActive ? "#EAF6F8" : "transparent", color: isActive ? "#0D6E6E" : "#456D7D" }}>
                  <span className="text-base shrink-0">{item.icon}</span>
                  {!collapsed && <span className="text-xs font-semibold">{item.label}</span>}
                  {isActive && !collapsed && <span className="ml-auto w-1.5 h-1.5 rounded-full" style={{ background: "#0D6E6E" }} />}
                </button>
              );
            })}
          </nav>
          {!collapsed && (
            <div className="p-3 border-t mx-2 mb-2 rounded-lg" style={{ borderColor: "#D6E8EE", background: "#F7FBFC" }}>
              <p className="text-xs font-semibold" style={{ color: "#315E6E" }}>{user.fullName}</p>
              <p className="text-xs mt-0.5" style={{ color: "#8CA5B0" }}>Kasir · {user.unit}</p>
              <p className="text-xs mt-1" style={{ fontFamily: "var(--font-mono)", color: "#8CA5B0", fontSize: "10px" }}>
                {new Date().toLocaleDateString("id-ID", { day: "2-digit", month: "short", year: "numeric" })}
              </p>
            </div>
          )}
        </aside>

        {/* ── Main ── */}
        <main className="flex-1 overflow-y-auto" style={{ background: "#F3FAFC" }}>

          {/* ════ DASHBOARD ════ */}
          {view === "dashboard" && (
            <div className="p-6 space-y-6">
              <div>
                <h1 className="text-xl font-bold" style={{ color: "#164E63" }}>Dashboard Kasir</h1>
                <p className="text-xs mt-0.5" style={{ color: "#66869A" }}>
                  {new Date().toLocaleDateString("id-ID", { weekday: "long", day: "2-digit", month: "long", year: "numeric" })} · Shift Pagi
                </p>
              </div>

              {/* KPI Cards */}
              <div className="grid grid-cols-2 lg:grid-cols-4 gap-4">
                {[
                  { label: "Pendapatan Hari Ini", val: rp(todayTotal), sub: `${todayCount} transaksi lunas`, icon: "💰", accent: "#0D6E6E", bg: "#EAF6F8" },
                  { label: "Menunggu Pembayaran", val: String(pendingCount) + " pasien", sub: rp(pendingAmount), icon: "⏳", accent: "#A66700", bg: "#FFF0D5" },
                  { label: "Klaim BPJS / Asuransi", val: rp(bpjsTotal), sub: `${txs.filter((t) => t.insuranceType === "BPJS").length} transaksi`, icon: "🏛", accent: "#1D5FA8", bg: "#E1EEFB" },
                  { label: "Total Tagihan Hari Ini", val: rp(txs.reduce((s, t) => s + t.total, 0)), sub: `${txs.length} total transaksi`, icon: "📊", accent: "#117A68", bg: "#D9F3EA" },
                ].map((k) => (
                  <div key={k.label} className="rounded-xl p-5 shadow-sm" style={{ background: "#fff", border: "1px solid #D6E8EE" }}>
                    <div className="flex items-start justify-between mb-3">
                      <p className="text-xs font-medium" style={{ color: "#66869A" }}>{k.label}</p>
                      <span className="w-9 h-9 rounded-lg flex items-center justify-center text-lg" style={{ background: k.bg }}>{k.icon}</span>
                    </div>
                    <p className="text-lg font-bold leading-tight" style={{ fontFamily: "var(--font-mono)", color: k.accent }}>{k.val}</p>
                    <p className="text-xs mt-1" style={{ color: "#8CA5B0" }}>{k.sub}</p>
                  </div>
                ))}
              </div>

              {/* Pending payments + today summary */}
              <div className="grid grid-cols-3 gap-5">
                {/* Pending list */}
                <div className="col-span-2 rounded-xl shadow-sm overflow-hidden" style={{ background: "#fff", border: "1px solid #D6E8EE" }}>
                  <div className="flex items-center justify-between px-5 py-4 border-b" style={{ borderColor: "#D6E8EE" }}>
                    <h2 className="text-sm font-semibold" style={{ color: "#164E63" }}>Antrian Pembayaran</h2>
                    <span className="text-xs px-2 py-0.5 rounded-full font-semibold" style={{ background: "#FFF0D5", color: "#A66700" }}>{pendingCount} menunggu</span>
                  </div>
                  <div className="divide-y" style={{ borderColor: "#EEF6F8" }}>
                    {txs.filter((t) => t.status === "pending" || t.status === "cicilan").map((t) => {
                      const s = STATUS_STYLE[t.status];
                      return (
                        <div key={t.id} className="flex items-center justify-between px-5 py-3.5 hover:bg-slate-50 transition-colors">
                          <div className="flex items-center gap-3">
                            <span className="text-xl">{MODULE_ICON[t.module] ?? "📄"}</span>
                            <div>
                              <p className="text-sm font-semibold" style={{ color: "#164E63" }}>{t.name}</p>
                              <p className="text-xs" style={{ fontFamily: "var(--font-mono)", color: "#66869A" }}>{t.rm} · {t.module}</p>
                            </div>
                          </div>
                          <div className="flex items-center gap-4">
                            <div className="text-right">
                              <p className="text-sm font-bold" style={{ fontFamily: "var(--font-mono)", color: "#C0362C" }}>{rp(t.patientShare)}</p>
                              <p className="text-xs" style={{ color: "#8CA5B0" }}>{t.insuranceType}</p>
                            </div>
                            <span className="text-xs px-2 py-0.5 rounded-full font-medium" style={{ background: s.bg, color: s.color }}>{s.label}</span>
                            <button
                              onClick={() => { setSelectedTx(t); setPayForm({ method: "Tunai", amount: String(t.patientShare) }); setPayModal(true); }}
                              className="text-xs px-3 py-1.5 rounded-lg font-semibold text-white"
                              style={{ background: "#0D6E6E" }}
                            >
                              Proses Bayar
                            </button>
                          </div>
                        </div>
                      );
                    })}
                    {txs.filter((t) => t.status === "pending" || t.status === "cicilan").length === 0 && (
                      <div className="py-8 text-center text-sm" style={{ color: "#8CA5B0" }}>✓ Tidak ada antrian pembayaran</div>
                    )}
                  </div>
                </div>

                {/* Method breakdown */}
                <div className="rounded-xl shadow-sm" style={{ background: "#fff", border: "1px solid #D6E8EE" }}>
                  <div className="px-5 py-4 border-b" style={{ borderColor: "#D6E8EE" }}>
                    <h2 className="text-sm font-semibold" style={{ color: "#164E63" }}>Rekap Metode Bayar</h2>
                  </div>
                  <div className="p-5 space-y-3">
                    {(["BPJS", "Tunai", "Transfer Bank", "QRIS", "Asuransi Swasta"] as const).map((m) => {
                      const count = txs.filter((t) => t.payMethod === m).length;
                      const amount = txs.filter((t) => t.payMethod === m).reduce((s, t) => s + t.paid, 0);
                      if (count === 0) return null;
                      const pct = Math.round((count / txs.filter((t) => t.status === "lunas").length) * 100);
                      return (
                        <div key={m}>
                          <div className="flex justify-between text-xs mb-1">
                            <span className="font-medium" style={{ color: "#315E6E" }}>{m}</span>
                            <span style={{ fontFamily: "var(--font-mono)", color: "#66869A" }}>{rp(amount)}</span>
                          </div>
                          <div className="h-1.5 rounded-full overflow-hidden" style={{ background: "#D6E8EE" }}>
                            <div className="h-full rounded-full transition-all" style={{ width: `${pct}%`, background: "#0D6E6E" }} />
                          </div>
                          <p className="text-xs mt-0.5" style={{ color: "#8CA5B0" }}>{count} transaksi · {pct}%</p>
                        </div>
                      );
                    })}
                  </div>
                </div>
              </div>

              {/* Recent transactions */}
              <div className="rounded-xl shadow-sm overflow-hidden" style={{ background: "#fff", border: "1px solid #D6E8EE" }}>
                <div className="flex items-center justify-between px-5 py-4 border-b" style={{ borderColor: "#D6E8EE" }}>
                  <h2 className="text-sm font-semibold" style={{ color: "#164E63" }}>Transaksi Terbaru</h2>
                  <button onClick={() => setView("riwayat")} className="text-xs font-medium" style={{ color: "#0D6E6E" }}>Lihat semua →</button>
                </div>
                <table className="w-full text-sm">
                  <thead>
                    <tr style={{ background: "#F7FBFC", borderBottom: "1px solid #D6E8EE" }}>
                      {["NO. INVOICE", "PASIEN", "MODUL", "TOTAL", "TANGGUNG PASIEN", "METODE", "STATUS", "AKSI"].map((h) => (
                        <th key={h} className="text-left text-xs font-semibold px-4 py-3" style={{ color: "#66869A" }}>{h}</th>
                      ))}
                    </tr>
                  </thead>
                  <tbody className="divide-y" style={{ borderColor: "#EEF6F8" }}>
                    {txs.slice(0, 5).map((t) => {
                      const s = STATUS_STYLE[t.status];
                      return (
                        <tr key={t.id} className="hover:bg-slate-50 transition-colors">
                          <td className="px-4 py-3 text-xs" style={{ fontFamily: "var(--font-mono)", color: "#66869A" }}>{t.invoiceNo}</td>
                          <td className="px-4 py-3">
                            <p className="text-sm font-semibold" style={{ color: "#164E63" }}>{t.name}</p>
                            <p className="text-xs" style={{ fontFamily: "var(--font-mono)", color: "#8CA5B0" }}>{t.rm}</p>
                          </td>
                          <td className="px-4 py-3 text-xs" style={{ color: "#66869A" }}>{MODULE_ICON[t.module]} {t.module}</td>
                          <td className="px-4 py-3 text-sm font-semibold" style={{ fontFamily: "var(--font-mono)", color: "#315E6E" }}>{rp(t.total)}</td>
                          <td className="px-4 py-3 text-sm font-bold" style={{ fontFamily: "var(--font-mono)", color: t.patientShare > 0 ? "#C0362C" : "#117A68" }}>{rp(t.patientShare)}</td>
                          <td className="px-4 py-3 text-xs" style={{ color: "#66869A" }}>{t.payMethod ?? "—"}</td>
                          <td className="px-4 py-3">
                            <span className="flex items-center gap-1 text-xs font-medium">
                              <span className="w-1.5 h-1.5 rounded-full" style={{ background: s.dot }} />
                              <span style={{ color: s.color }}>{s.label}</span>
                            </span>
                          </td>
                          <td className="px-4 py-3">
                            <div className="flex gap-1.5">
                              {t.status === "pending" && (
                                <button onClick={() => { setSelectedTx(t); setPayForm({ method: "Tunai", amount: String(t.patientShare) }); setPayModal(true); }}
                                  className="text-xs px-2 py-1 rounded font-medium text-white" style={{ background: "#0D6E6E" }}>
                                  Bayar
                                </button>
                              )}
                              <button onClick={() => { setSelectedTx(t); setView("invoice"); }}
                                className="text-xs px-2 py-1 rounded font-medium border" style={{ borderColor: "#D6E8EE", color: "#315E6E" }}>
                                Invoice
                              </button>
                            </div>
                          </td>
                        </tr>
                      );
                    })}
                  </tbody>
                </table>
              </div>
            </div>
          )}

          {/* ════ REKAPITULASI BIAYA ════ */}
          {view === "rekap" && (
            <div className="p-6 space-y-5">
              <div className="flex items-center justify-between">
                <div>
                  <h1 className="text-xl font-bold" style={{ color: "#164E63" }}>Rekapitulasi Biaya</h1>
                  <p className="text-xs mt-0.5" style={{ color: "#66869A" }}>Ringkasan pendapatan per modul layanan</p>
                </div>
                <div className="flex gap-2">
                  {["Hari Ini", "Minggu Ini", "Bulan Ini"].map((p) => (
                    <button key={p} onClick={() => setRekapPeriod(p)}
                      className="text-xs px-3 py-1.5 rounded-lg border font-medium transition-colors"
                      style={{ background: rekapPeriod === p ? "#0D6E6E" : "#fff", color: rekapPeriod === p ? "#fff" : "#315E6E", borderColor: rekapPeriod === p ? "#0D6E6E" : "#D6E8EE" }}>
                      {p}
                    </button>
                  ))}
                </div>
              </div>

              {/* Summary totals */}
              <div className="grid grid-cols-4 gap-4">
                {[
                  { label: "Total Tagihan", val: txs.reduce((s, t) => s + t.total, 0), color: "#164E63" },
                  { label: "Klaim Asuransi", val: txs.reduce((s, t) => s + t.covered, 0), color: "#1D5FA8" },
                  { label: "Tanggung Pasien", val: txs.reduce((s, t) => s + t.patientShare, 0), color: "#A66700" },
                  { label: "Sudah Terbayar", val: txs.reduce((s, t) => s + t.paid, 0), color: "#117A68" },
                ].map((k) => (
                  <div key={k.label} className="rounded-xl p-4 shadow-sm" style={{ background: "#fff", border: "1px solid #D6E8EE" }}>
                    <p className="text-xs mb-1" style={{ color: "#66869A" }}>{k.label}</p>
                    <p className="text-base font-bold" style={{ fontFamily: "var(--font-mono)", color: k.color }}>{rp(k.val)}</p>
                  </div>
                ))}
              </div>

              {/* Per-module table */}
              <div className="rounded-xl shadow-sm overflow-hidden" style={{ background: "#fff", border: "1px solid #D6E8EE" }}>
                <div className="px-5 py-4 border-b" style={{ borderColor: "#D6E8EE" }}>
                  <h2 className="text-sm font-semibold" style={{ color: "#164E63" }}>Rekap per Modul — {rekapPeriod}</h2>
                </div>
                <table className="w-full text-sm">
                  <thead>
                    <tr style={{ background: "#F7FBFC", borderBottom: "1px solid #D6E8EE" }}>
                      {["MODUL", "JML TRANSAKSI", "TOTAL TAGIHAN", "KLAIM ASURANSI", "TANGGUNG PASIEN", "LUNAS", "COMPLETION"].map((h) => (
                        <th key={h} className="text-left text-xs font-semibold px-4 py-3" style={{ color: "#66869A" }}>{h}</th>
                      ))}
                    </tr>
                  </thead>
                  <tbody className="divide-y" style={{ borderColor: "#EEF6F8" }}>
                    {rekapData.map((row) => {
                      const pct = row.count > 0 ? Math.round((row.lunas / row.count) * 100) : 0;
                      return (
                        <tr key={row.module} className="hover:bg-slate-50 transition-colors">
                          <td className="px-4 py-3">
                            <span className="flex items-center gap-2 font-semibold text-sm" style={{ color: "#164E63" }}>
                              {MODULE_ICON[row.module] ?? "📄"} {row.module}
                            </span>
                          </td>
                          <td className="px-4 py-3 text-xs font-bold text-center" style={{ fontFamily: "var(--font-mono)", color: "#315E6E" }}>{row.count}</td>
                          <td className="px-4 py-3 text-sm" style={{ fontFamily: "var(--font-mono)", color: "#315E6E" }}>{rp(row.total)}</td>
                          <td className="px-4 py-3 text-sm" style={{ fontFamily: "var(--font-mono)", color: "#1D5FA8" }}>{rp(row.covered)}</td>
                          <td className="px-4 py-3 text-sm font-semibold" style={{ fontFamily: "var(--font-mono)", color: "#A66700" }}>{rp(row.patientShare)}</td>
                          <td className="px-4 py-3 text-xs text-center" style={{ color: "#117A68", fontWeight: 600 }}>{row.lunas}/{row.count}</td>
                          <td className="px-4 py-3 w-32">
                            <div className="flex items-center gap-2">
                              <div className="flex-1 h-1.5 rounded-full overflow-hidden" style={{ background: "#D6E8EE" }}>
                                <div className="h-full rounded-full" style={{ width: `${pct}%`, background: pct === 100 ? "#117A68" : "#0D6E6E" }} />
                              </div>
                              <span className="text-xs font-bold" style={{ color: "#66869A" }}>{pct}%</span>
                            </div>
                          </td>
                        </tr>
                      );
                    })}
                  </tbody>
                  <tfoot>
                    <tr style={{ background: "#F7FBFC", borderTop: "2px solid #D6E8EE" }}>
                      <td className="px-4 py-3 text-sm font-bold" style={{ color: "#164E63" }}>TOTAL</td>
                      <td className="px-4 py-3 text-sm font-bold text-center" style={{ fontFamily: "var(--font-mono)", color: "#315E6E" }}>{txs.length}</td>
                      <td className="px-4 py-3 text-sm font-bold" style={{ fontFamily: "var(--font-mono)", color: "#164E63" }}>{rp(txs.reduce((s, t) => s + t.total, 0))}</td>
                      <td className="px-4 py-3 text-sm font-bold" style={{ fontFamily: "var(--font-mono)", color: "#1D5FA8" }}>{rp(txs.reduce((s, t) => s + t.covered, 0))}</td>
                      <td className="px-4 py-3 text-sm font-bold" style={{ fontFamily: "var(--font-mono)", color: "#A66700" }}>{rp(txs.reduce((s, t) => s + t.patientShare, 0))}</td>
                      <td className="px-4 py-3 text-sm font-bold text-center" style={{ color: "#117A68" }}>{txs.filter((t) => t.status === "lunas").length}/{txs.length}</td>
                      <td className="px-4 py-3" />
                    </tr>
                  </tfoot>
                </table>
              </div>

              {/* Per-item breakdown */}
              <div className="rounded-xl shadow-sm overflow-hidden" style={{ background: "#fff", border: "1px solid #D6E8EE" }}>
                <div className="px-5 py-4 border-b" style={{ borderColor: "#D6E8EE" }}>
                  <h2 className="text-sm font-semibold" style={{ color: "#164E63" }}>Rekap Jenis Layanan</h2>
                </div>
                <div className="p-5 grid grid-cols-2 gap-3">
                  {(() => {
                    const map: Record<string, number> = {};
                    txs.forEach((t) => t.items.forEach((i) => {
                      const cat = i.name.includes("Kamar") ? "Biaya Kamar" : i.name.includes("Dokter") || i.name.includes("Konsultasi") ? "Jasa Dokter" : i.name.includes("Obat") || i.name.includes("BHP") ? "Obat & BHP" : i.name.includes("Lab") ? "Laboratorium" : i.name.includes("Tindakan") || i.name.includes("Operasi") || i.name.includes("IGD") ? "Tindakan Medis" : "Lainnya";
                      map[cat] = (map[cat] ?? 0) + i.price * i.qty;
                    }));
                    const grandTotal = Object.values(map).reduce((a, b) => a + b, 0);
                    return Object.entries(map).sort(([, a], [, b]) => b - a).map(([cat, val]) => (
                      <div key={cat} className="flex items-center justify-between p-3 rounded-lg" style={{ background: "#F7FBFC" }}>
                        <div>
                          <p className="text-sm font-semibold" style={{ color: "#315E6E" }}>{cat}</p>
                          <p className="text-xs" style={{ color: "#8CA5B0" }}>{Math.round((val / grandTotal) * 100)}% dari total</p>
                        </div>
                        <p className="text-sm font-bold" style={{ fontFamily: "var(--font-mono)", color: "#0D6E6E" }}>{rp(val)}</p>
                      </div>
                    ));
                  })()}
                </div>
              </div>
            </div>
          )}

          {/* ════ RIWAYAT TRANSAKSI ════ */}
          {view === "riwayat" && (
            <div className="p-6 space-y-5">
              <div>
                <h1 className="text-xl font-bold" style={{ color: "#164E63" }}>Riwayat Transaksi</h1>
                <p className="text-xs mt-0.5" style={{ color: "#66869A" }}>Semua transaksi keuangan SIMRS</p>
              </div>

              {/* Filters */}
              <div className="flex flex-wrap gap-3">
                <div className="relative flex-1" style={{ minWidth: 200 }}>
                  <span className="absolute left-3 top-1/2 -translate-y-1/2 text-sm" style={{ color: "#8CA5B0" }}>🔍</span>
                  <input
                    className="w-full border rounded-lg pl-9 pr-4 py-2 text-sm outline-none"
                    style={{ borderColor: "#D6E8EE", background: "#fff", color: "#315E6E" }}
                    placeholder="Cari nama, No. RM, No. Invoice..."
                    value={search} onChange={(e) => setSearch(e.target.value)}
                  />
                </div>
                <select className="border rounded-lg px-3 py-2 text-sm outline-none"
                  style={{ borderColor: "#D6E8EE", background: "#fff", color: "#315E6E" }}
                  value={filterStatus} onChange={(e) => setFilterStatus(e.target.value)}>
                  <option value="Semua">Semua Status</option>
                  <option value="lunas">Lunas</option>
                  <option value="pending">Pending</option>
                  <option value="cicilan">Cicilan</option>
                  <option value="batal">Batal</option>
                </select>
                <select className="border rounded-lg px-3 py-2 text-sm outline-none"
                  style={{ borderColor: "#D6E8EE", background: "#fff", color: "#315E6E" }}
                  value={filterModule} onChange={(e) => setFilterModule(e.target.value)}>
                  <option value="Semua">Semua Modul</option>
                  <option>Rawat Inap</option>
                  <option>Rawat Jalan</option>
                  <option>IGD</option>
                </select>
                <input type="date" className="border rounded-lg px-3 py-2 text-sm outline-none"
                  style={{ borderColor: "#D6E8EE", background: "#fff", color: "#315E6E" }}
                  value={dateFrom} onChange={(e) => setDateFrom(e.target.value)} />
              </div>

              <div className="rounded-xl shadow-sm overflow-hidden" style={{ background: "#fff", border: "1px solid #D6E8EE" }}>
                <div className="flex items-center justify-between px-5 py-3 border-b" style={{ borderColor: "#D6E8EE" }}>
                  <p className="text-xs font-semibold" style={{ color: "#66869A" }}>Menampilkan {filteredTxs.length} dari {txs.length} transaksi</p>
                  <button className="text-xs font-medium px-3 py-1.5 rounded border" style={{ borderColor: "#D6E8EE", color: "#315E6E" }}>⬇ Export CSV</button>
                </div>
                <table className="w-full text-sm">
                  <thead>
                    <tr style={{ background: "#F7FBFC", borderBottom: "1px solid #D6E8EE" }}>
                      {["NO. INVOICE", "TANGGAL", "PASIEN", "MODUL", "TOTAL", "ASURANSI", "TANGGUNG PASIEN", "METODE", "KASIR", "STATUS", "AKSI"].map((h) => (
                        <th key={h} className="text-left text-xs font-semibold px-3 py-3 whitespace-nowrap" style={{ color: "#66869A" }}>{h}</th>
                      ))}
                    </tr>
                  </thead>
                  <tbody className="divide-y" style={{ borderColor: "#EEF6F8" }}>
                    {filteredTxs.map((t) => {
                      const s = STATUS_STYLE[t.status];
                      return (
                        <tr key={t.id} className="hover:bg-slate-50 transition-colors">
                          <td className="px-3 py-3 text-xs" style={{ fontFamily: "var(--font-mono)", color: "#66869A" }}>{t.invoiceNo}</td>
                          <td className="px-3 py-3 text-xs whitespace-nowrap" style={{ color: "#66869A" }}>{t.dischargeDate}</td>
                          <td className="px-3 py-3">
                            <p className="text-sm font-semibold" style={{ color: "#164E63" }}>{t.name}</p>
                            <p className="text-xs" style={{ fontFamily: "var(--font-mono)", color: "#8CA5B0" }}>{t.rm}</p>
                          </td>
                          <td className="px-3 py-3 text-xs whitespace-nowrap" style={{ color: "#66869A" }}>{MODULE_ICON[t.module]} {t.module}</td>
                          <td className="px-3 py-3 text-sm" style={{ fontFamily: "var(--font-mono)", color: "#315E6E" }}>{rp(t.total)}</td>
                          <td className="px-3 py-3 text-xs" style={{ color: "#1D5FA8" }}>{t.insuranceType}</td>
                          <td className="px-3 py-3 text-sm font-bold" style={{ fontFamily: "var(--font-mono)", color: t.patientShare > 0 ? "#C0362C" : "#117A68" }}>{rp(t.patientShare)}</td>
                          <td className="px-3 py-3 text-xs" style={{ color: "#66869A" }}>{t.payMethod ?? "—"}</td>
                          <td className="px-3 py-3 text-xs" style={{ color: "#66869A" }}>{t.kasir}</td>
                          <td className="px-3 py-3">
                            <span className="flex items-center gap-1 text-xs font-medium whitespace-nowrap">
                              <span className="w-1.5 h-1.5 rounded-full shrink-0" style={{ background: s.dot }} />
                              <span style={{ color: s.color }}>{s.label}</span>
                            </span>
                          </td>
                          <td className="px-3 py-3">
                            <div className="flex gap-1">
                              {(t.status === "pending" || t.status === "cicilan") && (
                                <button onClick={() => { setSelectedTx(t); setPayForm({ method: "Tunai", amount: String(t.patientShare) }); setPayModal(true); }}
                                  className="text-xs px-2 py-1 rounded font-medium text-white whitespace-nowrap" style={{ background: "#0D6E6E" }}>Bayar</button>
                              )}
                              <button onClick={() => { setSelectedTx(t); setView("invoice"); }}
                                className="text-xs px-2 py-1 rounded border whitespace-nowrap" style={{ borderColor: "#D6E8EE", color: "#315E6E" }}>Invoice</button>
                            </div>
                          </td>
                        </tr>
                      );
                    })}
                  </tbody>
                </table>
              </div>
            </div>
          )}

          {/* ════ CETAK INVOICE ════ */}
          {view === "invoice" && (
            <div className="p-6 space-y-5">
              <div className="flex items-center justify-between">
                <div>
                  <h1 className="text-xl font-bold" style={{ color: "#164E63" }}>Cetak Invoice</h1>
                  <p className="text-xs mt-0.5" style={{ color: "#66869A" }}>Pilih transaksi untuk melihat dan mencetak invoice</p>
                </div>
                {selectedTx && (
                  <button onClick={handlePrint} className="flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-semibold text-white" style={{ background: "#0D6E6E" }}>
                    🖨 Cetak Invoice
                  </button>
                )}
              </div>

              <div className="grid grid-cols-3 gap-5">
                {/* Transaction picker */}
                <div className="rounded-xl overflow-hidden shadow-sm" style={{ background: "#fff", border: "1px solid #D6E8EE" }}>
                  <div className="px-4 py-3 border-b" style={{ borderColor: "#D6E8EE" }}>
                    <p className="text-xs font-semibold" style={{ color: "#66869A" }}>Pilih Transaksi</p>
                  </div>
                  <div className="divide-y overflow-y-auto" style={{ maxHeight: 500, borderColor: "#EEF6F8" }}>
                    {txs.map((t) => {
                      const s = STATUS_STYLE[t.status];
                      const isActive = selectedTx?.id === t.id;
                      return (
                        <div key={t.id} onClick={() => setSelectedTx(t)}
                          className="p-3 cursor-pointer transition-colors"
                          style={{ background: isActive ? "#EAF6F8" : undefined }}>
                          <div className="flex items-start justify-between gap-2">
                            <div>
                              <p className="text-sm font-semibold" style={{ color: "#164E63" }}>{t.name}</p>
                              <p className="text-xs" style={{ fontFamily: "var(--font-mono)", color: "#66869A" }}>{t.invoiceNo}</p>
                              <p className="text-xs" style={{ color: "#8CA5B0" }}>{t.module} · {t.dischargeDate}</p>
                            </div>
                            <span className="text-xs px-1.5 py-0.5 rounded font-medium whitespace-nowrap" style={{ background: s.bg, color: s.color }}>{s.label}</span>
                          </div>
                        </div>
                      );
                    })}
                  </div>
                </div>

                {/* Invoice preview */}
                <div className="col-span-2">
                  {!selectedTx ? (
                    <div className="h-full rounded-xl flex items-center justify-center" style={{ background: "#fff", border: "1px solid #D6E8EE", minHeight: 400 }}>
                      <div className="text-center" style={{ color: "#8CA5B0" }}>
                        <p className="text-4xl mb-3">🖨</p>
                        <p className="font-semibold">Pilih transaksi di sebelah kiri</p>
                        <p className="text-sm mt-1">untuk melihat preview invoice</p>
                      </div>
                    </div>
                  ) : (
                    <div ref={printRef} className="rounded-xl shadow-sm p-8" style={{ background: "#fff", border: "1px solid #D6E8EE" }}>
                      {/* Invoice header */}
                      <div className="flex items-start justify-between mb-6 pb-6 border-b" style={{ borderColor: "#D6E8EE" }}>
                        <div className="flex items-center gap-3">
                          <img src={logoSrc} alt="RS Suka Sehat" className="w-16 h-16 rounded-full" style={{ background: "#F3FAFC" }} />
                          <div>
                            <h2 className="text-lg font-bold" style={{ color: "#0D6E6E" }}>RS SUKA SEHAT</h2>
                            <p className="text-xs" style={{ color: "#66869A" }}>Jl. Kesehatan No. 1, Kota Sehat, Jawa Barat</p>
                            <p className="text-xs" style={{ color: "#66869A" }}>Telp: (022) 1234-5678 · simrs@rssukasehat.co.id</p>
                          </div>
                        </div>
                        <div className="text-right">
                          <p className="text-xs font-semibold mb-0.5" style={{ color: "#66869A" }}>INVOICE</p>
                          <p className="text-xl font-bold" style={{ fontFamily: "var(--font-mono)", color: "#164E63" }}>{selectedTx.invoiceNo}</p>
                          <p className="text-xs mt-1" style={{ color: "#8CA5B0" }}>Diterbitkan: {selectedTx.dischargeDate} · {selectedTx.time}</p>
                          <span className="text-xs px-2 py-0.5 rounded font-bold inline-block mt-1" style={{ background: STATUS_STYLE[selectedTx.status].bg, color: STATUS_STYLE[selectedTx.status].color }}>
                            {STATUS_STYLE[selectedTx.status].label.toUpperCase()}
                          </span>
                        </div>
                      </div>

                      {/* Patient info */}
                      <div className="grid grid-cols-2 gap-6 mb-6">
                        <div>
                          <p className="text-xs font-semibold uppercase tracking-wide mb-2" style={{ color: "#8CA5B0" }}>Data Pasien</p>
                          {[["Nama", selectedTx.name], ["No. RM", selectedTx.rm], ["Usia", `${selectedTx.age} tahun`], ["Penjamin", selectedTx.insuranceType]].map(([k, v]) => (
                            <div key={k} className="flex gap-2 text-xs mb-1">
                              <span className="w-20 shrink-0" style={{ color: "#8CA5B0" }}>{k}</span>
                              <span className="font-medium" style={{ color: "#315E6E", fontFamily: k === "No. RM" ? "var(--font-mono)" : undefined }}>{v}</span>
                            </div>
                          ))}
                        </div>
                        <div>
                          <p className="text-xs font-semibold uppercase tracking-wide mb-2" style={{ color: "#8CA5B0" }}>Detail Layanan</p>
                          {[["Modul", selectedTx.module], ["Tgl Masuk", selectedTx.admitDate], ["Tgl Keluar", selectedTx.dischargeDate], ["Kasir", selectedTx.kasir]].map(([k, v]) => (
                            <div key={k} className="flex gap-2 text-xs mb-1">
                              <span className="w-20 shrink-0" style={{ color: "#8CA5B0" }}>{k}</span>
                              <span className="font-medium" style={{ color: "#315E6E" }}>{v}</span>
                            </div>
                          ))}
                        </div>
                      </div>

                      {/* Line items */}
                      <table className="w-full text-sm mb-5">
                        <thead>
                          <tr style={{ background: "#F7FBFC", borderBottom: "2px solid #D6E8EE" }}>
                            {["NAMA LAYANAN", "QTY", "HARGA SATUAN", "TOTAL"].map((h) => (
                              <th key={h} className="text-left text-xs font-semibold px-3 py-2.5" style={{ color: "#66869A" }}>{h}</th>
                            ))}
                          </tr>
                        </thead>
                        <tbody className="divide-y" style={{ borderColor: "#EEF6F8" }}>
                          {selectedTx.items.map((item, i) => (
                            <tr key={i}>
                              <td className="px-3 py-2 text-sm" style={{ color: "#315E6E" }}>{item.name}</td>
                              <td className="px-3 py-2 text-xs text-center" style={{ color: "#66869A" }}>{item.qty}</td>
                              <td className="px-3 py-2 text-xs" style={{ fontFamily: "var(--font-mono)", color: "#66869A" }}>{rp(item.price)}</td>
                              <td className="px-3 py-2 text-sm font-semibold" style={{ fontFamily: "var(--font-mono)", color: "#315E6E" }}>{rp(item.price * item.qty)}</td>
                            </tr>
                          ))}
                        </tbody>
                      </table>

                      {/* Totals */}
                      <div className="border-t pt-4" style={{ borderColor: "#D6E8EE" }}>
                        {[
                          { label: "Subtotal", val: selectedTx.total, bold: false, color: "#315E6E" },
                          { label: `Tanggungan ${selectedTx.insuranceType}`, val: -selectedTx.covered, bold: false, color: "#1D5FA8" },
                          { label: "Total Tanggung Pasien", val: selectedTx.patientShare, bold: true, color: "#164E63" },
                          { label: "Sudah Dibayar", val: -selectedTx.paid, bold: false, color: "#117A68" },
                          { label: "Sisa Tagihan", val: selectedTx.patientShare - selectedTx.paid, bold: true, color: (selectedTx.patientShare - selectedTx.paid) > 0 ? "#C0362C" : "#117A68" },
                        ].map((row) => (
                          <div key={row.label} className="flex justify-between text-sm py-1">
                            <span style={{ color: "#66869A", fontWeight: row.bold ? 600 : 400 }}>{row.label}</span>
                            <span style={{ fontFamily: "var(--font-mono)", fontWeight: row.bold ? 700 : 500, color: row.color }}>
                              {row.val < 0 ? `- ${rp(-row.val)}` : rp(row.val)}
                            </span>
                          </div>
                        ))}
                      </div>

                      {/* Footer */}
                      <div className="mt-6 pt-4 border-t text-center" style={{ borderColor: "#D6E8EE" }}>
                        <p className="text-xs" style={{ color: "#8CA5B0" }}>Terima kasih telah mempercayakan kesehatan Anda kepada RS Suka Sehat.</p>
                        <p className="text-xs mt-0.5" style={{ color: "#8CA5B0" }}>Invoice ini dicetak oleh sistem SIMRS dan sah tanpa tanda tangan basah.</p>
                      </div>
                    </div>
                  )}
                </div>
              </div>
            </div>
          )}
        </main>
      </div>

      {/* ── Payment Modal ── */}
      {payModal && selectedTx && (
        <div className="fixed inset-0 z-50 flex items-center justify-center" style={{ background: "rgba(0,0,0,0.4)" }}>
          <div className="rounded-2xl p-6 w-[440px] shadow-2xl" style={{ background: "#fff" }}>
            <h3 className="text-base font-bold mb-1" style={{ color: "#164E63" }}>Proses Pembayaran</h3>
            <p className="text-xs mb-4" style={{ color: "#66869A" }}>{selectedTx.name} · {selectedTx.invoiceNo}</p>

            <div className="rounded-lg p-3 mb-4" style={{ background: "#F7FBFC", border: "1px solid #D6E8EE" }}>
              {[["Total Tagihan", rp(selectedTx.total)], ["Tanggungan Asuransi", `- ${rp(selectedTx.covered)}`], ["Tanggung Pasien", rp(selectedTx.patientShare)]].map(([k, v]) => (
                <div key={k} className="flex justify-between text-xs py-1">
                  <span style={{ color: "#66869A" }}>{k}</span>
                  <span className="font-bold" style={{ fontFamily: "var(--font-mono)", color: "#164E63" }}>{v}</span>
                </div>
              ))}
            </div>

            <div className="space-y-4 mb-5">
              <div>
                <label className="text-xs font-semibold block mb-2" style={{ color: "#315E6E" }}>Metode Pembayaran *</label>
                <div className="grid grid-cols-3 gap-2">
                  {(["Tunai", "Transfer Bank", "BPJS", "Asuransi Swasta", "Kartu Kredit", "QRIS"] as PayMethod[]).map((m) => (
                    <button key={m} onClick={() => setPayForm((f) => ({ ...f, method: m }))}
                      className="py-2 px-2 rounded-lg text-xs font-semibold border-2 transition-all"
                      style={{ background: payForm.method === m ? "#EAF6F8" : "#F7FBFC", color: payForm.method === m ? "#0D6E6E" : "#66869A", borderColor: payForm.method === m ? "#0D6E6E" : "#D6E8EE" }}>
                      {m}
                    </button>
                  ))}
                </div>
              </div>
              <div>
                <label className="text-xs font-semibold block mb-1.5" style={{ color: "#315E6E" }}>Jumlah Diterima (Rp) *</label>
                <input type="number" className="w-full border rounded-lg px-3 py-2.5 text-sm outline-none"
                  style={{ borderColor: "#D6E8EE", fontFamily: "var(--font-mono)", color: "#315E6E", background: "#fff" }}
                  placeholder={String(selectedTx.patientShare)}
                  value={payForm.amount} onChange={(e) => setPayForm((f) => ({ ...f, amount: e.target.value }))} />
                {Number(payForm.amount) >= selectedTx.patientShare && (
                  <p className="text-xs mt-1" style={{ color: "#117A68" }}>
                    Kembalian: {rp(Number(payForm.amount) - selectedTx.patientShare)}
                  </p>
                )}
              </div>
            </div>

            <div className="flex gap-3">
              <button onClick={() => setPayModal(false)} className="flex-1 py-2.5 rounded-lg text-sm font-semibold border" style={{ borderColor: "#D6E8EE", color: "#315E6E" }}>Batal</button>
              <button onClick={processPayment} className="flex-1 py-2.5 rounded-lg text-sm font-semibold text-white" style={{ background: "#0D6E6E" }}>Konfirmasi Pembayaran</button>
            </div>
          </div>
        </div>
      )}

      {/* ── Toast ── */}
      {toast && (
        <div className="fixed bottom-5 right-5 px-4 py-3 rounded-lg shadow-lg text-sm font-medium text-white z-50" style={{ background: "#0D6E6E" }}>
          {toast}
        </div>
      )}

      <style>{`@media print { header, aside, .no-print { display: none !important; } main { overflow: visible !important; } }`}</style>
    </div>
  );
}
