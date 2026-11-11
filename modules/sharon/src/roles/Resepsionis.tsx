import { useState } from "react";
import type { UserInfo } from "../types/user";
import logoSrc from "../assets/logo.png";

interface ResepsionisProps {
  user: UserInfo;
  onLogout: () => void;
}

type ResView = "dashboard" | "registrasi" | "antrian" | "kamar";
type VisitType = "Rawat Jalan" | "Rawat Inap" | "IGD";
type QueueStatus = "menunggu" | "dipanggil" | "dilayani" | "selesai" | "batal";
type BedStatus = "tersedia" | "terisi" | "maintenance";
type BedClass = "VIP" | "Kelas I" | "Kelas II" | "Kelas III" | "ICU";

interface Patient {
  id: string;
  rm: string;
  nik: string;
  name: string;
  dob: string;
  age: number;
  gender: "L" | "P";
  address: string;
  phone: string;
  insurance: string;
  memberNo: string;
  registeredAt: string;
  isNew: boolean;
}

interface QueueItem {
  id: string;
  no: number;
  rm: string;
  name: string;
  age: number;
  gender: "L" | "P";
  visitType: VisitType;
  poli: string;
  insurance: string;
  status: QueueStatus;
  registeredAt: string;
  calledAt?: string;
}

interface Bed {
  id: string;
  room: string;
  class: BedClass;
  status: BedStatus;
  patientName?: string;
  patientRm?: string;
  admitDate?: string;
  days?: number;
  gender?: "L" | "P" | "Mixed";
}

// ── Seed data ────────────────────────────────────────────────────────────────

const SEED_PATIENTS: Patient[] = [
  { id: "1", rm: "RM-2026-000201", nik: "3271010101880001", name: "Rina Wulandari", dob: "01/01/1985", age: 41, gender: "P", address: "Jl. Mawar No. 5, Bandung", phone: "081234567890", insurance: "BPJS", memberNo: "0009876543210", registeredAt: "07/09/2026 08:30", isNew: false },
  { id: "2", rm: "RM-2026-000202", nik: "3271020202700002", name: "Hendra Gunawan", dob: "02/02/1971", age: 55, gender: "L", address: "Jl. Melati No. 12, Bandung", phone: "081298765432", insurance: "Mandiri", memberNo: "", registeredAt: "07/09/2026 09:05", isNew: false },
  { id: "3", rm: "RM-2026-000210", nik: "3271030303990003", name: "Putri Ayu Lestari", dob: "03/03/1999", age: 27, gender: "P", address: "Jl. Anggrek No. 7, Cimahi", phone: "085678901234", insurance: "BPJS", memberNo: "0001122334455", registeredAt: "07/09/2026 09:45", isNew: true },
];

const SEED_QUEUE: QueueItem[] = [
  { id: "q1", no: 1, rm: "RM-2026-000201", name: "Rina Wulandari", age: 41, gender: "P", visitType: "Rawat Jalan", poli: "Jantung", insurance: "BPJS", status: "dilayani", registeredAt: "08:30", calledAt: "09:10" },
  { id: "q2", no: 2, rm: "RM-2026-000202", name: "Hendra Gunawan", age: 55, gender: "L", visitType: "Rawat Jalan", poli: "Umum", insurance: "Mandiri", status: "dipanggil", registeredAt: "09:05", calledAt: "09:40" },
  { id: "q3", no: 3, rm: "RM-2026-000210", name: "Putri Ayu Lestari", age: 27, gender: "P", visitType: "Rawat Jalan", poli: "Gigi", insurance: "BPJS", status: "menunggu", registeredAt: "09:45" },
  { id: "q4", no: 4, rm: "RM-2026-000215", name: "Doni Kusuma", age: 44, gender: "L", visitType: "Rawat Jalan", poli: "Mata", insurance: "Asuransi Swasta", status: "menunggu", registeredAt: "10:02" },
  { id: "q5", no: 5, rm: "RM-2026-000216", name: "Sri Lestari", age: 61, gender: "P", visitType: "Rawat Inap", poli: "Penyakit Dalam", insurance: "BPJS", status: "menunggu", registeredAt: "10:15" },
  { id: "q6", no: 6, rm: "RM-2026-000217", name: "Yudi Prasetyo", age: 32, gender: "L", visitType: "IGD", poli: "IGD", insurance: "Mandiri", status: "selesai", registeredAt: "07:55", calledAt: "08:00" },
];

const SEED_BEDS: Bed[] = [
  { id: "VIP-01", room: "VIP-01", class: "VIP", status: "terisi", patientName: "Herman Suryadi", patientRm: "RM-2026-000219", admitDate: "16/07/2026", days: 45, gender: "L" },
  { id: "VIP-02", room: "VIP-02", class: "VIP", status: "tersedia", gender: "Mixed" },
  { id: "VIP-03", room: "VIP-03", class: "VIP", status: "tersedia", gender: "Mixed" },
  { id: "I-01",   room: "Kelas I-01", class: "Kelas I", status: "terisi", patientName: "Siti Nurhaliza", patientRm: "RM-2026-000220", admitDate: "20/08/2026", days: 18, gender: "P" },
  { id: "I-02",   room: "Kelas I-02", class: "Kelas I", status: "tersedia", gender: "L" },
  { id: "I-03",   room: "Kelas I-03", class: "Kelas I", status: "tersedia", gender: "P" },
  { id: "I-04",   room: "Kelas I-04", class: "Kelas I", status: "terisi", patientName: "Bambang Irawan", patientRm: "RM-2026-000221", admitDate: "01/09/2026", days: 6, gender: "L" },
  { id: "II-01",  room: "Kelas II-01", class: "Kelas II", status: "tersedia", gender: "Mixed" },
  { id: "II-02",  room: "Kelas II-02", class: "Kelas II", status: "tersedia", gender: "Mixed" },
  { id: "II-03",  room: "Kelas II-03", class: "Kelas II", status: "maintenance" },
  { id: "II-04",  room: "Kelas II-04", class: "Kelas II", status: "terisi", patientName: "Wati Susanti", patientRm: "RM-2026-000222", admitDate: "05/09/2026", days: 2, gender: "P" },
  { id: "III-01", room: "Kelas III-01", class: "Kelas III", status: "tersedia", gender: "L" },
  { id: "III-02", room: "Kelas III-02", class: "Kelas III", status: "terisi", patientName: "Joko Widodo", patientRm: "RM-2026-000223", admitDate: "04/09/2026", days: 3, gender: "L" },
  { id: "III-03", room: "Kelas III-03", class: "Kelas III", status: "tersedia", gender: "P" },
  { id: "III-04", room: "Kelas III-04", class: "Kelas III", status: "tersedia", gender: "Mixed" },
  { id: "ICU-01", room: "ICU-01", class: "ICU", status: "terisi", patientName: "Ahmad Rizal", patientRm: "RM-2026-000224", admitDate: "06/09/2026", days: 1, gender: "L" },
  { id: "ICU-02", room: "ICU-02", class: "ICU", status: "tersedia" },
  { id: "ICU-03", room: "ICU-03", class: "ICU", status: "tersedia" },
];

// ── Style maps ────────────────────────────────────────────────────────────────

