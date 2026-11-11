import { useState } from "react";
import type { UserInfo } from "../types/user";
import logoSrc from "../assets/logo.png";

interface FarmasiProps {
  user: UserInfo;
  onLogout: () => void;
}

type FarmasiView = "dashboard" | "resep" | "dispensing" | "stok" | "riwayat";
type ResepStatus = "menunggu" | "diverifikasi" | "disiapkan" | "selesai" | "ditolak";
type StokStatus = "aman" | "menipis" | "habis";
type DrugForm = "Tablet" | "Kapsul" | "Sirup" | "Injeksi" | "Salep" | "Tetes" | "Supositoria" | "Inhaler";

interface Resep {
  id: string;
  no: string;
  patientRm: string;
  patientName: string;
  patientAge: number;
  patientGender: "L" | "P";
  insurance: string;
  doctor: string;
  unit: string;
  visitType: "Rawat Jalan" | "Rawat Inap" | "IGD";
  issuedAt: string;
  status: ResepStatus;
  items: ResepItem[];
  notes: string;
  allergyAlert?: string;
}

interface ResepItem {
  id: string;
  drug: string;
  dose: string;
  form: DrugForm;
  qty: number;
  unit: string;
  route: string;
  frequency: string;
  duration: string;
  signa: string;
  stock: number;
  covered: boolean;
}

interface DrugStock {
  id: string;
  name: string;
  generic: string;
  form: DrugForm;
  strength: string;
  unit: string;
  stock: number;
  minStock: number;
  location: string;
  expiry: string;
  supplier: string;
  lastUpdate: string;
  status: StokStatus;
  price: number;
}

interface DispenseHistory {
  id: string;
  resepId: string;
  resepNo: string;
  patientName: string;
  patientRm: string;
  drug: string;
  qty: number;
  unit: string;
  dispensedAt: string;
  dispensedBy: string;
  insurance: string;
  total: number;
}

// ── Seed data ────────────────────────────────────────────────────────────────

const RESEP_LIST: Resep[] = [
  {
    id: "r1", no: "RX-2026-00451", patientRm: "RM-2026-000201", patientName: "Rina Wulandari",
    patientAge: 41, patientGender: "P", insurance: "BPJS", doctor: "dr. Ahmad Fauzi, Sp.PD",
    unit: "Rawat Jalan – Poli Jantung", visitType: "Rawat Jalan",
    issuedAt: "07/09/2026 09:00", status: "menunggu", notes: "Pasien alergi Penisilin",
    allergyAlert: "ALERGI: Penisilin",
    items: [
      { id: "i1", drug: "Furosemide", dose: "40 mg", form: "Tablet", qty: 30, unit: "tablet", route: "Oral", frequency: "2x sehari", duration: "15 hari", signa: "2 dd 1 tab pagi & sore", stock: 120, covered: true },
      { id: "i2", drug: "Spironolakton", dose: "25 mg", form: "Tablet", qty: 15, unit: "tablet", route: "Oral", frequency: "1x sehari", duration: "15 hari", signa: "1 dd 1 tab pagi", stock: 85, covered: true },
      { id: "i3", drug: "Bisoprolol", dose: "5 mg", form: "Tablet", qty: 15, unit: "tablet", route: "Oral", frequency: "1x sehari", duration: "15 hari", signa: "1 dd 1 tab pagi", stock: 0, covered: true },
    ],
  },
  {
    id: "r2", no: "RX-2026-00452", patientRm: "RM-2026-000221", patientName: "Bambang Irawan",
    patientAge: 45, patientGender: "L", insurance: "Mandiri", doctor: "dr. Budi Hartono, Sp.P",
    unit: "Rawat Inap – Kelas I", visitType: "Rawat Inap",
    issuedAt: "07/09/2026 09:30", status: "diverifikasi", notes: "",
    items: [
      { id: "i4", drug: "Amoksisilin", dose: "500 mg", form: "Kapsul", qty: 21, unit: "kapsul", route: "Oral", frequency: "3x sehari", duration: "7 hari", signa: "3 dd 1 kaps", stock: 200, covered: false },
      { id: "i5", drug: "Paracetamol", dose: "500 mg", form: "Tablet", qty: 30, unit: "tablet", route: "Oral", frequency: "3x sehari (k/p)", duration: "10 hari", signa: "3 dd 1 tab prn demam", stock: 500, covered: false },
    ],
  },
  {
    id: "r3", no: "RX-2026-00453", patientRm: "RM-2026-000224", patientName: "Ahmad Rizal",
    patientAge: 72, patientGender: "L", insurance: "BPJS", doctor: "dr. Candra Utama, Sp.S",
    unit: "ICU", visitType: "IGD",
    issuedAt: "07/09/2026 08:00", status: "disiapkan", notes: "Persiapkan segera",
    allergyAlert: "",
    items: [
      { id: "i6", drug: "Aspirin", dose: "80 mg", form: "Tablet", qty: 30, unit: "tablet", route: "Oral", frequency: "1x sehari", duration: "30 hari", signa: "1 dd 1 tab pagi", stock: 300, covered: true },
      { id: "i7", drug: "Atorvastatin", dose: "20 mg", form: "Tablet", qty: 30, unit: "tablet", route: "Oral", frequency: "1x sehari", duration: "30 hari", signa: "1 dd 1 tab malam", stock: 60, covered: true },
    ],
  },
  {
    id: "r4", no: "RX-2026-00448", patientRm: "RM-2026-000220", patientName: "Siti Nurhaliza",
    patientAge: 54, patientGender: "P", insurance: "BPJS", doctor: "dr. Rina Soekartini, Sp.PD",
    unit: "Rawat Inap – Kelas I", visitType: "Rawat Inap",
    issuedAt: "06/09/2026 14:00", status: "selesai", notes: "",
    items: [
      { id: "i8", drug: "Metformin", dose: "500 mg", form: "Tablet", qty: 60, unit: "tablet", route: "Oral", frequency: "2x sehari", duration: "30 hari", signa: "2 dd 1 tab sesudah makan", stock: 400, covered: true },
      { id: "i9", drug: "Glimepiride", dose: "2 mg", form: "Tablet", qty: 30, unit: "tablet", route: "Oral", frequency: "1x sehari", duration: "30 hari", signa: "1 dd 1 tab sebelum makan", stock: 25, covered: true },
    ],
  },
];