const Q_STYLE: Record<QueueStatus, { bg: string; color: string; label: string; dot: string }> = {
  menunggu:  { bg: "#FFF0D5", color: "#A66700", label: "Menunggu",  dot: "#A66700" },
  dipanggil: { bg: "#E1EEFB", color: "#1D5FA8", label: "Dipanggil", dot: "#1D5FA8" },
  dilayani:  { bg: "#EAF6F8", color: "#0D6E6E", label: "Dilayani",  dot: "#0D6E6E" },
  selesai:   { bg: "#D9F3EA", color: "#117A68", label: "Selesai",   dot: "#117A68" },
  batal:     { bg: "#FDE7E7", color: "#C0362C", label: "Dibatalkan",dot: "#C0362C" },
};

const CLASS_COLOR: Record<BedClass, { bg: string; color: string }> = {
  "VIP":      { bg: "#FFF0D5", color: "#A66700" },
  "Kelas I":  { bg: "#E1EEFB", color: "#1D5FA8" },
  "Kelas II": { bg: "#EAF6F8", color: "#0D6E6E" },
  "Kelas III":{ bg: "#EEF6F8", color: "#456D7D" },
  "ICU":      { bg: "#FDE7E7", color: "#C0362C" },
};

const POLI_LIST = ["Umum", "Gigi", "Mata", "Jantung", "Paru", "Penyakit Dalam", "Bedah", "Saraf", "Ortopedi", "Kebidanan", "Anak", "IGD"];
const INSURANCE_LIST = ["Mandiri / Umum", "BPJS", "Asuransi Swasta", "Jaminan Perusahaan"];

const SIDEBAR: { key: ResView; label: string; icon: string }[] = [
  { key: "dashboard",  label: "Dashboard",            icon: "📊" },
  { key: "registrasi", label: "Registrasi Pasien",    icon: "📝" },
  { key: "antrian",    label: "Manajemen Antrean",    icon: "🔢" },
  { key: "kamar",      label: "Ketersediaan Kamar",   icon: "🛏" },
];

// ── Helpers ───────────────────────────────────────────────────────────────────

function genRM() {
  return "RM-2026-" + String(Math.floor(Math.random() * 900) + 300).padStart(6, "0");
}
function nowTime() {
  return new Date().toLocaleTimeString("id-ID", { hour: "2-digit", minute: "2-digit" });
}

// ── Component ─────────────────────────────────────────────────────────────────