const DRUG_STOCK: DrugStock[] = [
  { id: "s1", name: "Furosemide 40 mg", generic: "Furosemide", form: "Tablet", strength: "40 mg", unit: "tablet", stock: 120, minStock: 50, location: "Rak A-01", expiry: "12/2027", supplier: "PT Kimia Farma", lastUpdate: "01/09/2026", status: "aman", price: 850 },
  { id: "s2", name: "Spironolakton 25 mg", generic: "Spironolakton", form: "Tablet", strength: "25 mg", unit: "tablet", stock: 85, minStock: 40, location: "Rak A-02", expiry: "06/2027", supplier: "PT Kimia Farma", lastUpdate: "01/09/2026", status: "aman", price: 1200 },
  { id: "s3", name: "Bisoprolol 5 mg", generic: "Bisoprolol", form: "Tablet", strength: "5 mg", unit: "tablet", stock: 0, minStock: 30, location: "Rak A-03", expiry: "03/2027", supplier: "PT Dexa Medica", lastUpdate: "25/08/2026", status: "habis", price: 2100 },
  { id: "s4", name: "Amoksisilin 500 mg", generic: "Amoksisilin", form: "Kapsul", strength: "500 mg", unit: "kapsul", stock: 200, minStock: 100, location: "Rak B-01", expiry: "09/2027", supplier: "PT Kalbe Farma", lastUpdate: "03/09/2026", status: "aman", price: 1500 },
  { id: "s5", name: "Paracetamol 500 mg", generic: "Paracetamol", form: "Tablet", strength: "500 mg", unit: "tablet", stock: 500, minStock: 200, location: "Rak B-02", expiry: "11/2027", supplier: "PT Sanbe Farma", lastUpdate: "05/09/2026", status: "aman", price: 400 },
  { id: "s6", name: "Aspirin 80 mg", generic: "Asam Asetilsalisilat", form: "Tablet", strength: "80 mg", unit: "tablet", stock: 300, minStock: 100, location: "Rak C-01", expiry: "04/2027", supplier: "PT Bayer", lastUpdate: "02/09/2026", status: "aman", price: 600 },
  { id: "s7", name: "Atorvastatin 20 mg", generic: "Atorvastatin", form: "Tablet", strength: "20 mg", unit: "tablet", stock: 60, minStock: 50, location: "Rak C-02", expiry: "08/2027", supplier: "PT Pfizer", lastUpdate: "01/09/2026", status: "menipis", price: 4500 },
  { id: "s8", name: "Metformin 500 mg", generic: "Metformin HCl", form: "Tablet", strength: "500 mg", unit: "tablet", stock: 400, minStock: 150, location: "Rak D-01", expiry: "02/2028", supplier: "PT Kimia Farma", lastUpdate: "04/09/2026", status: "aman", price: 750 },
  { id: "s9", name: "Glimepiride 2 mg", generic: "Glimepiride", form: "Tablet", strength: "2 mg", unit: "tablet", stock: 25, minStock: 40, location: "Rak D-02", expiry: "01/2027", supplier: "PT Aventis", lastUpdate: "30/08/2026", status: "menipis", price: 3200 },
  { id: "s10", name: "NaCl 0.9% 500 mL", generic: "Natrium Klorida", form: "Injeksi", strength: "0.9%", unit: "botol", stock: 45, minStock: 20, location: "Gudang Infus", expiry: "07/2027", supplier: "PT Otsuka", lastUpdate: "06/09/2026", status: "aman", price: 18000 },
];

const DISPENSE_HISTORY: DispenseHistory[] = [
  { id: "d1", resepId: "r4", resepNo: "RX-2026-00448", patientName: "Siti Nurhaliza", patientRm: "RM-2026-000220", drug: "Metformin 500 mg (60 tab) + Glimepiride 2 mg (30 tab)", qty: 90, unit: "tablet", dispensedAt: "06/09/2026 15:30", dispensedBy: "apt. Budi Santoso", insurance: "BPJS", total: 141000 },
  { id: "d2", resepId: "r3", resepNo: "RX-2026-00441", patientName: "Herman Suryadi", patientRm: "RM-2026-000219", drug: "Furosemide 40 mg (30 tab) + Spironolakton 25 mg (15 tab)", qty: 45, unit: "tablet", dispensedAt: "05/09/2026 10:15", dispensedBy: "apt. Budi Santoso", insurance: "BPJS", total: 43500 },
  { id: "d3", resepId: "r2", resepNo: "RX-2026-00437", patientName: "Wati Susanti", patientRm: "RM-2026-000222", drug: "Amoksisilin 500 mg (21 kaps) + Paracetamol 500 mg (30 tab)", qty: 51, unit: "pcs", dispensedAt: "05/09/2026 09:00", dispensedBy: "apt. Budi Santoso", insurance: "Asuransi Swasta", total: 43500 },
];

// ── Style helpers ────────────────────────────────────────────────────────────

const RESEP_STYLE: Record<ResepStatus, { bg: string; color: string; label: string; dot: string }> = {
  menunggu:    { bg: "#FFF0D5", color: "#A66700", label: "Menunggu Verifikasi", dot: "#A66700" },
  diverifikasi:{ bg: "#E1EEFB", color: "#1D5FA8", label: "Terverifikasi",        dot: "#1D5FA8" },
  disiapkan:   { bg: "#EAF6F8", color: "#0D6E6E", label: "Sedang Disiapkan",     dot: "#0D6E6E" },
  selesai:     { bg: "#D9F3EA", color: "#117A68", label: "Diserahkan",           dot: "#117A68" },
  ditolak:     { bg: "#FDE7E7", color: "#C0362C", label: "Ditolak",              dot: "#C0362C" },
};

const STOK_STYLE: Record<StokStatus, { bg: string; color: string; label: string }> = {
  aman:    { bg: "#D9F3EA", color: "#117A68", label: "Aman" },
  menipis: { bg: "#FFF0D5", color: "#A66700", label: "Menipis" },
  habis:   { bg: "#FDE7E7", color: "#C0362C", label: "Habis" },
};

const VISIT_STYLE: Record<string, { bg: string; color: string }> = {
  "Rawat Jalan": { bg: "#D9F3EA", color: "#117A68" },
  "Rawat Inap":  { bg: "#E1EEFB", color: "#1D5FA8" },
  "IGD":         { bg: "#FDE7E7", color: "#C0362C" },
};

const SIDEBAR_ITEMS: { key: FarmasiView; label: string; icon: string }[] = [
  { key: "dashboard",  label: "Dashboard",          icon: "📊" },
  { key: "resep",      label: "Antrian Resep",       icon: "📄" },
  { key: "dispensing", label: "Dispensing Obat",     icon: "🧴" },
  { key: "stok",       label: "Manajemen Stok",      icon: "📦" },
  { key: "riwayat",    label: "Riwayat Dispensing",  icon: "🗂" },
];

function fmt(n: number) { return n.toLocaleString("id-ID"); }

// ── Component ─────────────────────────────────────────────────────────────────