export default function Resepsionis({ user, onLogout }: ResepsionisProps) {
  const [view, setView] = useState<ResView>("dashboard");
  const [collapsed, setCollapsed] = useState(false);
  const [notif] = useState(2);

  const [patients, setPatients]   = useState<Patient[]>(SEED_PATIENTS);
  const [queue, setQueue]         = useState<QueueItem[]>(SEED_QUEUE);
  const [beds]                    = useState<Bed[]>(SEED_BEDS);

  const [toast, setToast]         = useState<string | null>(null);
  const [confirmId, setConfirmId] = useState<string | null>(null);

  // Registrasi form state
  const [isNewPatient, setIsNewPatient]   = useState(true);
  const [searchRM, setSearchRM]           = useState("");
  const [foundPatient, setFoundPatient]   = useState<Patient | null>(null);
  const [regStep, setRegStep]             = useState<1 | 2>(1);
  const [regErrors, setRegErrors]         = useState<Record<string, string>>({});

  const [regForm, setRegForm] = useState({
    nik: "", name: "", dob: "", gender: "L" as "L" | "P",
    address: "", phone: "", insurance: "Mandiri / Umum", memberNo: "",
  });
  const [visitForm, setVisitForm] = useState({
    visitType: "Rawat Jalan" as VisitType,
    poli: "Umum",
    doctor: "",
    complaint: "",
    notes: "",
  });

  // Antrian filters
  const [qFilter, setQFilter] = useState("Semua");
  const [qSearch, setQSearch] = useState("");

  // Kamar filter
  const [bedFilter, setBedFilter] = useState("Semua");

  function showToast(msg: string) {
    setToast(msg);
    setTimeout(() => setToast(null), 2500);
  }

  // ── Queue actions ────────────────────────────────────────────────────────
  function callNext() {
    const nextWaiting = queue.find((q) => q.status === "menunggu");
    if (!nextWaiting) { showToast("Tidak ada pasien yang menunggu"); return; }
    setQueue((prev) => prev.map((q) => q.id === nextWaiting.id ? { ...q, status: "dipanggil", calledAt: nowTime() } : q));
    showToast(`Memanggil ${nextWaiting.name} — No. ${nextWaiting.no}`);
  }

  function updateQStatus(id: string, status: QueueStatus) {
    setQueue((prev) => prev.map((q) => q.id === id ? { ...q, status, calledAt: status === "dipanggil" ? nowTime() : q.calledAt } : q));
    showToast(status === "selesai" ? "Kunjungan selesai dicatat" : status === "batal" ? "Kunjungan dibatalkan" : `Status diperbarui`);
    setConfirmId(null);
  }

  // ── Registration ─────────────────────────────────────────────────────────
  function searchExistingPatient() {
    const found = patients.find((p) => p.rm === searchRM.trim() || p.nik === searchRM.trim() || p.name.toLowerCase() === searchRM.trim().toLowerCase());
    if (found) { setFoundPatient(found); setRegStep(2); }
    else showToast("Pasien tidak ditemukan. Gunakan Registrasi Baru.");
  }

  function validateRegStep1() {
    const e: Record<string, string> = {};
    if (!regForm.nik || regForm.nik.length !== 16) e.nik = "NIK harus 16 digit";
    if (!regForm.name.trim()) e.name = "Nama lengkap wajib diisi";
    if (!regForm.dob) e.dob = "Tanggal lahir wajib diisi";
    if (!regForm.phone.match(/^(08|\+628)\d{8,11}$/)) e.phone = "Format nomor HP tidak valid";
    if ((regForm.insurance !== "Mandiri / Umum") && !regForm.memberNo) e.memberNo = "No. kepesertaan wajib diisi";
    return e;
  }

  function validateVisitForm() {
    const e: Record<string, string> = {};
    if (!visitForm.complaint.trim()) e.complaint = "Keluhan utama wajib diisi";
    return e;
  }

  function submitRegistration() {
    const ev = validateVisitForm();
    if (Object.keys(ev).length) { setRegErrors(ev); return; }

    let patient = foundPatient;
    if (!patient) {
      const rm = genRM();
      patient = {
        id: String(Date.now()), rm, nik: regForm.nik, name: regForm.name, dob: regForm.dob,
        age: new Date().getFullYear() - Number(regForm.dob.split("/")[2] || regForm.dob.slice(0, 4)),
        gender: regForm.gender, address: regForm.address, phone: regForm.phone,
        insurance: regForm.insurance, memberNo: regForm.memberNo,
        registeredAt: new Date().toLocaleDateString("id-ID") + " " + nowTime(), isNew: true,
      };
      setPatients((prev) => [...prev, patient!]);
    }

    const nextNo = Math.max(...queue.map((q) => q.no), 0) + 1;
    const newQ: QueueItem = {
      id: String(Date.now()), no: nextNo, rm: patient.rm, name: patient.name,
      age: patient.age, gender: patient.gender, visitType: visitForm.visitType,
      poli: visitForm.visitType === "IGD" ? "IGD" : visitForm.poli,
      insurance: patient.insurance, status: "menunggu", registeredAt: nowTime(),
    };
    setQueue((prev) => [...prev, newQ]);

    showToast(`Registrasi berhasil! ${patient.name} — No. Antrean ${nextNo}`);

    // Reset form
    setRegForm({ nik: "", name: "", dob: "", gender: "L", address: "", phone: "", insurance: "Mandiri / Umum", memberNo: "" });
    setVisitForm({ visitType: "Rawat Jalan", poli: "Umum", doctor: "", complaint: "", notes: "" });
    setFoundPatient(null); setSearchRM(""); setIsNewPatient(true); setRegStep(1); setRegErrors({});
    setView("antrian");
  }

  // ── Derived stats ────────────────────────────────────────────────────────
  const waiting   = queue.filter((q) => q.status === "menunggu").length;
  const called    = queue.filter((q) => q.status === "dipanggil").length;
  const serving   = queue.filter((q) => q.status === "dilayani").length;
  const done      = queue.filter((q) => q.status === "selesai").length;
  const totalToday = queue.length;

  const availBeds = beds.filter((b) => b.status === "tersedia").length;
  const occupBeds = beds.filter((b) => b.status === "terisi").length;

  const bedSummary = (["VIP", "Kelas I", "Kelas II", "Kelas III", "ICU"] as BedClass[]).map((c) => ({
    class: c,
    total: beds.filter((b) => b.class === c).length,
    avail: beds.filter((b) => b.class === c && b.status === "tersedia").length,
  }));

  const filteredQueue = queue.filter((q) => {
    const ms = qFilter === "Semua" || q.status === qFilter;
    const mq = !qSearch || q.name.toLowerCase().includes(qSearch.toLowerCase()) || q.rm.includes(qSearch) || String(q.no) === qSearch;
    return ms && mq;
  });

  const filteredBeds = bedFilter === "Semua" ? beds : beds.filter((b) => b.class === bedFilter);

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
          <span className="hidden md:inline text-white/40 text-xs">— Resepsionis / Admisi</span>
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
              <p className="text-white/60 text-xs leading-tight">Resepsionis — {user.unit}</p>
            </div>
          </div>
          <button onClick={onLogout} className="text-xs px-2.5 py-1 rounded font-medium"
            style={{ background: "#C0362C", color: "white" }}>Keluar</button>
        </div>
      </header>

      <div className="flex flex-1 overflow-hidden">
        {/* Sidebar */}
        <aside className="flex flex-col shrink-0 border-r transition-all duration-200"
          style={{ width: collapsed ? 52 : 216, background: "#fff", borderColor: "#D6E8EE" }}>
          {!collapsed && (
            <div className="px-3 pt-4 pb-2">
              <p className="text-xs font-semibold uppercase tracking-widest" style={{ color: "#8CA5B0" }}>Menu Admisi</p>
            </div>
          )}
          <nav className="flex-1 p-2 space-y-0.5">
            {SIDEBAR.map((item) => {
              const active = view === item.key;
              return (
                <button key={item.key} onClick={() => setView(item.key)}
                  className="w-full flex items-center gap-2.5 rounded-lg px-2.5 py-2.5 text-left transition-all"
                  style={{ background: active ? "#EAF6F8" : "transparent", color: active ? "#0D6E6E" : "#456D7D" }}>
                  <span className="text-base shrink-0">{item.icon}</span>
                  {!collapsed && <span className="text-xs font-semibold">{item.label}</span>}
                  {active && !collapsed && <span className="ml-auto w-1.5 h-1.5 rounded-full" style={{ background: "#0D6E6E" }} />}
                </button>
              );
            })}
          </nav>
          {!collapsed && (
            <div className="p-3 border-t mx-2 mb-2 rounded-lg" style={{ borderColor: "#D6E8EE", background: "#F7FBFC" }}>
              <p className="text-xs font-semibold" style={{ color: "#315E6E" }}>{user.fullName}</p>
              <p className="text-xs mt-0.5" style={{ color: "#8CA5B0" }}>Admisi · {user.unit}</p>
              <p className="text-xs mt-1" style={{ fontFamily: "var(--font-mono)", color: "#8CA5B0", fontSize: "10px" }}>
                {new Date().toLocaleDateString("id-ID", { day: "2-digit", month: "short", year: "numeric" })}
              </p>
            </div>
          )}
        </aside>

        {/* Main */}
        <main className="flex-1 overflow-y-auto" style={{ background: "#F3FAFC" }}>

          {/* ════ DASHBOARD ════ */}
          {view === "dashboard" && (
            <div className="p-6 space-y-5">
              <div>
                <h1 className="text-xl font-bold" style={{ color: "#164E63" }}>Dashboard Admisi</h1>
                <p className="text-xs mt-0.5" style={{ color: "#66869A" }}>
                  {new Date().toLocaleDateString("id-ID", { weekday: "long", day: "2-digit", month: "long", year: "numeric" })} · Shift Pagi
                </p>
              </div>

              {/* KPI row */}
              <div className="grid grid-cols-2 lg:grid-cols-4 gap-4">
                {[
                  { label: "Total Kunjungan Hari Ini", val: String(totalToday), sub: "pasien terdaftar", icon: "👥", accent: "#0D6E6E", bg: "#EAF6F8" },
                  { label: "Menunggu Dipanggil",        val: String(waiting),    sub: "dalam antrean",  icon: "⏳", accent: "#A66700", bg: "#FFF0D5" },
                  { label: "Sedang Dilayani",           val: String(serving + called), sub: "di poli / IGD", icon: "🩺", accent: "#1D5FA8", bg: "#E1EEFB" },
                  { label: "Kunjungan Selesai",         val: String(done),       sub: "hari ini",       icon: "✅", accent: "#117A68", bg: "#D9F3EA" },
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
                {/* Live queue */}
                <div className="col-span-2 rounded-xl shadow-sm overflow-hidden" style={{ background: "#fff", border: "1px solid #D6E8EE" }}>
                  <div className="flex items-center justify-between px-5 py-4 border-b" style={{ borderColor: "#D6E8EE" }}>
                    <h2 className="text-sm font-semibold" style={{ color: "#164E63" }}>Antrean Aktif</h2>
                    <div className="flex gap-2">
                      <button onClick={callNext}
                        className="text-xs px-3 py-1.5 rounded-lg font-semibold text-white"
                        style={{ background: "#0D6E6E" }}>
                        📢 Panggil Berikutnya
                      </button>
                      <button onClick={() => setView("registrasi")}
                        className="text-xs px-3 py-1.5 rounded-lg font-semibold border"
                        style={{ borderColor: "#0D6E6E", color: "#0D6E6E" }}>
                        + Daftarkan
                      </button>
                    </div>
                  </div>
                  <div className="divide-y" style={{ borderColor: "#EEF6F8" }}>
                    {queue.filter((q) => q.status !== "selesai" && q.status !== "batal").slice(0, 6).map((q) => {
                      const s = Q_STYLE[q.status];
                      return (
                        <div key={q.id} className="flex items-center gap-4 px-5 py-3 hover:bg-slate-50 transition-colors">
                          <span className="w-9 h-9 rounded-full flex items-center justify-center font-bold text-sm text-white shrink-0"
                            style={{ background: "#0D6E6E" }}>{q.no}</span>
                          <div className="flex-1 min-w-0">
                            <p className="text-sm font-semibold truncate" style={{ color: "#164E63" }}>{q.name}</p>
                            <p className="text-xs" style={{ color: "#66869A" }}>{q.visitType} · {q.poli} · {q.insurance}</p>
                          </div>
                          <span className="flex items-center gap-1 text-xs font-medium shrink-0">
                            <span className="w-1.5 h-1.5 rounded-full" style={{ background: s.dot }} />
                            <span style={{ color: s.color }}>{s.label}</span>
                          </span>
                          {q.status === "menunggu" && (
                            <button onClick={() => updateQStatus(q.id, "dipanggil")}
                              className="text-xs px-2.5 py-1 rounded-lg font-medium text-white shrink-0"
                              style={{ background: "#A66700" }}>Panggil</button>
                          )}
                          {q.status === "dipanggil" && (
                            <button onClick={() => updateQStatus(q.id, "selesai")}
                              className="text-xs px-2.5 py-1 rounded-lg font-medium text-white shrink-0"
                              style={{ background: "#117A68" }}>Selesai</button>
                          )}
                        </div>
                      );
                    })}
                    {queue.filter((q) => q.status !== "selesai" && q.status !== "batal").length === 0 && (
                      <div className="py-8 text-center text-sm" style={{ color: "#8CA5B0" }}>
                        ✓ Tidak ada antrean aktif
                      </div>
                    )}
                  </div>
                </div>

                {/* Bed availability */}
                <div className="rounded-xl shadow-sm" style={{ background: "#fff", border: "1px solid #D6E8EE" }}>
                  <div className="px-5 py-4 border-b" style={{ borderColor: "#D6E8EE" }}>
                    <h2 className="text-sm font-semibold" style={{ color: "#164E63" }}>Ketersediaan Kamar</h2>
                    <div className="flex items-center gap-3 mt-1.5">
                      <span className="text-xs font-bold" style={{ color: "#117A68" }}>{availBeds} tersedia</span>
                      <span className="text-xs" style={{ color: "#8CA5B0" }}>·</span>
                      <span className="text-xs font-bold" style={{ color: "#C0362C" }}>{occupBeds} terisi</span>
                    </div>
                  </div>
                  <div className="p-4 space-y-3">
                    {bedSummary.map((b) => {
                      const pct = b.total > 0 ? Math.round(((b.total - b.avail) / b.total) * 100) : 0;
                      const cc = CLASS_COLOR[b.class];
                      return (
                        <div key={b.class}>
                          <div className="flex items-center justify-between mb-1">
                            <span className="text-xs font-semibold px-1.5 py-0.5 rounded"
                              style={{ background: cc.bg, color: cc.color }}>{b.class}</span>
                            <span className="text-xs" style={{ color: "#66869A" }}>
                              {b.avail}/{b.total} kosong
                            </span>
                          </div>
                          <div className="h-1.5 rounded-full overflow-hidden" style={{ background: "#D6E8EE" }}>
                            <div className="h-full rounded-full transition-all"
                              style={{ width: `${pct}%`, background: pct >= 90 ? "#C0362C" : pct >= 70 ? "#A66700" : "#0D6E6E" }} />
                          </div>
                        </div>
                      );
                    })}
                  </div>
                  <div className="px-4 pb-4">
                    <button onClick={() => setView("kamar")}
                      className="w-full text-xs py-2 rounded-lg border font-medium"
                      style={{ borderColor: "#D6E8EE", color: "#0D6E6E" }}>
                      Lihat Detail Kamar →
                    </button>
                  </div>
                </div>
              </div>

              {/* Registered today */}
              <div className="rounded-xl shadow-sm overflow-hidden" style={{ background: "#fff", border: "1px solid #D6E8EE" }}>
                <div className="flex items-center justify-between px-5 py-4 border-b" style={{ borderColor: "#D6E8EE" }}>
                  <h2 className="text-sm font-semibold" style={{ color: "#164E63" }}>Pasien Terdaftar Hari Ini</h2>
                  <button onClick={() => setView("registrasi")}
                    className="text-xs px-3 py-1.5 rounded-lg font-semibold text-white" style={{ background: "#0D6E6E" }}>
                    + Registrasi Baru
                  </button>
                </div>
                <table className="w-full text-sm">
                  <thead>
                    <tr style={{ background: "#F7FBFC", borderBottom: "1px solid #D6E8EE" }}>
                      {["NO. RM", "NAMA", "JENIS KELAMIN", "PENJAMIN", "WAKTU DAFTAR", "STATUS PASIEN"].map((h) => (
                        <th key={h} className="text-left text-xs font-semibold px-4 py-3" style={{ color: "#66869A" }}>{h}</th>
                      ))}
                    </tr>
                  </thead>
                  <tbody className="divide-y" style={{ borderColor: "#EEF6F8" }}>
                    {patients.map((p) => (
                      <tr key={p.id} className="hover:bg-slate-50 transition-colors">
                        <td className="px-4 py-3 text-xs" style={{ fontFamily: "var(--font-mono)", color: "#66869A" }}>{p.rm}</td>
                        <td className="px-4 py-3">
                          <p className="text-sm font-semibold" style={{ color: "#164E63" }}>{p.name}</p>
                          <p className="text-xs" style={{ color: "#8CA5B0" }}>{p.age} thn · {p.gender === "L" ? "Laki-laki" : "Perempuan"}</p>
                        </td>
                        <td className="px-4 py-3 text-xs" style={{ color: "#66869A" }}>{p.gender === "L" ? "Laki-laki" : "Perempuan"}</td>
                        <td className="px-4 py-3 text-xs" style={{ color: "#315E6E" }}>{p.insurance}</td>
                        <td className="px-4 py-3 text-xs" style={{ fontFamily: "var(--font-mono)", color: "#66869A" }}>{p.registeredAt}</td>
                        <td className="px-4 py-3">
                          <span className="text-xs px-2 py-0.5 rounded-full font-medium"
                            style={{ background: p.isNew ? "#FFF0D5" : "#D9F3EA", color: p.isNew ? "#A66700" : "#117A68" }}>
                            {p.isNew ? "Pasien Baru" : "Pasien Lama"}
                          </span>
                        </td>
                      </tr>
                    ))}
                  </tbody>
                </table>
              </div>
            </div>
          )}

          {/* ════ REGISTRASI ════ */}
          {view === "registrasi" && (
            <div className="p-6 space-y-5 max-w-3xl">
              <div>
                <h1 className="text-xl font-bold" style={{ color: "#164E63" }}>Registrasi Pasien</h1>
                <p className="text-xs mt-0.5" style={{ color: "#66869A" }}>Daftarkan kunjungan pasien baru maupun yang sudah terdaftar</p>
              </div>

              {/* Toggle new / existing */}
              <div className="flex rounded-xl overflow-hidden border" style={{ borderColor: "#D6E8EE", background: "#fff" }}>
                {[{ val: true, label: "🆕 Pasien Baru" }, { val: false, label: "🔍 Pasien Lama" }].map((opt) => (
                  <button key={String(opt.val)} onClick={() => { setIsNewPatient(opt.val); setRegStep(1); setFoundPatient(null); setRegErrors({}); }}
                    className="flex-1 py-3 text-sm font-semibold transition-colors"
                    style={{ background: isNewPatient === opt.val ? "#0D6E6E" : "transparent", color: isNewPatient === opt.val ? "#fff" : "#66869A" }}>
                    {opt.label}
                  </button>
                ))}
              </div>

              {/* Existing patient search */}
              {!isNewPatient && regStep === 1 && (
                <div className="rounded-xl p-5 shadow-sm" style={{ background: "#fff", border: "1px solid #D6E8EE" }}>
                  <h3 className="text-sm font-semibold mb-3" style={{ color: "#164E63" }}>Cari Pasien</h3>
                  <label className="text-xs font-semibold block mb-1.5" style={{ color: "#315E6E" }}>No. RM / NIK / Nama Lengkap *</label>
                  <div className="flex gap-2">
                    <input className="flex-1 border rounded-lg px-3 py-2.5 text-sm outline-none"
                      style={{ borderColor: "#D6E8EE", color: "#315E6E", fontFamily: "var(--font-mono)" }}
                      placeholder="Masukkan No. RM, NIK, atau nama..."
                      value={searchRM} onChange={(e) => setSearchRM(e.target.value)}
                      onKeyDown={(e) => e.key === "Enter" && searchExistingPatient()} />
                    <button onClick={searchExistingPatient}
                      className="px-5 py-2.5 rounded-lg text-sm font-semibold text-white"
                      style={{ background: "#0D6E6E" }}>Cari</button>
                  </div>
                  <p className="text-xs mt-2" style={{ color: "#8CA5B0" }}>Coba: RM-2026-000201 atau nama "Rina"</p>
                </div>
              )}

              {/* Found existing patient info */}
              {!isNewPatient && foundPatient && regStep === 2 && (
                <div className="rounded-xl p-4 shadow-sm" style={{ background: "#D9F3EA", border: "1px solid #86efac" }}>
                  <div className="flex items-center justify-between">
                    <div>
                      <p className="text-sm font-bold" style={{ color: "#15803d" }}>✓ Pasien Ditemukan</p>
                      <p className="text-sm font-semibold mt-0.5" style={{ color: "#164E63" }}>{foundPatient.name}</p>
                      <p className="text-xs" style={{ fontFamily: "var(--font-mono)", color: "#66869A" }}>
                        {foundPatient.rm} · {foundPatient.age} thn · {foundPatient.gender === "L" ? "Laki-laki" : "Perempuan"} · {foundPatient.insurance}
                      </p>
                    </div>
                    <button onClick={() => { setFoundPatient(null); setRegStep(1); }}
                      className="text-xs px-2 py-1 rounded border" style={{ borderColor: "#86efac", color: "#15803d" }}>
                      Ganti
                    </button>
                  </div>
                </div>
              )}

              {/* New patient data form (Step 1) */}
              {isNewPatient && regStep === 1 && (
                <div className="rounded-xl p-5 shadow-sm" style={{ background: "#fff", border: "1px solid #D6E8EE" }}>
                  <h3 className="text-sm font-semibold mb-4" style={{ color: "#164E63" }}>Data Identitas Pasien</h3>
                  <div className="grid grid-cols-2 gap-4">
                    {/* NIK */}
                    <div className="col-span-2">
                      <label className="text-xs font-semibold block mb-1.5" style={{ color: "#315E6E" }}>NIK (16 digit) *</label>
                      <input className="w-full border rounded-lg px-3 py-2.5 text-sm outline-none"
                        style={{ borderColor: regErrors.nik ? "#C0362C" : "#D6E8EE", color: "#315E6E", fontFamily: "var(--font-mono)" }}
                        placeholder="3271XXXXXXXXXXXX" maxLength={16}
                        value={regForm.nik} onChange={(e) => { setRegForm((f) => ({ ...f, nik: e.target.value })); setRegErrors((er) => ({ ...er, nik: "" })); }} />
                      {regErrors.nik && <p className="text-xs mt-1" style={{ color: "#C0362C" }}>⚠ {regErrors.nik}</p>}
                    </div>
                    {/* Name */}
                    <div className="col-span-2">
                      <label className="text-xs font-semibold block mb-1.5" style={{ color: "#315E6E" }}>Nama Lengkap *</label>
                      <input className="w-full border rounded-lg px-3 py-2.5 text-sm outline-none"
                        style={{ borderColor: regErrors.name ? "#C0362C" : "#D6E8EE", color: "#315E6E" }}
                        placeholder="Nama sesuai KTP..."
                        value={regForm.name} onChange={(e) => { setRegForm((f) => ({ ...f, name: e.target.value })); setRegErrors((er) => ({ ...er, name: "" })); }} />
                      {regErrors.name && <p className="text-xs mt-1" style={{ color: "#C0362C" }}>⚠ {regErrors.name}</p>}
                    </div>
                    {/* DOB */}
                    <div>
                      <label className="text-xs font-semibold block mb-1.5" style={{ color: "#315E6E" }}>Tanggal Lahir *</label>
                      <input type="date" className="w-full border rounded-lg px-3 py-2.5 text-sm outline-none"
                        style={{ borderColor: regErrors.dob ? "#C0362C" : "#D6E8EE", color: "#315E6E" }}
                        max={new Date().toISOString().split("T")[0]}
                        value={regForm.dob} onChange={(e) => { setRegForm((f) => ({ ...f, dob: e.target.value })); setRegErrors((er) => ({ ...er, dob: "" })); }} />
                      {regErrors.dob && <p className="text-xs mt-1" style={{ color: "#C0362C" }}>⚠ {regErrors.dob}</p>}
                    </div>
                    {/* Gender */}
                    <div>
                      <label className="text-xs font-semibold block mb-1.5" style={{ color: "#315E6E" }}>Jenis Kelamin *</label>
                      <div className="flex gap-2 h-[42px]">
                        {(["L", "P"] as const).map((g) => (
                          <button key={g} onClick={() => setRegForm((f) => ({ ...f, gender: g }))}
                            className="flex-1 rounded-lg text-sm font-semibold border-2 transition-all"
                            style={{ background: regForm.gender === g ? "#EAF6F8" : "#F7FBFC", color: regForm.gender === g ? "#0D6E6E" : "#66869A", borderColor: regForm.gender === g ? "#0D6E6E" : "#D6E8EE" }}>
                            {g === "L" ? "Laki-laki" : "Perempuan"}
                          </button>
                        ))}
                      </div>
                    </div>
                    {/* Phone */}
                    <div>
                      <label className="text-xs font-semibold block mb-1.5" style={{ color: "#315E6E" }}>No. HP *</label>
                      <input type="tel" className="w-full border rounded-lg px-3 py-2.5 text-sm outline-none"
                        style={{ borderColor: regErrors.phone ? "#C0362C" : "#D6E8EE", color: "#315E6E", fontFamily: "var(--font-mono)" }}
                        placeholder="08xxxxxxxxxx"
                        value={regForm.phone} onChange={(e) => { setRegForm((f) => ({ ...f, phone: e.target.value })); setRegErrors((er) => ({ ...er, phone: "" })); }} />
                      {regErrors.phone && <p className="text-xs mt-1" style={{ color: "#C0362C" }}>⚠ {regErrors.phone}</p>}
                    </div>
                    {/* Insurance */}
                    <div>
                      <label className="text-xs font-semibold block mb-1.5" style={{ color: "#315E6E" }}>Penjamin *</label>
                      <select className="w-full border rounded-lg px-3 py-2.5 text-sm outline-none"
                        style={{ borderColor: "#D6E8EE", color: "#315E6E", background: "#fff" }}
                        value={regForm.insurance} onChange={(e) => setRegForm((f) => ({ ...f, insurance: e.target.value }))}>
                        {INSURANCE_LIST.map((i) => <option key={i}>{i}</option>)}
                      </select>
                    </div>
                    {/* Member No */}
                    {regForm.insurance !== "Mandiri / Umum" && (
                      <div className="col-span-2">
                        <label className="text-xs font-semibold block mb-1.5" style={{ color: "#315E6E" }}>No. Kepesertaan / Polis *</label>
                        <input className="w-full border rounded-lg px-3 py-2.5 text-sm outline-none"
                          style={{ borderColor: regErrors.memberNo ? "#C0362C" : "#D6E8EE", color: "#315E6E", fontFamily: "var(--font-mono)" }}
                          placeholder="Nomor anggota / polis..."
                          value={regForm.memberNo} onChange={(e) => { setRegForm((f) => ({ ...f, memberNo: e.target.value })); setRegErrors((er) => ({ ...er, memberNo: "" })); }} />
                        {regErrors.memberNo && <p className="text-xs mt-1" style={{ color: "#C0362C" }}>⚠ {regErrors.memberNo}</p>}
                      </div>
                    )}
                    {/* Address */}
                    <div className="col-span-2">
                      <label className="text-xs font-semibold block mb-1.5" style={{ color: "#315E6E" }}>Alamat</label>
                      <textarea rows={2} className="w-full border rounded-lg px-3 py-2.5 text-sm outline-none resize-none"
                        style={{ borderColor: "#D6E8EE", color: "#315E6E" }}
                        placeholder="Alamat lengkap..."
                        value={regForm.address} onChange={(e) => setRegForm((f) => ({ ...f, address: e.target.value }))} />
                    </div>
                  </div>
                  <div className="flex justify-end mt-5">
                    <button onClick={() => {
                      const e = validateRegStep1();
                      if (Object.keys(e).length) { setRegErrors(e); return; }
                      setRegErrors({}); setRegStep(2);
                    }} className="px-6 py-2.5 rounded-lg text-sm font-semibold text-white"
                      style={{ background: "#0D6E6E" }}>Lanjut ke Data Kunjungan →</button>
                  </div>
                </div>
              )}

              {/* Visit form — Step 2 */}
              {(regStep === 2 || (!isNewPatient && foundPatient)) && (
                <div className="rounded-xl p-5 shadow-sm" style={{ background: "#fff", border: "1px solid #D6E8EE" }}>
                  <h3 className="text-sm font-semibold mb-4" style={{ color: "#164E63" }}>Data Kunjungan</h3>
                  <div className="space-y-4">
                    {/* Visit type */}
                    <div>
                      <label className="text-xs font-semibold block mb-2" style={{ color: "#315E6E" }}>Jenis Kunjungan *</label>
                      <div className="grid grid-cols-3 gap-2">
                        {(["Rawat Jalan", "Rawat Inap", "IGD"] as VisitType[]).map((t) => (
                          <button key={t} onClick={() => setVisitForm((f) => ({ ...f, visitType: t, poli: t === "IGD" ? "IGD" : f.poli }))}
                            className="py-2.5 rounded-lg text-sm font-semibold border-2 transition-all"
                            style={{ background: visitForm.visitType === t ? "#EAF6F8" : "#F7FBFC", color: visitForm.visitType === t ? "#0D6E6E" : "#66869A", borderColor: visitForm.visitType === t ? "#0D6E6E" : "#D6E8EE" }}>
                            {t === "Rawat Jalan" ? "🏥 " : t === "Rawat Inap" ? "🛏 " : "🚨 "}{t}
                          </button>
                        ))}
                      </div>
                    </div>

                    {/* Poli — hidden for IGD */}
                    {visitForm.visitType !== "IGD" && (
                      <div>
                        <label className="text-xs font-semibold block mb-1.5" style={{ color: "#315E6E" }}>Poli / Bagian *</label>
                        <select className="w-full border rounded-lg px-3 py-2.5 text-sm outline-none"
                          style={{ borderColor: "#D6E8EE", color: "#315E6E", background: "#fff" }}
                          value={visitForm.poli} onChange={(e) => setVisitForm((f) => ({ ...f, poli: e.target.value }))}>
                          {POLI_LIST.filter((p) => p !== "IGD").map((p) => <option key={p}>{p}</option>)}
                        </select>
                      </div>
                    )}

                    {/* Complaint */}
                    <div>
                      <label className="text-xs font-semibold block mb-1.5" style={{ color: "#315E6E" }}>Keluhan Utama *</label>
                      <textarea rows={3} className="w-full border rounded-lg px-3 py-2.5 text-sm outline-none resize-none"
                        style={{ borderColor: regErrors.complaint ? "#C0362C" : "#D6E8EE", color: "#315E6E" }}
                        placeholder="Deskripsikan keluhan pasien..."
                        value={visitForm.complaint} onChange={(e) => { setVisitForm((f) => ({ ...f, complaint: e.target.value })); setRegErrors((er) => ({ ...er, complaint: "" })); }} />
                      {regErrors.complaint && <p className="text-xs mt-1" style={{ color: "#C0362C" }}>⚠ {regErrors.complaint}</p>}
                    </div>

                    {/* Notes */}
                    <div>
                      <label className="text-xs font-semibold block mb-1.5" style={{ color: "#315E6E" }}>Catatan Tambahan</label>
                      <input className="w-full border rounded-lg px-3 py-2.5 text-sm outline-none"
                        style={{ borderColor: "#D6E8EE", color: "#315E6E" }}
                        placeholder="Informasi tambahan untuk petugas..."
                        value={visitForm.notes} onChange={(e) => setVisitForm((f) => ({ ...f, notes: e.target.value }))} />
                    </div>
                  </div>

                  <div className="flex justify-between mt-5">
                    {isNewPatient && (
                      <button onClick={() => setRegStep(1)} className="px-4 py-2.5 rounded-lg text-sm font-semibold border"
                        style={{ borderColor: "#D6E8EE", color: "#315E6E" }}>← Kembali</button>
                    )}
                    <button onClick={submitRegistration}
                      className="ml-auto px-6 py-2.5 rounded-lg text-sm font-semibold text-white"
                      style={{ background: "#0D6E6E" }}>
                      ✓ Selesaikan Registrasi
                    </button>
                  </div>
                </div>
              )}
            </div>
          )}

          {/* ════ ANTREAN ════ */}
          {view === "antrian" && (
            <div className="p-6 space-y-5">
              <div className="flex items-center justify-between">
                <div>
                  <h1 className="text-xl font-bold" style={{ color: "#164E63" }}>Manajemen Antrean</h1>
                  <p className="text-xs mt-0.5" style={{ color: "#66869A" }}>Monitor dan kelola antrean pasien hari ini</p>
                </div>
                <div className="flex gap-2">
                  <button onClick={callNext}
                    className="flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-semibold text-white"
                    style={{ background: "#0D6E6E" }}>
                    📢 Panggil Berikutnya
                  </button>
                  <button onClick={() => setView("registrasi")}
                    className="flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-semibold border"
                    style={{ borderColor: "#0D6E6E", color: "#0D6E6E" }}>
                    + Tambah
                  </button>
                </div>
              </div>

              {/* Status counters */}
              <div className="grid grid-cols-4 gap-3">
                {[
                  { title: "Menunggu",  count: waiting, ...Q_STYLE.menunggu },
                  { title: "Dipanggil", count: called,  ...Q_STYLE.dipanggil },
                  { title: "Dilayani",  count: serving, ...Q_STYLE.dilayani },
                  { title: "Selesai",   count: done,    ...Q_STYLE.selesai },
                ].map((s) => (
                  <div key={s.title} className="rounded-xl p-4 flex items-center gap-3" style={{ background: "#fff", border: "1px solid #D6E8EE" }}>
                    <span className="w-3 h-3 rounded-full shrink-0" style={{ background: s.dot }} />
                    <div>
                      <p className="text-2xl font-bold" style={{ fontFamily: "var(--font-mono)", color: s.color }}>{s.count}</p>
                      <p className="text-xs" style={{ color: "#66869A" }}>{s.title}</p>
                    </div>
                  </div>
                ))}
              </div>

              {/* Filters */}
              <div className="flex gap-3">
                <div className="relative flex-1" style={{ maxWidth: 280 }}>
                  <span className="absolute left-3 top-1/2 -translate-y-1/2" style={{ color: "#8CA5B0", fontSize: 13 }}>🔍</span>
                  <input className="w-full border rounded-lg pl-9 pr-4 py-2 text-sm outline-none"
                    style={{ borderColor: "#D6E8EE", background: "#fff", color: "#315E6E" }}
                    placeholder="Cari nama, No. RM, No. Antrean..."
                    value={qSearch} onChange={(e) => setQSearch(e.target.value)} />
                </div>
                <div className="flex gap-1.5">
                  {["Semua", "menunggu", "dipanggil", "dilayani", "selesai"].map((s) => (
                    <button key={s} onClick={() => setQFilter(s)}
                      className="text-xs px-3 py-2 rounded-lg border font-medium capitalize transition-colors"
                      style={{ background: qFilter === s ? "#0D6E6E" : "#fff", color: qFilter === s ? "#fff" : "#66869A", borderColor: qFilter === s ? "#0D6E6E" : "#D6E8EE" }}>
                      {s === "Semua" ? "Semua" : Q_STYLE[s as QueueStatus].label}
                    </button>
                  ))}
                </div>
              </div>

              {/* Queue table */}
              <div className="rounded-xl shadow-sm overflow-hidden" style={{ background: "#fff", border: "1px solid #D6E8EE" }}>
                <table className="w-full text-sm">
                  <thead>
                    <tr style={{ background: "#F7FBFC", borderBottom: "1px solid #D6E8EE" }}>
                      {["NO.", "PASIEN", "KUNJUNGAN", "POLI", "PENJAMIN", "DAFTAR", "DIPANGGIL", "STATUS", "AKSI"].map((h) => (
                        <th key={h} className="text-left text-xs font-semibold px-4 py-3" style={{ color: "#66869A" }}>{h}</th>
                      ))}
                    </tr>
                  </thead>
                  <tbody className="divide-y" style={{ borderColor: "#EEF6F8" }}>
                    {filteredQueue.map((q) => {
                      const s = Q_STYLE[q.status];
                      return (
                        <tr key={q.id} className="hover:bg-slate-50 transition-colors">
                          <td className="px-4 py-3">
                            <span className="w-8 h-8 rounded-full flex items-center justify-center font-bold text-sm text-white"
                              style={{ background: "#0D6E6E" }}>{q.no}</span>
                          </td>
                          <td className="px-4 py-3">
                            <p className="text-sm font-semibold" style={{ color: "#164E63" }}>{q.name}</p>
                            <p className="text-xs" style={{ fontFamily: "var(--font-mono)", color: "#8CA5B0" }}>{q.rm}</p>
                            <p className="text-xs" style={{ color: "#8CA5B0" }}>{q.age} thn · {q.gender === "L" ? "L" : "P"}</p>
                          </td>
                          <td className="px-4 py-3">
                            <span className="text-xs px-1.5 py-0.5 rounded font-medium"
                              style={{ background: q.visitType === "IGD" ? "#FDE7E7" : q.visitType === "Rawat Inap" ? "#E1EEFB" : "#D9F3EA", color: q.visitType === "IGD" ? "#C0362C" : q.visitType === "Rawat Inap" ? "#1D5FA8" : "#117A68" }}>
                              {q.visitType}
                            </span>
                          </td>
                          <td className="px-4 py-3 text-xs" style={{ color: "#315E6E" }}>{q.poli}</td>
                          <td className="px-4 py-3 text-xs" style={{ color: "#66869A" }}>{q.insurance}</td>
                          <td className="px-4 py-3 text-xs" style={{ fontFamily: "var(--font-mono)", color: "#66869A" }}>{q.registeredAt}</td>
                          <td className="px-4 py-3 text-xs" style={{ fontFamily: "var(--font-mono)", color: "#66869A" }}>{q.calledAt ?? "—"}</td>
                          <td className="px-4 py-3">
                            <span className="flex items-center gap-1.5 text-xs font-medium whitespace-nowrap">
                              <span className="w-1.5 h-1.5 rounded-full shrink-0" style={{ background: s.dot }} />
                              <span style={{ color: s.color }}>{s.label}</span>
                            </span>
                          </td>
                          <td className="px-4 py-3">
                            <div className="flex gap-1">
                              {q.status === "menunggu" && (
                                <button onClick={() => updateQStatus(q.id, "dipanggil")}
                                  className="text-xs px-2.5 py-1 rounded-lg font-medium text-white"
                                  style={{ background: "#A66700" }}>Panggil</button>
                              )}
                              {q.status === "dipanggil" && (
                                <button onClick={() => updateQStatus(q.id, "dilayani")}
                                  className="text-xs px-2.5 py-1 rounded-lg font-medium text-white"
                                  style={{ background: "#0D6E6E" }}>Layani</button>
                              )}
                              {q.status === "dilayani" && (
                                <button onClick={() => updateQStatus(q.id, "selesai")}
                                  className="text-xs px-2.5 py-1 rounded-lg font-medium text-white"
                                  style={{ background: "#117A68" }}>Selesai</button>
                              )}
                              {(q.status === "menunggu" || q.status === "dipanggil") && (
                                <button onClick={() => setConfirmId(q.id)}
                                  className="text-xs px-2 py-1 rounded-lg border"
                                  style={{ borderColor: "#FDE7E7", color: "#C0362C" }}>Batal</button>
                              )}
                            </div>
                          </td>
                        </tr>
                      );
                    })}
                    {filteredQueue.length === 0 && (
                      <tr><td colSpan={9} className="px-4 py-10 text-center text-sm" style={{ color: "#8CA5B0" }}>Tidak ada data antrean</td></tr>
                    )}
                  </tbody>
                </table>
              </div>
            </div>
          )}

          {/* ════ KAMAR ════ */}
          {view === "kamar" && (
            <div className="p-6 space-y-5">
              <div>
                <h1 className="text-xl font-bold" style={{ color: "#164E63" }}>Ketersediaan Kamar</h1>
                <p className="text-xs mt-0.5" style={{ color: "#66869A" }}>Status kamar rawat inap secara real-time</p>
              </div>

              {/* Summary bar */}
              <div className="grid grid-cols-5 gap-3">
                {bedSummary.map((b) => {
                  const cc = CLASS_COLOR[b.class];
                  const pct = b.total > 0 ? Math.round(((b.total - b.avail) / b.total) * 100) : 0;
                  return (
                    <div key={b.class} className="rounded-xl p-4 shadow-sm" style={{ background: "#fff", border: "1px solid #D6E8EE" }}>
                      <span className="text-xs font-semibold px-1.5 py-0.5 rounded inline-block mb-2"
                        style={{ background: cc.bg, color: cc.color }}>{b.class}</span>
                      <p className="text-2xl font-bold" style={{ fontFamily: "var(--font-mono)", color: "#164E63" }}>{b.total - b.avail}/{b.total}</p>
                      <div className="h-1 rounded-full overflow-hidden mt-2" style={{ background: "#D6E8EE" }}>
                        <div className="h-full rounded-full" style={{ width: `${pct}%`, background: pct >= 90 ? "#C0362C" : pct >= 70 ? "#A66700" : "#117A68" }} />
                      </div>
                      <p className="text-xs mt-1.5" style={{ color: "#117A68" }}>{b.avail} kosong</p>
                    </div>
                  );
                })}
              </div>

              {/* Filter tabs */}
              <div className="flex gap-2 flex-wrap">
                {["Semua", "VIP", "Kelas I", "Kelas II", "Kelas III", "ICU"].map((c) => (
                  <button key={c} onClick={() => setBedFilter(c)}
                    className="text-xs px-3 py-1.5 rounded-full border font-medium transition-colors"
                    style={{ background: bedFilter === c ? "#0D6E6E" : "#fff", color: bedFilter === c ? "#fff" : "#66869A", borderColor: bedFilter === c ? "#0D6E6E" : "#D6E8EE" }}>
                    {c}
                  </button>
                ))}
                <span className="ml-auto text-xs self-center" style={{ color: "#8CA5B0" }}>
                  <span style={{ color: "#117A68", fontWeight: 600 }}>■</span> Tersedia &nbsp;
                  <span style={{ color: "#C0362C", fontWeight: 600 }}>■</span> Terisi &nbsp;
                  <span style={{ color: "#8CA5B0", fontWeight: 600 }}>■</span> Maintenance
                </span>
              </div>

              {/* Bed grid */}
              <div className="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-3">
                {filteredBeds.map((bed) => {
                  const cc = CLASS_COLOR[bed.class];
                  const isFree = bed.status === "tersedia";
                  const isMaint = bed.status === "maintenance";
                  return (
                    <div key={bed.id} className="rounded-xl p-4 transition-shadow hover:shadow-md"
                      style={{ background: isMaint ? "#F7FBFC" : "#fff", border: `1px solid ${isFree ? "#D6E8EE" : isMaint ? "#D6E8EE" : "#C7DDE5"}` }}>
                      <div className="flex items-start justify-between mb-2">
                        <p className="text-sm font-bold" style={{ color: "#164E63" }}>{bed.room}</p>
                        <span className="text-xs px-1.5 py-0.5 rounded-full font-medium"
                          style={{ background: isFree ? "#D9F3EA" : isMaint ? "#EEF6F8" : "#FDE7E7", color: isFree ? "#117A68" : isMaint ? "#8CA5B0" : "#C0362C" }}>
                          {isFree ? "Kosong" : isMaint ? "Maintenance" : "Terisi"}
                        </span>
                      </div>
                      <span className="text-xs px-1.5 py-0.5 rounded font-medium inline-block mb-2"
                        style={{ background: cc.bg, color: cc.color }}>{bed.class}</span>
                      {bed.gender && !isMaint && (
                        <p className="text-xs mb-2" style={{ color: "#8CA5B0" }}>
                          {bed.gender === "Mixed" ? "🧑‍🤝‍🧑 Campuran" : bed.gender === "L" ? "👨 Pria" : "👩 Wanita"}
                        </p>
                      )}
                      {bed.patientName && (
                        <div className="pt-2 border-t" style={{ borderColor: "#D6E8EE" }}>
                          <p className="text-xs font-semibold truncate" style={{ color: "#315E6E" }}>{bed.patientName}</p>
                          <p className="text-xs" style={{ fontFamily: "var(--font-mono)", color: "#8CA5B0" }}>{bed.patientRm}</p>
                          <p className="text-xs mt-0.5" style={{ color: "#8CA5B0" }}>Masuk: {bed.admitDate} · {bed.days} hr</p>
                        </div>
                      )}
                      {isFree && (
                        <button onClick={() => { showToast(`Kamar ${bed.room} ditandai untuk persiapan admisi`); }}
                          className="mt-3 w-full text-xs py-1.5 rounded-lg border font-medium transition-colors hover:bg-teal-50"
                          style={{ borderColor: "#0D6E6E", color: "#0D6E6E" }}>
                          Reservasi Kamar
                        </button>
                      )}
                    </div>
                  );
                })}
              </div>
            </div>
          )}
        </main>
      </div>

      {/* Confirm cancel modal */}
      {confirmId && (
        <div className="fixed inset-0 z-50 flex items-center justify-center" style={{ background: "rgba(0,0,0,0.4)" }}>
          <div className="rounded-2xl p-6 w-96 shadow-2xl" style={{ background: "#fff" }}>
            <p className="text-lg font-bold text-center mb-2" style={{ color: "#C0362C" }}>[!] Konfirmasi Pembatalan</p>
            <p className="text-sm text-center mb-1" style={{ color: "#315E6E" }}>
              Kunjungan <strong>{queue.find((q) => q.id === confirmId)?.name}</strong> akan dibatalkan.
            </p>
            <p className="text-xs text-center mb-5" style={{ color: "#66869A" }}>Tindakan ini tidak dapat dibatalkan (undo).</p>
            <div className="flex gap-3">
              <button onClick={() => setConfirmId(null)} className="flex-1 py-2 rounded-lg border text-sm font-semibold"
                style={{ borderColor: "#D6E8EE", color: "#315E6E" }}>Kembali</button>
              <button onClick={() => updateQStatus(confirmId, "batal")} className="flex-1 py-2 rounded-lg text-sm font-semibold text-white"
                style={{ background: "#C0362C" }}>Batalkan Kunjungan</button>
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