export default function Farmasi({ user, onLogout }: FarmasiProps) {
  const [view, setView]           = useState<FarmasiView>("dashboard");
  const [collapsed, setCollapsed] = useState(false);
  const [notif]                   = useState(2);

  const [resepList, setResepList] = useState<Resep[]>(RESEP_LIST);
  const [stocks]                  = useState<DrugStock[]>(DRUG_STOCK);
  const [history]                 = useState<DispenseHistory[]>(DISPENSE_HISTORY);

  const [toast, setToast]         = useState<string | null>(null);
  const [selectedResepId, setSelectedResepId] = useState<string>("r1");
  const [rejectModal, setRejectModal]         = useState<string | null>(null);
  const [rejectReason, setRejectReason]       = useState("");
  const [dispenseConfirm, setDispenseConfirm] = useState<string | null>(null);

  // Stok filters
  const [stokSearch, setStokSearch] = useState("");
  const [stokFilter, setStokFilter] = useState("Semua");

  // Resep filters
  const [resepFilter, setResepFilter] = useState("Semua");
  const [resepSearch, setResepSearch] = useState("");

  // Riwayat search
  const [histSearch, setHistSearch] = useState("");

  function showToast(msg: string) {
    setToast(msg);
    setTimeout(() => setToast(null), 2500);
  }

  function advanceResepStatus(id: string, to: ResepStatus) {
    setResepList((prev) => prev.map((r) => r.id === id ? { ...r, status: to } : r));
    const labels: Record<ResepStatus, string> = { menunggu: "", diverifikasi: "Resep berhasil diverifikasi", disiapkan: "Obat sedang disiapkan", selesai: "Obat berhasil diserahkan ke pasien", ditolak: "Resep ditolak" };
    showToast(labels[to]);
    setDispenseConfirm(null);
  }

  function rejectResep(id: string) {
    if (!rejectReason.trim()) return;
    setResepList((prev) => prev.map((r) => r.id === id ? { ...r, status: "ditolak" } : r));
    showToast("Resep ditolak — alasan telah dicatat");
    setRejectModal(null);
    setRejectReason("");
  }

  // ── Derived ───────────────────────────────────────────────────────────────
  const waiting      = resepList.filter((r) => r.status === "menunggu").length;
  const verified     = resepList.filter((r) => r.status === "diverifikasi").length;
  const preparing    = resepList.filter((r) => r.status === "disiapkan").length;
  const done         = resepList.filter((r) => r.status === "selesai").length;
  const stockAlert   = stocks.filter((s) => s.status !== "aman").length;
  const outOfStock   = stocks.filter((s) => s.status === "habis").length;

  const selectedResep = resepList.find((r) => r.id === selectedResepId)!;

  const filteredResep = resepList.filter((r) => {
    const ms = resepFilter === "Semua" || r.status === resepFilter;
    const mq = !resepSearch || r.patientName.toLowerCase().includes(resepSearch.toLowerCase()) || r.no.includes(resepSearch) || r.patientRm.includes(resepSearch);
    return ms && mq;
  });

  const filteredStock = stocks.filter((s) => {
    const ms = stokFilter === "Semua" || s.status === stokFilter;
    const mq = !stokSearch || s.name.toLowerCase().includes(stokSearch.toLowerCase()) || s.generic.toLowerCase().includes(stokSearch.toLowerCase());
    return ms && mq;
  });

  const filteredHistory = history.filter((h) => !histSearch || h.patientName.toLowerCase().includes(histSearch.toLowerCase()) || h.resepNo.includes(histSearch));

  // ──────────────────────────────────────────────────────────────────────────
  return (
    <div className="flex flex-col h-full">
      {/* Header */}
      <header className="flex items-center justify-between px-5 shrink-0 border-b"
        style={{ height: 52, background: "#0D6E6E", borderColor: "#0A4D68" }}>
        <div className="flex items-center gap-3">
          <button onClick={() => setCollapsed((c) => !c)} className="text-white/70 hover:text-white p-1">
            <svg width="18" height="18" viewBox="0 0 18 18" fill="none">
              <rect y="3"    width="18" height="1.5" rx=".75" fill="currentColor" />
              <rect y="8.25" width="18" height="1.5" rx=".75" fill="currentColor" />
              <rect y="13.5" width="18" height="1.5" rx=".75" fill="currentColor" />
            </svg>
          </button>
          <img src={logoSrc} alt="logo" className="w-8 h-8 rounded-full" style={{ background: "#fff" }} />
          <span className="text-white font-bold text-sm tracking-wide">RS SUKA SEHAT</span>
          <span className="hidden md:inline text-white/40 text-xs">— Instalasi Farmasi</span>
        </div>
        <div className="flex items-center gap-3">
          <button className="relative text-white/70 hover:text-white">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
              <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/>
            </svg>
            {notif > 0 && (
              <span className="absolute -top-1 -right-1 w-3.5 h-3.5 rounded-full flex items-center justify-center text-white"
                style={{ background: "#C0362C", fontSize: "9px" }}>{notif}</span>
            )}
          </button>
          <div className="flex items-center gap-2">
            <div className="w-7 h-7 rounded-full flex items-center justify-center text-xs font-bold text-white"
              style={{ background: "rgba(255,255,255,0.2)" }}>{user.initials}</div>
            <div className="hidden sm:block">
              <p className="text-white text-xs font-semibold leading-tight">{user.fullName}</p>
              <p className="text-white/60 text-xs leading-tight">Farmasi — {user.unit}</p>
            </div>
          </div>
          <button onClick={onLogout} className="text-xs px-2.5 py-1 rounded font-medium text-white"
            style={{ background: "#C0362C" }}>Keluar</button>
        </div>
      </header>

      <div className="flex flex-1 overflow-hidden">
        {/* Sidebar */}
        <aside className="flex flex-col shrink-0 border-r transition-all duration-200"
          style={{ width: collapsed ? 52 : 216, background: "#fff", borderColor: "#D6E8EE" }}>
          {!collapsed && (
            <div className="px-3 pt-4 pb-2">
              <p className="text-xs font-semibold uppercase tracking-widest" style={{ color: "#8CA5B0" }}>Farmasi</p>
            </div>
          )}
          <nav className="flex-1 p-2 space-y-0.5">
            {SIDEBAR_ITEMS.map((item) => {
              const active = view === item.key;
              const badge = item.key === "resep" ? waiting : item.key === "stok" ? stockAlert : 0;
              return (
                <button key={item.key} onClick={() => setView(item.key)}
                  className="w-full flex items-center gap-2.5 rounded-lg px-2.5 py-2.5 text-left transition-all"
                  style={{ background: active ? "#EAF6F8" : "transparent", color: active ? "#0D6E6E" : "#456D7D" }}>
                  <span className="text-base shrink-0">{item.icon}</span>
                  {!collapsed && <span className="text-xs font-semibold">{item.label}</span>}
                  {badge > 0 && !collapsed && (
                    <span className="ml-auto text-white text-xs font-bold px-1.5 py-0.5 rounded-full"
                      style={{ background: "#C0362C", fontSize: "9px" }}>{badge}</span>
                  )}
                  {active && !collapsed && badge === 0 && <span className="w-1.5 h-1.5 rounded-full ml-auto" style={{ background: "#0D6E6E" }} />}
                </button>
              );
            })}
          </nav>
          {!collapsed && (
            <div className="p-3 border-t mx-2 mb-2 rounded-lg" style={{ borderColor: "#D6E8EE", background: "#F7FBFC" }}>
              <p className="text-xs font-semibold" style={{ color: "#315E6E" }}>{user.fullName}</p>
              <p className="text-xs mt-0.5" style={{ color: "#8CA5B0" }}>Apoteker · {user.unit}</p>
            </div>
          )}
        </aside>

        {/* Main */}
        <main className="flex-1 overflow-y-auto" style={{ background: "#F3FAFC" }}>

          {/* ════ DASHBOARD ════ */}
          {view === "dashboard" && (
            <div className="p-6 space-y-5">
              <div>
                <h1 className="text-xl font-bold" style={{ color: "#164E63" }}>Dashboard Farmasi</h1>
                <p className="text-xs mt-0.5" style={{ color: "#66869A" }}>
                  {new Date().toLocaleDateString("id-ID", { weekday: "long", day: "2-digit", month: "long", year: "numeric" })} · Shift Pagi
                </p>
              </div>

              {/* KPIs */}
              <div className="grid grid-cols-2 lg:grid-cols-4 gap-4">
                {[
                  { label: "Menunggu Verifikasi", val: String(waiting),   sub: "resep baru masuk",    icon: "📄", accent: "#A66700", bg: "#FFF0D5" },
                  { label: "Sedang Disiapkan",    val: String(preparing), sub: "dalam proses",        icon: "🧴", accent: "#0D6E6E", bg: "#EAF6F8" },
                  { label: "Diserahkan Hari Ini", val: String(done),      sub: "transaksi selesai",   icon: "✅", accent: "#117A68", bg: "#D9F3EA" },
                  { label: "Stok Perlu Perhatian",val: String(stockAlert),sub: `${outOfStock} habis`, icon: "⚠", accent: "#C0362C", bg: "#FDE7E7" },
                ].map((k) => (
                  <div key={k.label} className="rounded-xl p-5 shadow-sm" style={{ background: "#fff", border: "1px solid #D6E8EE" }}>
                    <div className="flex items-start justify-between mb-3">
                      <p className="text-xs font-medium" style={{ color: "#66869A" }}>{k.label}</p>
                      <span className="w-9 h-9 rounded-lg flex items-center justify-center text-lg" style={{ background: k.bg }}>{k.icon}</span>
                    </div>
                    <p className="text-3xl font-bold" style={{ fontFamily: "var(--font-mono)", color: k.accent }}>{k.val}</p>
                    <p className="text-xs mt-1" style={{ color: "#8CA5B0" }}>{k.sub}</p>
                  </div>
                ))}
              </div>

              <div className="grid grid-cols-3 gap-5">
                {/* Resep queue */}
                <div className="col-span-2 rounded-xl shadow-sm overflow-hidden" style={{ background: "#fff", border: "1px solid #D6E8EE" }}>
                  <div className="flex items-center justify-between px-5 py-4 border-b" style={{ borderColor: "#D6E8EE" }}>
                    <h2 className="text-sm font-semibold" style={{ color: "#164E63" }}>Antrean Resep Aktif</h2>
                    <button onClick={() => setView("resep")} className="text-xs font-medium" style={{ color: "#0D6E6E" }}>Lihat semua →</button>
                  </div>
                  <div className="divide-y" style={{ borderColor: "#EEF6F8" }}>
                    {resepList.filter((r) => r.status !== "selesai" && r.status !== "ditolak").map((r) => {
                      const rs = RESEP_STYLE[r.status];
                      const vs = VISIT_STYLE[r.visitType];
                      return (
                        <div key={r.id} className="flex items-center gap-4 px-5 py-3 hover:bg-slate-50 transition-colors cursor-pointer"
                          onClick={() => { setSelectedResepId(r.id); setView("dispensing"); }}>
                          <div className="flex-1 min-w-0">
                            <div className="flex items-center gap-2 mb-0.5">
                              <p className="text-sm font-semibold" style={{ color: "#164E63" }}>{r.patientName}</p>
                              {r.allergyAlert && <span className="text-xs px-1.5 py-0.5 rounded font-bold" style={{ background: "#FDE7E7", color: "#C0362C" }}>⚠ ALERGI</span>}
                            </div>
                            <p className="text-xs" style={{ fontFamily: "var(--font-mono)", color: "#8CA5B0" }}>{r.no} · {r.issuedAt}</p>
                            <p className="text-xs" style={{ color: "#66869A" }}>{r.doctor} · {r.items.length} item</p>
                          </div>
                          <div className="flex items-center gap-2 shrink-0">
                            <span className="text-xs px-1.5 py-0.5 rounded font-medium" style={{ background: vs.bg, color: vs.color }}>{r.visitType}</span>
                            <span className="flex items-center gap-1 text-xs font-medium">
                              <span className="w-1.5 h-1.5 rounded-full" style={{ background: rs.dot }} />
                              <span style={{ color: rs.color }}>{rs.label}</span>
                            </span>
                          </div>
                        </div>
                      );
                    })}
                    {resepList.filter((r) => r.status !== "selesai" && r.status !== "ditolak").length === 0 && (
                      <p className="py-8 text-center text-sm" style={{ color: "#8CA5B0" }}>✓ Semua resep telah diproses</p>
                    )}
                  </div>
                </div>

                {/* Stock alerts */}
                <div className="rounded-xl shadow-sm overflow-hidden" style={{ background: "#fff", border: "1px solid #D6E8EE" }}>
                  <div className="px-4 py-4 border-b" style={{ borderColor: "#D6E8EE" }}>
                    <h2 className="text-sm font-semibold" style={{ color: "#164E63" }}>⚠ Alert Stok Obat</h2>
                  </div>
                  <div className="divide-y" style={{ borderColor: "#EEF6F8" }}>
                    {stocks.filter((s) => s.status !== "aman").map((s) => {
                      const ss = STOK_STYLE[s.status];
                      return (
                        <div key={s.id} className="px-4 py-3">
                          <div className="flex items-start justify-between gap-2 mb-1">
                            <p className="text-xs font-semibold" style={{ color: "#164E63" }}>{s.name}</p>
                            <span className="text-xs px-1.5 py-0.5 rounded-full font-medium shrink-0"
                              style={{ background: ss.bg, color: ss.color }}>{ss.label}</span>
                          </div>
                          <p className="text-xs" style={{ fontFamily: "var(--font-mono)", color: s.status === "habis" ? "#C0362C" : "#A66700" }}>
                            Sisa: {s.stock} {s.unit} (min. {s.minStock})
                          </p>
                          <p className="text-xs mt-0.5" style={{ color: "#8CA5B0" }}>{s.location}</p>
                        </div>
                      );
                    })}
                    {stocks.filter((s) => s.status !== "aman").length === 0 && (
                      <p className="py-6 text-center text-xs" style={{ color: "#8CA5B0" }}>Semua stok dalam kondisi aman</p>
                    )}
                  </div>
                  <div className="px-4 pb-4">
                    <button onClick={() => setView("stok")} className="w-full text-xs py-1.5 rounded-lg border font-medium"
                      style={{ borderColor: "#D6E8EE", color: "#0D6E6E" }}>Kelola Stok Obat →</button>
                  </div>
                </div>
              </div>

              {/* Status breakdown */}
              <div className="rounded-xl p-5 shadow-sm" style={{ background: "#fff", border: "1px solid #D6E8EE" }}>
                <h2 className="text-sm font-semibold mb-4" style={{ color: "#164E63" }}>Ringkasan Status Resep Hari Ini</h2>
                <div className="grid grid-cols-4 gap-4">
                  {[
                    { title: "Menunggu",      count: waiting,   ...RESEP_STYLE.menunggu },
                    { title: "Terverifikasi", count: verified,  ...RESEP_STYLE.diverifikasi },
                    { title: "Disiapkan",     count: preparing, ...RESEP_STYLE.disiapkan },
                    { title: "Selesai",       count: done,      ...RESEP_STYLE.selesai },
                  ].map((s) => (
                    <div key={s.title} className="flex items-center gap-3 p-3 rounded-xl" style={{ background: "#F7FBFC" }}>
                      <span className="w-3 h-3 rounded-full shrink-0" style={{ background: s.dot }} />
                      <div>
                        <p className="text-2xl font-bold" style={{ fontFamily: "var(--font-mono)", color: s.color }}>{s.count}</p>
                        <p className="text-xs" style={{ color: "#66869A" }}>{s.title}</p>
                      </div>
                    </div>
                  ))}
                </div>
              </div>
            </div>
          )}

          {/* ════ ANTRIAN RESEP ════ */}
          {view === "resep" && (
            <div className="p-6 space-y-5">
              <div className="flex items-center justify-between">
                <div>
                  <h1 className="text-xl font-bold" style={{ color: "#164E63" }}>Antrian Resep</h1>
                  <p className="text-xs mt-0.5" style={{ color: "#66869A" }}>Verifikasi dan proses resep dari seluruh unit</p>
                </div>
              </div>

              {/* Filters */}
              <div className="flex gap-3">
                <div className="relative" style={{ minWidth: 260 }}>
                  <span className="absolute left-3 top-1/2 -translate-y-1/2" style={{ color: "#8CA5B0", fontSize: 13 }}>🔍</span>
                  <input className="w-full border rounded-lg pl-9 pr-4 py-2 text-sm outline-none"
                    style={{ borderColor: "#D6E8EE", background: "#fff", color: "#315E6E" }}
                    placeholder="Cari nama, No. Resep, No. RM..."
                    value={resepSearch} onChange={(e) => setResepSearch(e.target.value)} />
                </div>
                <div className="flex gap-1.5 flex-wrap">
                  {["Semua", "menunggu", "diverifikasi", "disiapkan", "selesai", "ditolak"].map((s) => (
                    <button key={s} onClick={() => setResepFilter(s)}
                      className="text-xs px-3 py-2 rounded-lg border font-medium capitalize"
                      style={{ background: resepFilter === s ? "#0D6E6E" : "#fff", color: resepFilter === s ? "#fff" : "#66869A", borderColor: resepFilter === s ? "#0D6E6E" : "#D6E8EE" }}>
                      {s === "Semua" ? "Semua" : RESEP_STYLE[s as ResepStatus].label}
                    </button>
                  ))}
                </div>
              </div>

              {/* Table */}
              <div className="rounded-xl shadow-sm overflow-hidden" style={{ background: "#fff", border: "1px solid #D6E8EE" }}>
                <table className="w-full text-sm">
                  <thead>
                    <tr style={{ background: "#F7FBFC", borderBottom: "1px solid #D6E8EE" }}>
                      {["NO. RESEP", "PASIEN", "DOKTER / UNIT", "ITEM", "PENJAMIN", "WAKTU", "STATUS", "AKSI"].map((h) => (
                        <th key={h} className="text-left text-xs font-semibold px-4 py-3" style={{ color: "#66869A" }}>{h}</th>
                      ))}
                    </tr>
                  </thead>
                  <tbody className="divide-y" style={{ borderColor: "#EEF6F8" }}>
                    {filteredResep.map((r) => {
                      const rs = RESEP_STYLE[r.status];
                      const vs = VISIT_STYLE[r.visitType];
                      const hasStockOut = r.items.some((i) => i.stock === 0);
                      return (
                        <tr key={r.id} className="hover:bg-slate-50 transition-colors">
                          <td className="px-4 py-3">
                            <p className="text-xs font-mono" style={{ fontFamily: "var(--font-mono)", color: "#66869A" }}>{r.no}</p>
                          </td>
                          <td className="px-4 py-3">
                            <div className="flex items-center gap-1.5">
                              <p className="text-sm font-semibold" style={{ color: "#164E63" }}>{r.patientName}</p>
                              {r.allergyAlert && <span className="text-xs px-1 py-0.5 rounded font-bold" style={{ background: "#FDE7E7", color: "#C0362C" }}>⚠</span>}
                            </div>
                            <p className="text-xs" style={{ fontFamily: "var(--font-mono)", color: "#8CA5B0" }}>{r.patientRm} · {r.patientAge} thn · {r.patientGender}</p>
                          </td>
                          <td className="px-4 py-3">
                            <p className="text-xs font-medium" style={{ color: "#315E6E" }}>{r.doctor}</p>
                            <span className="text-xs px-1 py-0.5 rounded mt-0.5 inline-block" style={{ background: vs.bg, color: vs.color }}>{r.visitType}</span>
                          </td>
                          <td className="px-4 py-3 text-center">
                            <span className="text-sm font-bold" style={{ color: "#0D6E6E" }}>{r.items.length}</span>
                            {hasStockOut && <p className="text-xs" style={{ color: "#C0362C" }}>Stok habis!</p>}
                          </td>
                          <td className="px-4 py-3 text-xs" style={{ color: "#66869A" }}>{r.insurance}</td>
                          <td className="px-4 py-3 text-xs" style={{ fontFamily: "var(--font-mono)", color: "#8CA5B0" }}>{r.issuedAt.split(" ")[1]}</td>
                          <td className="px-4 py-3">
                            <span className="flex items-center gap-1 text-xs font-medium whitespace-nowrap">
                              <span className="w-1.5 h-1.5 rounded-full" style={{ background: rs.dot }} />
                              <span style={{ color: rs.color }}>{rs.label}</span>
                            </span>
                          </td>
                          <td className="px-4 py-3">
                            <div className="flex gap-1">
                              <button onClick={() => { setSelectedResepId(r.id); setView("dispensing"); }}
                                className="text-xs px-2.5 py-1 rounded-lg font-medium border"
                                style={{ borderColor: "#0D6E6E", color: "#0D6E6E" }}>Detail</button>
                              {r.status === "menunggu" && (
                                <button onClick={() => advanceResepStatus(r.id, "diverifikasi")}
                                  className="text-xs px-2.5 py-1 rounded-lg font-medium text-white"
                                  style={{ background: "#1D5FA8" }}>Verifikasi</button>
                              )}
                              {r.status === "diverifikasi" && (
                                <button onClick={() => advanceResepStatus(r.id, "disiapkan")}
                                  className="text-xs px-2.5 py-1 rounded-lg font-medium text-white"
                                  style={{ background: "#0D6E6E" }}>Siapkan</button>
                              )}
                              {(r.status === "menunggu" || r.status === "diverifikasi") && (
                                <button onClick={() => setRejectModal(r.id)}
                                  className="text-xs px-2 py-1 rounded-lg border"
                                  style={{ borderColor: "#FDE7E7", color: "#C0362C" }}>Tolak</button>
                              )}
                            </div>
                          </td>
                        </tr>
                      );
                    })}
                    {filteredResep.length === 0 && (
                      <tr><td colSpan={8} className="px-4 py-10 text-center text-sm" style={{ color: "#8CA5B0" }}>Tidak ada data resep</td></tr>
                    )}
                  </tbody>
                </table>
              </div>
            </div>
          )}

          {/* ════ DISPENSING ════ */}
          {view === "dispensing" && selectedResep && (
            <div className="p-6 space-y-5">
              <div className="flex items-center justify-between">
                <div>
                  <h1 className="text-xl font-bold" style={{ color: "#164E63" }}>Dispensing Obat</h1>
                  <p className="text-xs mt-0.5" style={{ color: "#66869A" }}>Detail resep dan konfirmasi penyerahan obat ke pasien</p>
                </div>
                <button onClick={() => setView("resep")} className="text-xs px-3 py-1.5 rounded-lg border font-medium"
                  style={{ borderColor: "#D6E8EE", color: "#315E6E" }}>← Kembali ke Antrian</button>
              </div>

              {/* Resep picker */}
              <div className="flex gap-2 flex-wrap">
                {resepList.filter((r) => r.status !== "selesai" && r.status !== "ditolak").map((r) => {
                  const rs = RESEP_STYLE[r.status];
                  return (
                    <button key={r.id} onClick={() => setSelectedResepId(r.id)}
                      className="flex items-center gap-2 px-3 py-2 rounded-xl border-2 text-left transition-all"
                      style={{ background: selectedResepId === r.id ? "#EAF6F8" : "#fff", borderColor: selectedResepId === r.id ? "#0D6E6E" : "#D6E8EE" }}>
                      <span className="w-2 h-2 rounded-full" style={{ background: rs.dot }} />
                      <div>
                        <p className="text-xs font-semibold" style={{ color: "#164E63" }}>{r.patientName}</p>
                        <p className="text-xs" style={{ fontFamily: "var(--font-mono)", color: "#8CA5B0" }}>{r.no}</p>
                      </div>
                    </button>
                  );
                })}
              </div>

              <div className="grid grid-cols-3 gap-5">
                {/* Patient info */}
                <div className="space-y-4">
                  <div className="rounded-xl p-5 shadow-sm" style={{ background: "#fff", border: "1px solid #D6E8EE" }}>
                    <h3 className="text-xs font-semibold uppercase tracking-wide mb-3" style={{ color: "#8CA5B0" }}>Info Pasien</h3>
                    <p className="text-base font-bold" style={{ color: "#164E63" }}>{selectedResep.patientName}</p>
                    <p className="text-xs mt-0.5" style={{ fontFamily: "var(--font-mono)", color: "#66869A" }}>{selectedResep.patientRm}</p>
                    <p className="text-xs mt-0.5" style={{ color: "#66869A" }}>{selectedResep.patientAge} thn · {selectedResep.patientGender === "L" ? "Laki-laki" : "Perempuan"}</p>
                    <div className="mt-3 pt-3 border-t space-y-1" style={{ borderColor: "#D6E8EE" }}>
                      <p className="text-xs"><span style={{ color: "#8CA5B0" }}>Dokter: </span><span style={{ color: "#315E6E" }}>{selectedResep.doctor}</span></p>
                      <p className="text-xs"><span style={{ color: "#8CA5B0" }}>Unit: </span><span style={{ color: "#315E6E" }}>{selectedResep.unit}</span></p>
                      <p className="text-xs"><span style={{ color: "#8CA5B0" }}>Penjamin: </span><span style={{ color: "#315E6E" }}>{selectedResep.insurance}</span></p>
                      <p className="text-xs"><span style={{ color: "#8CA5B0" }}>No. Resep: </span><span style={{ fontFamily: "var(--font-mono)", color: "#315E6E" }}>{selectedResep.no}</span></p>
                    </div>
                  </div>

                  {selectedResep.allergyAlert && (
                    <div className="rounded-xl p-4" style={{ background: "#FDE7E7", border: "2px solid #fca5a5" }}>
                      <p className="text-sm font-bold" style={{ color: "#C0362C" }}>⚠ PERINGATAN ALERGI</p>
                      <p className="text-xs mt-1" style={{ color: "#C0362C" }}>{selectedResep.allergyAlert}</p>
                      <p className="text-xs mt-1" style={{ color: "#991b1b" }}>Pastikan tidak ada obat yang tergolong alergen sebelum dispensing.</p>
                    </div>
                  )}

                  {selectedResep.notes && (
                    <div className="rounded-xl p-4" style={{ background: "#FFF0D5", border: "1px solid #fde68a" }}>
                      <p className="text-xs font-semibold mb-1" style={{ color: "#A66700" }}>📝 Catatan Dokter</p>
                      <p className="text-xs" style={{ color: "#92400e" }}>{selectedResep.notes}</p>
                    </div>
                  )}

                  {/* Status + actions */}
                  <div className="rounded-xl p-4 shadow-sm space-y-3" style={{ background: "#fff", border: "1px solid #D6E8EE" }}>
                    <div>
                      <p className="text-xs font-semibold mb-1" style={{ color: "#315E6E" }}>Status Resep</p>
                      <span className="flex items-center gap-2 text-sm font-semibold">
                        <span className="w-2.5 h-2.5 rounded-full" style={{ background: RESEP_STYLE[selectedResep.status].dot }} />
                        <span style={{ color: RESEP_STYLE[selectedResep.status].color }}>{RESEP_STYLE[selectedResep.status].label}</span>
                      </span>
                    </div>
                    {selectedResep.status === "menunggu" && (
                      <button onClick={() => advanceResepStatus(selectedResep.id, "diverifikasi")}
                        className="w-full py-2.5 rounded-lg text-sm font-semibold text-white"
                        style={{ background: "#1D5FA8" }}>✓ Verifikasi Resep</button>
                    )}
                    {selectedResep.status === "diverifikasi" && (
                      <button onClick={() => advanceResepStatus(selectedResep.id, "disiapkan")}
                        className="w-full py-2.5 rounded-lg text-sm font-semibold text-white"
                        style={{ background: "#0D6E6E" }}>🧴 Mulai Siapkan Obat</button>
                    )}
                    {selectedResep.status === "disiapkan" && (
                      <button onClick={() => setDispenseConfirm(selectedResep.id)}
                        className="w-full py-2.5 rounded-lg text-sm font-semibold text-white"
                        style={{ background: "#117A68" }}>✅ Serahkan ke Pasien</button>
                    )}
                    {(selectedResep.status === "menunggu" || selectedResep.status === "diverifikasi") && (
                      <button onClick={() => setRejectModal(selectedResep.id)}
                        className="w-full py-2.5 rounded-lg text-sm font-semibold border"
                        style={{ borderColor: "#fca5a5", color: "#C0362C" }}>✗ Tolak Resep</button>
                    )}
                  </div>
                </div>

                {/* Drug items */}
                <div className="col-span-2 space-y-3">
                  <div className="flex items-center justify-between">
                    <h2 className="text-sm font-semibold" style={{ color: "#164E63" }}>Daftar Obat — {selectedResep.items.length} item</h2>
                    <span className="text-xs px-2 py-0.5 rounded-full font-medium"
                      style={{ background: VISIT_STYLE[selectedResep.visitType].bg, color: VISIT_STYLE[selectedResep.visitType].color }}>
                      {selectedResep.visitType}
                    </span>
                  </div>
                  {selectedResep.items.map((item) => {
                    const stockOk = item.stock >= item.qty;
                    return (
                      <div key={item.id} className="rounded-xl p-4 shadow-sm" style={{ background: "#fff", border: `1px solid ${item.stock === 0 ? "#fca5a5" : "#D6E8EE"}` }}>
                        <div className="flex items-start justify-between gap-3">
                          <div className="flex-1">
                            <div className="flex items-center gap-2 mb-1">
                              <p className="text-sm font-bold" style={{ color: "#164E63" }}>{item.drug}</p>
                              <span className="text-xs px-1.5 py-0.5 rounded font-medium" style={{ fontFamily: "var(--font-mono)", background: "#EAF6F8", color: "#0D6E6E" }}>{item.dose}</span>
                              <span className="text-xs px-1.5 py-0.5 rounded" style={{ background: "#EEF6F8", color: "#456D7D" }}>{item.form}</span>
                              {item.covered && <span className="text-xs px-1.5 py-0.5 rounded font-medium" style={{ background: "#D9F3EA", color: "#117A68" }}>Tanggungan</span>}
                            </div>
                            <p className="text-xs" style={{ color: "#66869A" }}>
                              {item.route} · {item.frequency} · {item.duration}
                            </p>
                            <p className="text-xs mt-1 font-medium" style={{ color: "#315E6E" }}>Signa: {item.signa}</p>
                          </div>
                          <div className="text-right shrink-0">
                            <p className="text-base font-bold" style={{ fontFamily: "var(--font-mono)", color: "#164E63" }}>{item.qty} {item.unit}</p>
                            <div className="mt-1">
                              {item.stock === 0 ? (
                                <span className="text-xs px-2 py-0.5 rounded-full font-bold" style={{ background: "#FDE7E7", color: "#C0362C" }}>⚠ STOK HABIS</span>
                              ) : stockOk ? (
                                <span className="text-xs px-2 py-0.5 rounded-full font-medium" style={{ background: "#D9F3EA", color: "#117A68" }}>Stok: {item.stock}</span>
                              ) : (
                                <span className="text-xs px-2 py-0.5 rounded-full font-medium" style={{ background: "#FFF0D5", color: "#A66700" }}>Stok: {item.stock} (kurang)</span>
                              )}
                            </div>
                          </div>
                        </div>
                      </div>
                    );
                  })}

                  {/* Total */}
                  <div className="rounded-xl p-4 shadow-sm" style={{ background: "#F7FBFC", border: "1px solid #D6E8EE" }}>
                    <div className="flex items-center justify-between">
                      <div>
                        <p className="text-xs" style={{ color: "#66869A" }}>Total Item: <strong style={{ color: "#315E6E" }}>{selectedResep.items.reduce((a, b) => a + b.qty, 0)} pcs</strong></p>
                        <p className="text-xs mt-0.5" style={{ color: "#66869A" }}>
                          Ditanggung: {selectedResep.items.filter((i) => i.covered).length} dari {selectedResep.items.length} obat
                        </p>
                      </div>
                      {selectedResep.items.some((i) => i.stock === 0) && (
                        <p className="text-xs font-semibold" style={{ color: "#C0362C" }}>⚠ Beberapa obat stok habis — koordinasi dengan gudang</p>
                      )}
                    </div>
                  </div>
                </div>
              </div>
            </div>
          )}

          {/* ════ STOK OBAT ════ */}
          {view === "stok" && (
            <div className="p-6 space-y-5">
              <div>
                <h1 className="text-xl font-bold" style={{ color: "#164E63" }}>Manajemen Stok Obat</h1>
                <p className="text-xs mt-0.5" style={{ color: "#66869A" }}>Monitor ketersediaan dan kondisi stok obat instalasi farmasi</p>
              </div>

              {/* Summary */}
              <div className="grid grid-cols-3 gap-4">
                {[
                  { label: "Total Jenis Obat", val: String(stocks.length), icon: "💊", accent: "#0D6E6E", bg: "#EAF6F8" },
                  { label: "Stok Menipis",     val: String(stocks.filter((s) => s.status === "menipis").length), icon: "⚠", accent: "#A66700", bg: "#FFF0D5" },
                  { label: "Stok Habis",       val: String(outOfStock), icon: "🚫", accent: "#C0362C", bg: "#FDE7E7" },
                ].map((k) => (
                  <div key={k.label} className="rounded-xl p-4 flex items-center gap-4 shadow-sm" style={{ background: "#fff", border: "1px solid #D6E8EE" }}>
                    <span className="w-10 h-10 rounded-lg flex items-center justify-center text-xl" style={{ background: k.bg }}>{k.icon}</span>
                    <div>
                      <p className="text-2xl font-bold" style={{ fontFamily: "var(--font-mono)", color: k.accent }}>{k.val}</p>
                      <p className="text-xs" style={{ color: "#66869A" }}>{k.label}</p>
                    </div>
                  </div>
                ))}
              </div>

              {/* Filters */}
              <div className="flex gap-3">
                <div className="relative flex-1" style={{ maxWidth: 320 }}>
                  <span className="absolute left-3 top-1/2 -translate-y-1/2" style={{ color: "#8CA5B0", fontSize: 13 }}>🔍</span>
                  <input className="w-full border rounded-lg pl-9 pr-4 py-2 text-sm outline-none"
                    style={{ borderColor: "#D6E8EE", background: "#fff", color: "#315E6E" }}
                    placeholder="Cari nama obat atau generik..."
                    value={stokSearch} onChange={(e) => setStokSearch(e.target.value)} />
                </div>
                <div className="flex gap-1.5">
                  {["Semua", "aman", "menipis", "habis"].map((s) => (
                    <button key={s} onClick={() => setStokFilter(s)}
                      className="text-xs px-3 py-2 rounded-lg border font-medium"
                      style={{ background: stokFilter === s ? "#0D6E6E" : "#fff", color: stokFilter === s ? "#fff" : "#66869A", borderColor: stokFilter === s ? "#0D6E6E" : "#D6E8EE" }}>
                      {s === "Semua" ? "Semua" : STOK_STYLE[s as StokStatus].label}
                    </button>
                  ))}
                </div>
              </div>

              {/* Stok table */}
              <div className="rounded-xl shadow-sm overflow-hidden" style={{ background: "#fff", border: "1px solid #D6E8EE" }}>
                <table className="w-full text-sm">
                  <thead>
                    <tr style={{ background: "#F7FBFC", borderBottom: "1px solid #D6E8EE" }}>
                      {["NAMA OBAT", "GENERIK", "BENTUK", "STOK", "MIN.", "LOKASI", "KEDALUWARSA", "PEMASOK", "STATUS"].map((h) => (
                        <th key={h} className="text-left text-xs font-semibold px-4 py-3" style={{ color: "#66869A" }}>{h}</th>
                      ))}
                    </tr>
                  </thead>
                  <tbody className="divide-y" style={{ borderColor: "#EEF6F8" }}>
                    {filteredStock.map((s) => {
                      const ss = STOK_STYLE[s.status];
                      const pct = Math.min(100, Math.round((s.stock / (s.minStock * 3)) * 100));
                      return (
                        <tr key={s.id} className="hover:bg-slate-50 transition-colors" style={{ background: s.status === "habis" ? "#fff5f5" : "transparent" }}>
                          <td className="px-4 py-3">
                            <p className="text-sm font-semibold" style={{ color: "#164E63" }}>{s.name}</p>
                          </td>
                          <td className="px-4 py-3 text-xs" style={{ color: "#66869A" }}>{s.generic}</td>
                          <td className="px-4 py-3">
                            <span className="text-xs px-1.5 py-0.5 rounded font-medium" style={{ background: "#EEF6F8", color: "#456D7D" }}>{s.form}</span>
                          </td>
                          <td className="px-4 py-3">
                            <p className="text-sm font-bold" style={{ fontFamily: "var(--font-mono)", color: s.status === "habis" ? "#C0362C" : s.status === "menipis" ? "#A66700" : "#164E63" }}>
                              {s.stock}
                            </p>
                            <div className="h-1 w-16 rounded-full overflow-hidden mt-1" style={{ background: "#D6E8EE" }}>
                              <div className="h-full rounded-full" style={{ width: `${pct}%`, background: s.status === "habis" ? "#C0362C" : s.status === "menipis" ? "#A66700" : "#117A68" }} />
                            </div>
                          </td>
                          <td className="px-4 py-3 text-xs" style={{ fontFamily: "var(--font-mono)", color: "#66869A" }}>{s.minStock}</td>
                          <td className="px-4 py-3 text-xs" style={{ color: "#66869A" }}>{s.location}</td>
                          <td className="px-4 py-3 text-xs" style={{ fontFamily: "var(--font-mono)", color: "#66869A" }}>{s.expiry}</td>
                          <td className="px-4 py-3 text-xs" style={{ color: "#66869A" }}>{s.supplier}</td>
                          <td className="px-4 py-3">
                            <span className="text-xs px-2 py-0.5 rounded-full font-medium" style={{ background: ss.bg, color: ss.color }}>{ss.label}</span>
                          </td>
                        </tr>
                      );
                    })}
                    {filteredStock.length === 0 && (
                      <tr><td colSpan={9} className="px-4 py-10 text-center text-sm" style={{ color: "#8CA5B0" }}>Tidak ada data stok</td></tr>
                    )}
                  </tbody>
                </table>
              </div>
            </div>
          )}

          {/* ════ RIWAYAT ════ */}
          {view === "riwayat" && (
            <div className="p-6 space-y-5">
              <div>
                <h1 className="text-xl font-bold" style={{ color: "#164E63" }}>Riwayat Dispensing</h1>
                <p className="text-xs mt-0.5" style={{ color: "#66869A" }}>Catatan penyerahan obat ke pasien</p>
              </div>

              <div className="flex gap-3 items-center justify-between">
                <div className="relative" style={{ minWidth: 280 }}>
                  <span className="absolute left-3 top-1/2 -translate-y-1/2" style={{ color: "#8CA5B0", fontSize: 13 }}>🔍</span>
                  <input className="w-full border rounded-lg pl-9 pr-4 py-2 text-sm outline-none"
                    style={{ borderColor: "#D6E8EE", background: "#fff", color: "#315E6E" }}
                    placeholder="Cari nama pasien atau No. Resep..."
                    value={histSearch} onChange={(e) => setHistSearch(e.target.value)} />
                </div>
                <div className="flex items-center gap-3">
                  <span className="text-sm font-semibold" style={{ color: "#164E63" }}>
                    Total Hari Ini: <span style={{ fontFamily: "var(--font-mono)", color: "#0D6E6E" }}>
                      Rp {fmt(filteredHistory.reduce((a, b) => a + b.total, 0))}
                    </span>
                  </span>
                </div>
              </div>

              <div className="rounded-xl shadow-sm overflow-hidden" style={{ background: "#fff", border: "1px solid #D6E8EE" }}>
                <table className="w-full text-sm">
                  <thead>
                    <tr style={{ background: "#F7FBFC", borderBottom: "1px solid #D6E8EE" }}>
                      {["NO. RESEP", "PASIEN", "OBAT YANG DISERAHKAN", "PENJAMIN", "WAKTU", "APOTEKER", "TOTAL"].map((h) => (
                        <th key={h} className="text-left text-xs font-semibold px-4 py-3" style={{ color: "#66869A" }}>{h}</th>
                      ))}
                    </tr>
                  </thead>
                  <tbody className="divide-y" style={{ borderColor: "#EEF6F8" }}>
                    {filteredHistory.map((h) => (
                      <tr key={h.id} className="hover:bg-slate-50 transition-colors">
                        <td className="px-4 py-3 text-xs" style={{ fontFamily: "var(--font-mono)", color: "#66869A" }}>{h.resepNo}</td>
                        <td className="px-4 py-3">
                          <p className="text-sm font-semibold" style={{ color: "#164E63" }}>{h.patientName}</p>
                          <p className="text-xs" style={{ fontFamily: "var(--font-mono)", color: "#8CA5B0" }}>{h.patientRm}</p>
                        </td>
                        <td className="px-4 py-3">
                          <p className="text-xs" style={{ color: "#315E6E", maxWidth: 280 }}>{h.drug}</p>
                          <p className="text-xs mt-0.5" style={{ fontFamily: "var(--font-mono)", color: "#8CA5B0" }}>{h.qty} {h.unit}</p>
                        </td>
                        <td className="px-4 py-3 text-xs" style={{ color: "#66869A" }}>{h.insurance}</td>
                        <td className="px-4 py-3 text-xs" style={{ fontFamily: "var(--font-mono)", color: "#66869A" }}>{h.dispensedAt}</td>
                        <td className="px-4 py-3 text-xs" style={{ color: "#66869A" }}>{h.dispensedBy}</td>
                        <td className="px-4 py-3 text-sm font-bold" style={{ fontFamily: "var(--font-mono)", color: "#0D6E6E" }}>
                          Rp {fmt(h.total)}
                        </td>
                      </tr>
                    ))}
                    {filteredHistory.length === 0 && (
                      <tr><td colSpan={7} className="px-4 py-10 text-center text-sm" style={{ color: "#8CA5B0" }}>Tidak ada data riwayat</td></tr>
                    )}
                  </tbody>
                </table>
              </div>
            </div>
          )}
        </main>
      </div>

      {/* Reject modal */}
      {rejectModal && (
        <div className="fixed inset-0 z-50 flex items-center justify-center" style={{ background: "rgba(0,0,0,0.4)" }}>
          <div className="rounded-2xl p-6 w-[420px] shadow-2xl" style={{ background: "#fff" }}>
            <p className="text-lg font-bold mb-1" style={{ color: "#C0362C" }}>Tolak Resep</p>
            <p className="text-sm mb-4" style={{ color: "#66869A" }}>
              Resep <strong>{resepList.find((r) => r.id === rejectModal)?.no}</strong> — {resepList.find((r) => r.id === rejectModal)?.patientName}
            </p>
            <label className="text-xs font-semibold block mb-1.5" style={{ color: "#315E6E" }}>Alasan Penolakan *</label>
            <textarea rows={3} className="w-full border rounded-lg px-3 py-2.5 text-sm outline-none resize-none mb-4"
              style={{ borderColor: rejectReason ? "#D6E8EE" : "#fca5a5", color: "#315E6E" }}
              placeholder="Mis. Obat tidak tersedia, dosis tidak sesuai, resep tidak lengkap..."
              value={rejectReason} onChange={(e) => setRejectReason(e.target.value)} />
            <div className="flex gap-3">
              <button onClick={() => { setRejectModal(null); setRejectReason(""); }}
                className="flex-1 py-2.5 rounded-lg border text-sm font-semibold"
                style={{ borderColor: "#D6E8EE", color: "#315E6E" }}>Batal</button>
              <button onClick={() => rejectResep(rejectModal!)}
                className="flex-1 py-2.5 rounded-lg text-sm font-semibold text-white"
                style={{ background: "#C0362C" }}>Konfirmasi Penolakan</button>
            </div>
          </div>
        </div>
      )}

      {/* Dispense confirm modal */}
      {dispenseConfirm && (
        <div className="fixed inset-0 z-50 flex items-center justify-center" style={{ background: "rgba(0,0,0,0.4)" }}>
          <div className="rounded-2xl p-6 w-[420px] shadow-2xl" style={{ background: "#fff" }}>
            <p className="text-lg font-bold mb-1" style={{ color: "#117A68" }}>✅ Konfirmasi Penyerahan</p>
            <p className="text-sm mb-2" style={{ color: "#315E6E" }}>
              Obat untuk <strong>{resepList.find((r) => r.id === dispenseConfirm)?.patientName}</strong> siap diserahkan.
            </p>
            <p className="text-xs mb-5" style={{ color: "#66869A" }}>
              Pastikan identitas pasien sudah diverifikasi dan pasien/keluarga sudah menerima penjelasan penggunaan obat (KIE) sebelum menekan tombol konfirmasi.
            </p>
            <div className="flex gap-3">
              <button onClick={() => setDispenseConfirm(null)}
                className="flex-1 py-2.5 rounded-lg border text-sm font-semibold"
                style={{ borderColor: "#D6E8EE", color: "#315E6E" }}>Periksa Ulang</button>
              <button onClick={() => advanceResepStatus(dispenseConfirm!, "selesai")}
                className="flex-1 py-2.5 rounded-lg text-sm font-semibold text-white"
                style={{ background: "#117A68" }}>Obat Telah Diserahkan</button>
            </div>
          </div>
        </div>
      )}

      {/* Toast */}
      {toast && (
        <div className="fixed bottom-5 right-5 px-4 py-3 rounded-lg shadow-lg text-sm font-medium text-white z-50"
          style={{ background: "#0D6E6E" }}>{toast}</div>
      )}
    </div>
  );
}
