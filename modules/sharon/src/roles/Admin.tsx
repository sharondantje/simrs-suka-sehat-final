import { useState } from "react";
import type { UserInfo, UserRole } from "../types/user";
import logoSrc from "../assets/logo.png";

interface AdminProps {
  user: UserInfo;
  onLogout: () => void;
}

type AdminView = "dashboard" | "pengguna" | "laporan" | "sistem" | "log";
type UserStatus = "aktif" | "nonaktif" | "terkunci";
type LogLevel = "info" | "warning" | "error" | "success";

interface SystemUser {
  id: string;
  username: string;
  fullName: string;
  role: UserRole;
  unit: string;
  email: string;
  phone: string;
  status: UserStatus;
  lastLogin: string;
  createdAt: string;
  loginCount: number;
}

interface ActivityLog {
  id: string;
  timestamp: string;
  user: string;
  role: string;
  action: string;
  module: string;
  ip: string;
  level: LogLevel;
  detail: string;
}

interface SystemSetting {
  key: string;
  label: string;
  description: string;
  value: string | boolean | number;
  type: "toggle" | "text" | "number" | "select";
  options?: string[];
  category: string;
}

interface StatCard {
  module: string;
  visits: number;
  inpatient: number;
  igd: number;
  revenue: number;
  change: number;
}

// ── Seed data ─────────────────────────────────────────────────────────────────

const SYSTEM_USERS: SystemUser[] = [
  { id: "u1", username: "dr.ahmad",   fullName: "dr. Ahmad Fauzi, Sp.PD",      role: "Dokter",             unit: "Rawat Jalan",      email: "ahmad@rssukasehat.id",   phone: "081234567890", status: "aktif",    lastLogin: "07/09/2026 08:12", createdAt: "01/01/2024", loginCount: 842 },
  { id: "u2", username: "ns.rina",    fullName: "Ns. Rina Wulandari, S.Kep",   role: "Perawat / Bidan",    unit: "IGD / Emergency",  email: "rina@rssukasehat.id",    phone: "081234567891", status: "aktif",    lastLogin: "07/09/2026 07:55", createdAt: "01/03/2024", loginCount: 620 },
  { id: "u3", username: "sari.dewi",  fullName: "Sari Dewi Kusuma",             role: "Resepsionis / Admisi",unit: "Admisi",           email: "sari@rssukasehat.id",    phone: "081234567892", status: "aktif",    lastLogin: "07/09/2026 08:00", createdAt: "15/02/2024", loginCount: 510 },
  { id: "u4", username: "apt.budi",   fullName: "apt. Budi Santoso, S.Farm",   role: "Farmasi",            unit: "Instalasi Farmasi",email: "budi@rssukasehat.id",    phone: "081234567893", status: "aktif",    lastLogin: "07/09/2026 08:30", createdAt: "01/01/2024", loginCount: 730 },
  { id: "u5", username: "dewi.kasir", fullName: "Dewi Rahayu, S.E.",           role: "Kasir",              unit: "Keuangan",         email: "dewi@rssukasehat.id",    phone: "081234567894", status: "aktif",    lastLogin: "07/09/2026 08:45", createdAt: "01/04/2024", loginCount: 489 },
  { id: "u6", username: "rizky.it",   fullName: "Rizky Pratama, S.Kom",        role: "Admin / IT",         unit: "IT & Sistem",      email: "rizky@rssukasehat.id",   phone: "081234567895", status: "aktif",    lastLogin: "07/09/2026 07:30", createdAt: "01/01/2024", loginCount: 1240 },
  { id: "u7", username: "dr.rina",    fullName: "dr. Rina Soekartini, Sp.PD",  role: "Dokter",             unit: "Rawat Inap",       email: "rinasoe@rssukasehat.id", phone: "081234567896", status: "nonaktif", lastLogin: "01/08/2026 14:00", createdAt: "01/06/2024", loginCount: 312 },
  { id: "u8", username: "ns.dewi",    fullName: "Ns. Dewi Lestari, S.Kep",     role: "Perawat / Bidan",    unit: "Rawat Inap",       email: "dewilestari@rssukasehat.id",phone:"081234567897",status: "terkunci",lastLogin: "03/09/2026 09:10", createdAt: "01/05/2024", loginCount: 198 },
];

const ACTIVITY_LOGS: ActivityLog[] = [
  { id: "l1",  timestamp: "07/09/2026 09:45", user: "apt. Budi Santoso",    role: "Farmasi",             action: "DISPENSE_COMPLETE",     module: "Farmasi",    ip: "192.168.1.45", level: "success", detail: "Resep RX-2026-00452 diserahkan ke Bambang Irawan" },
  { id: "l2",  timestamp: "07/09/2026 09:32", user: "Sari Dewi Kusuma",     role: "Resepsionis / Admisi",action: "PATIENT_REGISTER",      module: "Admisi",     ip: "192.168.1.22", level: "info",    detail: "Registrasi pasien baru Putri Ayu Lestari (RM-2026-000210)" },
  { id: "l3",  timestamp: "07/09/2026 09:15", user: "Dewi Rahayu",          role: "Kasir",               action: "PAYMENT_PROCESSED",     module: "Keuangan",   ip: "192.168.1.30", level: "success", detail: "Pembayaran Rp 450.000 oleh Hendra Gunawan — Tunai" },
  { id: "l4",  timestamp: "07/09/2026 09:00", user: "Ns. Rina Wulandari",   role: "Perawat / Bidan",     action: "VITAL_RECORDED",        module: "Keperawatan",ip: "192.168.1.18", level: "info",    detail: "TTV dicatat untuk Ahmad Rizal (ICU-01) — SpO2 94%, HR 98" },
  { id: "l5",  timestamp: "07/09/2026 08:55", user: "Ns. Dewi Lestari",     role: "Perawat / Bidan",     action: "LOGIN_FAILED",          module: "Auth",       ip: "192.168.1.19", level: "warning", detail: "Percobaan login gagal ke-3 — akun dikunci otomatis" },
  { id: "l6",  timestamp: "07/09/2026 08:47", user: "dr. Ahmad Fauzi",      role: "Dokter",              action: "ICD_FINALIZED",         module: "IGD",        ip: "192.168.1.12", level: "info",    detail: "Diagnosis I50.0 difinalisasi untuk Herman Suryadi" },
  { id: "l7",  timestamp: "07/09/2026 08:30", user: "apt. Budi Santoso",    role: "Farmasi",             action: "STOCK_LOW_ALERT",       module: "Farmasi",    ip: "192.168.1.45", level: "warning", detail: "Stok Glimepiride 2 mg tersisa 25 unit (min. 40)" },
  { id: "l8",  timestamp: "07/09/2026 08:12", user: "dr. Ahmad Fauzi",      role: "Dokter",              action: "LOGIN_SUCCESS",         module: "Auth",       ip: "192.168.1.12", level: "success", detail: "Login berhasil dari perangkat Desktop-Poli" },
  { id: "l9",  timestamp: "07/09/2026 08:01", user: "SYSTEM",               role: "Sistem",              action: "BACKUP_COMPLETE",       module: "Sistem",     ip: "127.0.0.1",    level: "success", detail: "Backup otomatis selesai — 2.4 GB — 07/09/2026 08:00" },
  { id: "l10", timestamp: "07/09/2026 07:55", user: "Sari Dewi Kusuma",     role: "Resepsionis / Admisi",action: "QUEUE_CALLED",          module: "Admisi",     ip: "192.168.1.22", level: "info",    detail: "No. antrean 1 dipanggil — Rina Wulandari, Poli Jantung" },
  { id: "l11", timestamp: "07/09/2026 07:30", user: "Rizky Pratama",        role: "Admin / IT",          action: "USER_UNLOCKED",         module: "Admin",      ip: "192.168.1.5",  level: "info",    detail: "Akun dr. Candra Utama dibuka kuncinya oleh Admin" },
  { id: "l12", timestamp: "06/09/2026 23:58", user: "SYSTEM",               role: "Sistem",              action: "DAILY_REPORT_GEN",      module: "Sistem",     ip: "127.0.0.1",    level: "info",    detail: "Laporan harian otomatis dihasilkan untuk 06/09/2026" },
];

const SETTINGS: SystemSetting[] = [
  { key: "maint_mode",    label: "Mode Maintenance",        description: "Nonaktifkan akses pengguna saat pemeliharaan sistem",       value: false,    type: "toggle",  category: "Sistem" },
  { key: "auto_backup",   label: "Backup Otomatis",         description: "Jadwalkan backup database setiap hari pukul 08:00",         value: true,     type: "toggle",  category: "Sistem" },
  { key: "session_timeout",label:"Timeout Sesi (menit)",    description: "Durasi sesi login sebelum otomatis keluar",                 value: 30,       type: "number",  category: "Sistem" },
  { key: "max_login",     label: "Maks. Percobaan Login",   description: "Kunci akun setelah gagal login beberapa kali",              value: 3,        type: "number",  category: "Keamanan" },
  { key: "log_retention", label: "Retensi Log (hari)",      description: "Berapa lama log aktivitas disimpan",                       value: 90,       type: "number",  category: "Keamanan" },
  { key: "2fa_required",  label: "Wajib 2FA untuk Admin",   description: "Admin/IT harus menggunakan autentikasi dua faktor",         value: false,    type: "toggle",  category: "Keamanan" },
  { key: "hospital_name", label: "Nama Rumah Sakit",        description: "Nama yang ditampilkan di header dan dokumen",               value: "RS Suka Sehat", type: "text", category: "Profil" },
  { key: "hospital_addr", label: "Alamat",                  description: "Alamat lengkap rumah sakit",                               value: "Jl. Kesehatan No. 1, Bandung", type: "text", category: "Profil" },
  { key: "notif_email",   label: "Email Notifikasi",        description: "Penerima email notifikasi sistem",                         value: "admin@rssukasehat.id", type: "text", category: "Notifikasi" },
  { key: "notif_stok",    label: "Alert Stok Kritis",       description: "Kirim notifikasi saat stok obat di bawah minimum",         value: true,     type: "toggle",  category: "Notifikasi" },
  { key: "report_period", label: "Periode Laporan Default", description: "Rentang waktu default laporan",                            value: "Bulanan", type: "select", options: ["Harian", "Mingguan", "Bulanan", "Tahunan"], category: "Laporan" },
];

const STAT_DATA: StatCard[] = [
  { module: "Rawat Jalan", visits: 412, inpatient: 0,  igd: 0,  revenue: 82400000, change: 8 },
  { module: "Rawat Inap",  visits: 0,   inpatient: 87, igd: 0,  revenue: 521000000, change: 3 },
  { module: "IGD",         visits: 0,   inpatient: 0,  igd: 63, revenue: 94500000,  change: -2 },
  { module: "Farmasi",     visits: 389, inpatient: 0,  igd: 0,  revenue: 136200000, change: 12 },
];

// ── Style maps ────────────────────────────────────────────────────────────────

const USER_STATUS_STYLE: Record<UserStatus, { bg: string; color: string; label: string }> = {
  aktif:    { bg: "#D9F3EA", color: "#117A68", label: "Aktif" },
  nonaktif: { bg: "#EEF6F8", color: "#66869A", label: "Nonaktif" },
  terkunci: { bg: "#FDE7E7", color: "#C0362C", label: "Terkunci" },
};

const ROLE_COLOR: Record<UserRole, { bg: string; color: string }> = {
  "Dokter":              { bg: "#E1EEFB", color: "#1D5FA8" },
  "Perawat / Bidan":     { bg: "#f0fdf4", color: "#117A68" },
  "Resepsionis / Admisi":{ bg: "#FFF0D5", color: "#A66700" },
  "Farmasi":             { bg: "#fce7f3", color: "#db2777" },
  "Kasir":               { bg: "#ede9fe", color: "#7c3aed" },
  "Admin / IT":          { bg: "#EAF6F8", color: "#0D6E6E" },
};

const LOG_STYLE: Record<LogLevel, { bg: string; color: string; icon: string }> = {
  info:    { bg: "#EAF6F8", color: "#0D6E6E", icon: "ℹ" },
  warning: { bg: "#FFF0D5", color: "#A66700", icon: "⚠" },
  error:   { bg: "#FDE7E7", color: "#C0362C", icon: "✗" },
  success: { bg: "#D9F3EA", color: "#117A68", icon: "✓" },
};

const SIDEBAR_ITEMS: { key: AdminView; label: string; icon: string }[] = [
  { key: "dashboard", label: "Dashboard",         icon: "📊" },
  { key: "pengguna",  label: "Manajemen Pengguna",icon: "👥" },
  { key: "laporan",   label: "Laporan & Statistik",icon: "📈" },
  { key: "sistem",    label: "Pengaturan Sistem",  icon: "⚙" },
  { key: "log",       label: "Log Aktivitas",      icon: "🗒" },
];

const ALL_ROLES: UserRole[] = ["Dokter", "Perawat / Bidan", "Resepsionis / Admisi", "Farmasi", "Kasir", "Admin / IT"];
function fmt(n: number) { return n.toLocaleString("id-ID"); }

// ── Component ─────────────────────────────────────────────────────────────────

export default function Admin({ user, onLogout }: AdminProps) {
  const [view, setView]           = useState<AdminView>("dashboard");
  const [collapsed, setCollapsed] = useState(false);
  const [notif]                   = useState(4);

  const [users, setUsers]         = useState<SystemUser[]>(SYSTEM_USERS);
  const [settings, setSettings]   = useState<SystemSetting[]>(SETTINGS);

  const [toast, setToast]         = useState<string | null>(null);

  // Pengguna state
  const [userSearch, setUserSearch]       = useState("");
  const [userRoleFilter, setUserRoleFilter] = useState("Semua");
  const [userStatusFilter, setUserStatusFilter] = useState("Semua");
  const [editUserId, setEditUserId]       = useState<string | null>(null);
  const [addModal, setAddModal]           = useState(false);
  const [confirmLock, setConfirmLock]     = useState<string | null>(null);
  const [newUser, setNewUser] = useState({ fullName: "", username: "", email: "", phone: "", role: "Dokter" as UserRole, unit: "" });
  const [newUserErrors, setNewUserErrors] = useState<Record<string, string>>({});

  // Log state
  const [logSearch, setLogSearch] = useState("");
  const [logLevel, setLogLevel]   = useState("Semua");

  // Laporan period
  const [reportPeriod, setReportPeriod] = useState("Bulanan");

  function showToast(msg: string) {
    setToast(msg);
    setTimeout(() => setToast(null), 2600);
  }

  function toggleUserStatus(id: string) {
    setUsers((prev) => prev.map((u) => {
      if (u.id !== id) return u;
      const next: UserStatus = u.status === "aktif" ? "nonaktif" : "aktif";
      return { ...u, status: next };
    }));
    const u = users.find((u) => u.id === id)!;
    showToast(u.status === "aktif" ? `Akun ${u.fullName} dinonaktifkan` : `Akun ${u.fullName} diaktifkan kembali`);
  }

  function unlockUser(id: string) {
    setUsers((prev) => prev.map((u) => u.id === id ? { ...u, status: "aktif" } : u));
    const u = users.find((u) => u.id === id)!;
    showToast(`Akun ${u.fullName} berhasil dibuka kuncinya`);
    setConfirmLock(null);
  }

  function validateNewUser() {
    const e: Record<string, string> = {};
    if (!newUser.fullName.trim()) e.fullName = "Nama lengkap wajib diisi";
    if (!newUser.username.trim() || newUser.username.includes(" ")) e.username = "Username wajib diisi dan tanpa spasi";
    if (users.some((u) => u.username === newUser.username)) e.username = "Username sudah digunakan";
    if (!newUser.email.match(/^[^@]+@[^@]+\.[^@]+$/)) e.email = "Format email tidak valid";
    if (!newUser.unit.trim()) e.unit = "Unit wajib diisi";
    return e;
  }

  function submitNewUser() {
    const e = validateNewUser();
    if (Object.keys(e).length) { setNewUserErrors(e); return; }
    const initials = newUser.fullName.split(" ").slice(0, 2).map((w) => w[0]).join("").toUpperCase();
    const u: SystemUser = {
      id: String(Date.now()), username: newUser.username, fullName: newUser.fullName,
      role: newUser.role, unit: newUser.unit, email: newUser.email, phone: newUser.phone,
      status: "aktif", lastLogin: "Belum pernah login",
      createdAt: new Date().toLocaleDateString("id-ID"), loginCount: 0,
    };
    void initials;
    setUsers((prev) => [...prev, u]);
    setAddModal(false);
    setNewUser({ fullName: "", username: "", email: "", phone: "", role: "Dokter", unit: "" });
    setNewUserErrors({});
    showToast(`Pengguna ${u.fullName} berhasil ditambahkan`);
  }

  function updateSetting(key: string, value: string | boolean | number) {
    setSettings((prev) => prev.map((s) => s.key === key ? { ...s, value } : s));
    showToast("Pengaturan berhasil disimpan");
  }

  // ── Derived ───────────────────────────────────────────────────────────────
  const activeUsers   = users.filter((u) => u.status === "aktif").length;
  const lockedUsers   = users.filter((u) => u.status === "terkunci").length;
  const inactiveUsers = users.filter((u) => u.status === "nonaktif").length;

  const filteredUsers = users.filter((u) => {
    const mr = userRoleFilter === "Semua" || u.role === userRoleFilter;
    const ms = userStatusFilter === "Semua" || u.status === userStatusFilter;
    const mq = !userSearch || u.fullName.toLowerCase().includes(userSearch.toLowerCase()) || u.username.includes(userSearch) || u.unit.toLowerCase().includes(userSearch.toLowerCase());
    return mr && ms && mq;
  });

  const filteredLogs = ACTIVITY_LOGS.filter((l) => {
    const ml = logLevel === "Semua" || l.level === logLevel;
    const mq = !logSearch || l.user.toLowerCase().includes(logSearch.toLowerCase()) || l.action.toLowerCase().includes(logSearch.toLowerCase()) || l.detail.toLowerCase().includes(logSearch.toLowerCase());
    return ml && mq;
  });

  const settingCategories = [...new Set(settings.map((s) => s.category))];
  const totalRevenue = STAT_DATA.reduce((a, b) => a + b.revenue, 0);

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
          <span className="hidden md:inline text-white/40 text-xs">— Admin / IT</span>
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
              <p className="text-white/60 text-xs leading-tight">Admin / IT — {user.unit}</p>
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
              <p className="text-xs font-semibold uppercase tracking-widest" style={{ color: "#8CA5B0" }}>Administrasi</p>
            </div>
          )}
          <nav className="flex-1 p-2 space-y-0.5">
            {SIDEBAR_ITEMS.map((item) => {
              const active = view === item.key;
              const badge = item.key === "pengguna" ? lockedUsers : 0;
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
              <p className="text-xs mt-0.5" style={{ color: "#8CA5B0" }}>Admin · {user.unit}</p>
              <div className="flex items-center gap-1 mt-1.5">
                <span className="w-1.5 h-1.5 rounded-full" style={{ background: "#117A68" }} />
                <span className="text-xs" style={{ color: "#117A68" }}>Sistem Online</span>
              </div>
            </div>
          )}
        </aside>

        {/* Main */}
        <main className="flex-1 overflow-y-auto" style={{ background: "#F3FAFC" }}>

          {/* ════ DASHBOARD ════ */}
          {view === "dashboard" && (
            <div className="p-6 space-y-5">
              <div>
                <h1 className="text-xl font-bold" style={{ color: "#164E63" }}>Dashboard Admin / IT</h1>
                <p className="text-xs mt-0.5" style={{ color: "#66869A" }}>
                  {new Date().toLocaleDateString("id-ID", { weekday: "long", day: "2-digit", month: "long", year: "numeric" })} · Sistem v2.1.0
                </p>
              </div>

              {/* KPIs */}
              <div className="grid grid-cols-2 lg:grid-cols-4 gap-4">
                {[
                  { label: "Pengguna Aktif",     val: String(activeUsers),                  sub: `${inactiveUsers} nonaktif`, icon: "👥", accent: "#0D6E6E", bg: "#EAF6F8" },
                  { label: "Akun Terkunci",       val: String(lockedUsers),                  sub: "butuh perhatian",           icon: "🔒", accent: lockedUsers > 0 ? "#C0362C" : "#117A68", bg: lockedUsers > 0 ? "#FDE7E7" : "#D9F3EA" },
                  { label: "Total Pendapatan",    val: `Rp ${fmt(Math.round(totalRevenue/1000000))}jt`, sub: "bulan ini",     icon: "💰", accent: "#117A68", bg: "#D9F3EA" },
                  { label: "Log Hari Ini",        val: String(ACTIVITY_LOGS.filter((l) => l.timestamp.startsWith("07/09")).length), sub: "aktivitas tercatat", icon: "🗒", accent: "#1D5FA8", bg: "#E1EEFB" },
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
                {/* Module stats */}
                <div className="col-span-2 rounded-xl shadow-sm overflow-hidden" style={{ background: "#fff", border: "1px solid #D6E8EE" }}>
                  <div className="px-5 py-4 border-b flex items-center justify-between" style={{ borderColor: "#D6E8EE" }}>
                    <h2 className="text-sm font-semibold" style={{ color: "#164E63" }}>Statistik Per Modul — Bulan Ini</h2>
                    <button onClick={() => setView("laporan")} className="text-xs font-medium" style={{ color: "#0D6E6E" }}>Laporan lengkap →</button>
                  </div>
                  <table className="w-full text-sm">
                    <thead>
                      <tr style={{ background: "#F7FBFC", borderBottom: "1px solid #D6E8EE" }}>
                        {["MODUL", "KUNJUNGAN/TRANSAKSI", "PENDAPATAN", "PERTUMBUHAN"].map((h) => (
                          <th key={h} className="text-left text-xs font-semibold px-5 py-3" style={{ color: "#66869A" }}>{h}</th>
                        ))}
                      </tr>
                    </thead>
                    <tbody className="divide-y" style={{ borderColor: "#EEF6F8" }}>
                      {STAT_DATA.map((s) => (
                        <tr key={s.module} className="hover:bg-slate-50">
                          <td className="px-5 py-3 text-sm font-semibold" style={{ color: "#164E63" }}>{s.module}</td>
                          <td className="px-5 py-3 text-sm font-bold" style={{ fontFamily: "var(--font-mono)", color: "#315E6E" }}>
                            {fmt(s.visits || s.inpatient || s.igd)}
                          </td>
                          <td className="px-5 py-3 text-sm font-bold" style={{ fontFamily: "var(--font-mono)", color: "#0D6E6E" }}>
                            Rp {fmt(s.revenue)}
                          </td>
                          <td className="px-5 py-3">
                            <span className="flex items-center gap-1 text-sm font-bold"
                              style={{ color: s.change >= 0 ? "#117A68" : "#C0362C" }}>
                              {s.change >= 0 ? "▲" : "▼"} {Math.abs(s.change)}%
                            </span>
                          </td>
                        </tr>
                      ))}
                      <tr style={{ borderTop: "2px solid #D6E8EE", background: "#F7FBFC" }}>
                        <td className="px-5 py-3 text-sm font-bold" style={{ color: "#164E63" }}>Total</td>
                        <td className="px-5 py-3 text-sm font-bold" style={{ fontFamily: "var(--font-mono)", color: "#315E6E" }}>
                          {fmt(STAT_DATA.reduce((a, b) => a + (b.visits || b.inpatient || b.igd), 0))}
                        </td>
                        <td className="px-5 py-3 text-sm font-bold" style={{ fontFamily: "var(--font-mono)", color: "#0D6E6E" }}>
                          Rp {fmt(totalRevenue)}
                        </td>
                        <td className="px-5 py-3" />
                      </tr>
                    </tbody>
                  </table>
                </div>

                {/* System status + recent alerts */}
                <div className="space-y-4">
                  <div className="rounded-xl shadow-sm overflow-hidden" style={{ background: "#fff", border: "1px solid #D6E8EE" }}>
                    <div className="px-4 py-4 border-b" style={{ borderColor: "#D6E8EE" }}>
                      <h3 className="text-sm font-semibold" style={{ color: "#164E63" }}>Status Sistem</h3>
                    </div>
                    <div className="divide-y" style={{ borderColor: "#EEF6F8" }}>
                      {[
                        { label: "Server Aplikasi",   ok: true },
                        { label: "Database",           ok: true },
                        { label: "Backup Otomatis",    ok: true },
                        { label: "Koneksi Jaringan",   ok: true },
                        { label: "Integrasi BPJS",     ok: false },
                      ].map((sys) => (
                        <div key={sys.label} className="flex items-center justify-between px-4 py-2.5">
                          <p className="text-xs" style={{ color: "#315E6E" }}>{sys.label}</p>
                          <span className="flex items-center gap-1 text-xs font-medium"
                            style={{ color: sys.ok ? "#117A68" : "#C0362C" }}>
                            <span className="w-1.5 h-1.5 rounded-full" style={{ background: sys.ok ? "#117A68" : "#C0362C" }} />
                            {sys.ok ? "Online" : "Gangguan"}
                          </span>
                        </div>
                      ))}
                    </div>
                  </div>

                  <div className="rounded-xl shadow-sm overflow-hidden" style={{ background: "#fff", border: "1px solid #D6E8EE" }}>
                    <div className="px-4 py-4 border-b" style={{ borderColor: "#D6E8EE" }}>
                      <h3 className="text-sm font-semibold" style={{ color: "#164E63" }}>⚠ Log Terakhir</h3>
                    </div>
                    <div className="divide-y" style={{ borderColor: "#EEF6F8" }}>
                      {ACTIVITY_LOGS.filter((l) => l.level === "warning" || l.level === "error").slice(0, 3).map((l) => {
                        const ls = LOG_STYLE[l.level];
                        return (
                          <div key={l.id} className="px-4 py-3">
                            <div className="flex items-center gap-1.5 mb-0.5">
                              <span className="text-xs px-1.5 py-0.5 rounded font-bold" style={{ background: ls.bg, color: ls.color }}>{ls.icon} {l.level}</span>
                              <span className="text-xs" style={{ fontFamily: "var(--font-mono)", color: "#8CA5B0" }}>{l.timestamp.split(" ")[1]}</span>
                            </div>
                            <p className="text-xs" style={{ color: "#315E6E" }}>{l.detail}</p>
                          </div>
                        );
                      })}
                    </div>
                    <div className="px-4 pb-3">
                      <button onClick={() => setView("log")} className="w-full text-xs py-1.5 rounded-lg border font-medium"
                        style={{ borderColor: "#D6E8EE", color: "#0D6E6E" }}>Lihat Semua Log →</button>
                    </div>
                  </div>
                </div>
              </div>

              {/* User distribution */}
              <div className="rounded-xl p-5 shadow-sm" style={{ background: "#fff", border: "1px solid #D6E8EE" }}>
                <h2 className="text-sm font-semibold mb-4" style={{ color: "#164E63" }}>Distribusi Pengguna per Role</h2>
                <div className="grid grid-cols-6 gap-3">
                  {ALL_ROLES.map((role) => {
                    const count = users.filter((u) => u.role === role).length;
                    const rc = ROLE_COLOR[role];
                    return (
                      <div key={role} className="rounded-xl p-4 text-center" style={{ background: "#F7FBFC" }}>
                        <span className="text-xs px-2 py-1 rounded-full font-semibold inline-block mb-2"
                          style={{ background: rc.bg, color: rc.color }}>{role}</span>
                        <p className="text-2xl font-bold" style={{ fontFamily: "var(--font-mono)", color: "#164E63" }}>{count}</p>
                        <p className="text-xs mt-0.5" style={{ color: "#8CA5B0" }}>pengguna</p>
                      </div>
                    );
                  })}
                </div>
              </div>
            </div>
          )}

          {/* ════ PENGGUNA ════ */}
          {view === "pengguna" && (
            <div className="p-6 space-y-5">
              <div className="flex items-center justify-between">
                <div>
                  <h1 className="text-xl font-bold" style={{ color: "#164E63" }}>Manajemen Pengguna</h1>
                  <p className="text-xs mt-0.5" style={{ color: "#66869A" }}>Kelola akun dan hak akses seluruh staf rumah sakit</p>
                </div>
                <button onClick={() => setAddModal(true)}
                  className="flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-semibold text-white"
                  style={{ background: "#0D6E6E" }}>
                  + Tambah Pengguna
                </button>
              </div>

              {/* Summary chips */}
              <div className="flex gap-3">
                {[
                  { label: `${activeUsers} Aktif`,    color: "#117A68", bg: "#D9F3EA" },
                  { label: `${inactiveUsers} Nonaktif`, color: "#66869A", bg: "#EEF6F8" },
                  { label: `${lockedUsers} Terkunci`,  color: "#C0362C", bg: "#FDE7E7" },
                ].map((c) => (
                  <span key={c.label} className="text-xs px-3 py-1.5 rounded-full font-semibold"
                    style={{ background: c.bg, color: c.color }}>{c.label}</span>
                ))}
              </div>

              {/* Filters */}
              <div className="flex gap-3 flex-wrap">
                <div className="relative" style={{ minWidth: 260 }}>
                  <span className="absolute left-3 top-1/2 -translate-y-1/2" style={{ color: "#8CA5B0", fontSize: 13 }}>🔍</span>
                  <input className="w-full border rounded-lg pl-9 pr-4 py-2 text-sm outline-none"
                    style={{ borderColor: "#D6E8EE", background: "#fff", color: "#315E6E" }}
                    placeholder="Cari nama, username, unit..."
                    value={userSearch} onChange={(e) => setUserSearch(e.target.value)} />
                </div>
                <select className="border rounded-lg px-3 py-2 text-sm outline-none"
                  style={{ borderColor: "#D6E8EE", background: "#fff", color: "#315E6E" }}
                  value={userRoleFilter} onChange={(e) => setUserRoleFilter(e.target.value)}>
                  <option value="Semua">Semua Role</option>
                  {ALL_ROLES.map((r) => <option key={r}>{r}</option>)}
                </select>
                <div className="flex gap-1.5">
                  {["Semua", "aktif", "nonaktif", "terkunci"].map((s) => (
                    <button key={s} onClick={() => setUserStatusFilter(s)}
                      className="text-xs px-3 py-2 rounded-lg border font-medium capitalize"
                      style={{ background: userStatusFilter === s ? "#0D6E6E" : "#fff", color: userStatusFilter === s ? "#fff" : "#66869A", borderColor: userStatusFilter === s ? "#0D6E6E" : "#D6E8EE" }}>
                      {s === "Semua" ? "Semua Status" : USER_STATUS_STYLE[s as UserStatus].label}
                    </button>
                  ))}
                </div>
              </div>

              {/* Table */}
              <div className="rounded-xl shadow-sm overflow-hidden" style={{ background: "#fff", border: "1px solid #D6E8EE" }}>
                <table className="w-full text-sm">
                  <thead>
                    <tr style={{ background: "#F7FBFC", borderBottom: "1px solid #D6E8EE" }}>
                      {["PENGGUNA", "ROLE", "UNIT", "EMAIL", "LOGIN TERAKHIR", "TOTAL LOGIN", "STATUS", "AKSI"].map((h) => (
                        <th key={h} className="text-left text-xs font-semibold px-4 py-3" style={{ color: "#66869A" }}>{h}</th>
                      ))}
                    </tr>
                  </thead>
                  <tbody className="divide-y" style={{ borderColor: "#EEF6F8" }}>
                    {filteredUsers.map((u) => {
                      const ss = USER_STATUS_STYLE[u.status];
                      const rc = ROLE_COLOR[u.role];
                      const isMe = u.username === user.username;
                      return (
                        <tr key={u.id} className="hover:bg-slate-50 transition-colors" style={{ background: isMe ? "#f0f9ff" : "transparent" }}>
                          <td className="px-4 py-3">
                            <div className="flex items-center gap-2.5">
                              <div className="w-8 h-8 rounded-full flex items-center justify-center text-xs font-bold text-white shrink-0"
                                style={{ background: "#0D6E6E" }}>
                                {u.fullName.split(" ").slice(0, 2).map((w) => w[0]).join("").toUpperCase()}
                              </div>
                              <div>
                                <p className="text-sm font-semibold" style={{ color: "#164E63" }}>{u.fullName}{isMe && <span className="ml-1 text-xs" style={{ color: "#0D6E6E" }}>(Saya)</span>}</p>
                                <p className="text-xs" style={{ fontFamily: "var(--font-mono)", color: "#8CA5B0" }}>@{u.username}</p>
                              </div>
                            </div>
                          </td>
                          <td className="px-4 py-3">
                            <span className="text-xs px-1.5 py-0.5 rounded font-medium"
                              style={{ background: rc.bg, color: rc.color }}>{u.role}</span>
                          </td>
                          <td className="px-4 py-3 text-xs" style={{ color: "#66869A" }}>{u.unit}</td>
                          <td className="px-4 py-3 text-xs" style={{ color: "#66869A" }}>{u.email}</td>
                          <td className="px-4 py-3 text-xs" style={{ fontFamily: "var(--font-mono)", color: "#8CA5B0" }}>{u.lastLogin}</td>
                          <td className="px-4 py-3 text-sm font-bold text-center" style={{ fontFamily: "var(--font-mono)", color: "#315E6E" }}>{u.loginCount}</td>
                          <td className="px-4 py-3">
                            <span className="text-xs px-2 py-0.5 rounded-full font-medium" style={{ background: ss.bg, color: ss.color }}>{ss.label}</span>
                          </td>
                          <td className="px-4 py-3">
                            <div className="flex gap-1">
                              {!isMe && u.status === "terkunci" && (
                                <button onClick={() => setConfirmLock(u.id)}
                                  className="text-xs px-2.5 py-1 rounded-lg font-medium text-white"
                                  style={{ background: "#A66700" }}>🔓 Buka</button>
                              )}
                              {!isMe && u.status !== "terkunci" && (
                                <button onClick={() => toggleUserStatus(u.id)}
                                  className="text-xs px-2.5 py-1 rounded-lg font-medium border"
                                  style={{ borderColor: u.status === "aktif" ? "#fca5a5" : "#86efac", color: u.status === "aktif" ? "#C0362C" : "#117A68" }}>
                                  {u.status === "aktif" ? "Nonaktifkan" : "Aktifkan"}
                                </button>
                              )}
                              <button onClick={() => setEditUserId(editUserId === u.id ? null : u.id)}
                                className="text-xs px-2.5 py-1 rounded-lg border font-medium"
                                style={{ borderColor: "#D6E8EE", color: "#66869A" }}>Detail</button>
                            </div>
                          </td>
                        </tr>
                      );
                    })}
                    {filteredUsers.length === 0 && (
                      <tr><td colSpan={8} className="px-4 py-10 text-center text-sm" style={{ color: "#8CA5B0" }}>Tidak ada pengguna ditemukan</td></tr>
                    )}
                  </tbody>
                </table>
              </div>
            </div>
          )}

          {/* ════ LAPORAN ════ */}
          {view === "laporan" && (
            <div className="p-6 space-y-5">
              <div className="flex items-center justify-between">
                <div>
                  <h1 className="text-xl font-bold" style={{ color: "#164E63" }}>Laporan & Statistik</h1>
                  <p className="text-xs mt-0.5" style={{ color: "#66869A" }}>Ringkasan kinerja operasional dan keuangan rumah sakit</p>
                </div>
                <div className="flex gap-2">
                  {["Harian", "Mingguan", "Bulanan", "Tahunan"].map((p) => (
                    <button key={p} onClick={() => setReportPeriod(p)}
                      className="text-xs px-3 py-2 rounded-lg border font-medium"
                      style={{ background: reportPeriod === p ? "#0D6E6E" : "#fff", color: reportPeriod === p ? "#fff" : "#66869A", borderColor: reportPeriod === p ? "#0D6E6E" : "#D6E8EE" }}>
                      {p}
                    </button>
                  ))}
                  <button onClick={() => showToast("Laporan berhasil diunduh")}
                    className="flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-semibold text-white"
                    style={{ background: "#117A68" }}>
                    ⬇ Export PDF
                  </button>
                </div>
              </div>

              {/* Revenue highlight */}
              <div className="rounded-xl p-5 shadow-sm" style={{ background: "linear-gradient(135deg, #0D6E6E, #0A4D68)", border: "none" }}>
                <div className="flex items-center justify-between">
                  <div>
                    <p className="text-sm font-semibold text-white/70">Total Pendapatan — {reportPeriod}</p>
                    <p className="text-4xl font-bold text-white mt-1" style={{ fontFamily: "var(--font-mono)" }}>
                      Rp {fmt(totalRevenue)}
                    </p>
                    <p className="text-sm text-white/60 mt-1">▲ 7% dibanding periode sebelumnya</p>
                  </div>
                  <div className="grid grid-cols-2 gap-3">
                    {[
                      { label: "Pasien Rawat Jalan", val: "412" },
                      { label: "Pasien Rawat Inap",  val: "87" },
                      { label: "Kunjungan IGD",       val: "63" },
                      { label: "Resep Dilayani",      val: "389" },
                    ].map((s) => (
                      <div key={s.label} className="text-center px-4 py-2 rounded-xl" style={{ background: "rgba(255,255,255,0.12)" }}>
                        <p className="text-xl font-bold text-white" style={{ fontFamily: "var(--font-mono)" }}>{s.val}</p>
                        <p className="text-xs text-white/60 mt-0.5">{s.label}</p>
                      </div>
                    ))}
                  </div>
                </div>
              </div>

              {/* Per module breakdown */}
              <div className="grid grid-cols-2 gap-5">
                {STAT_DATA.map((s) => {
                  const pct = Math.round((s.revenue / totalRevenue) * 100);
                  return (
                    <div key={s.module} className="rounded-xl p-5 shadow-sm" style={{ background: "#fff", border: "1px solid #D6E8EE" }}>
                      <div className="flex items-start justify-between mb-4">
                        <h3 className="text-sm font-bold" style={{ color: "#164E63" }}>{s.module}</h3>
                        <span className="text-sm font-bold" style={{ fontFamily: "var(--font-mono)", color: s.change >= 0 ? "#117A68" : "#C0362C" }}>
                          {s.change >= 0 ? "▲" : "▼"} {Math.abs(s.change)}%
                        </span>
                      </div>
                      <p className="text-2xl font-bold mb-1" style={{ fontFamily: "var(--font-mono)", color: "#0D6E6E" }}>
                        Rp {fmt(s.revenue)}
                      </p>
                      <div className="h-2 rounded-full overflow-hidden mb-2" style={{ background: "#D6E8EE" }}>
                        <div className="h-full rounded-full" style={{ width: `${pct}%`, background: "#0D6E6E" }} />
                      </div>
                      <div className="flex items-center justify-between text-xs" style={{ color: "#66869A" }}>
                        <span>{pct}% dari total pendapatan</span>
                        <span>{fmt(s.visits || s.inpatient || s.igd)} transaksi</span>
                      </div>
                    </div>
                  );
                })}
              </div>

              {/* Insurance breakdown */}
              <div className="rounded-xl p-5 shadow-sm" style={{ background: "#fff", border: "1px solid #D6E8EE" }}>
                <h2 className="text-sm font-semibold mb-4" style={{ color: "#164E63" }}>Distribusi Penjamin Pasien</h2>
                <div className="grid grid-cols-4 gap-4">
                  {[
                    { label: "BPJS", pct: 62, val: "627 pasien", color: "#0D6E6E" },
                    { label: "Mandiri / Umum", pct: 21, val: "212 pasien", color: "#1D5FA8" },
                    { label: "Asuransi Swasta", pct: 13, val: "132 pasien", color: "#7c3aed" },
                    { label: "Jaminan Perusahaan", pct: 4, val: "40 pasien", color: "#A66700" },
                  ].map((ins) => (
                    <div key={ins.label} className="text-center">
                      <div className="relative w-20 h-20 mx-auto mb-3">
                        <svg viewBox="0 0 36 36" className="w-full h-full -rotate-90">
                          <circle cx="18" cy="18" r="15.5" fill="none" stroke="#D6E8EE" strokeWidth="3" />
                          <circle cx="18" cy="18" r="15.5" fill="none" stroke={ins.color} strokeWidth="3"
                            strokeDasharray={`${ins.pct} ${100 - ins.pct}`} strokeLinecap="round" />
                        </svg>
                        <span className="absolute inset-0 flex items-center justify-center text-sm font-bold" style={{ color: ins.color }}>{ins.pct}%</span>
                      </div>
                      <p className="text-xs font-semibold" style={{ color: "#315E6E" }}>{ins.label}</p>
                      <p className="text-xs mt-0.5" style={{ color: "#8CA5B0" }}>{ins.val}</p>
                    </div>
                  ))}
                </div>
              </div>
            </div>
          )}

          {/* ════ SISTEM ════ */}
          {view === "sistem" && (
            <div className="p-6 space-y-5">
              <div>
                <h1 className="text-xl font-bold" style={{ color: "#164E63" }}>Pengaturan Sistem</h1>
                <p className="text-xs mt-0.5" style={{ color: "#66869A" }}>Konfigurasi sistem SIMRS — hanya dapat diakses oleh Admin / IT</p>
              </div>

              {settingCategories.map((cat) => (
                <div key={cat} className="rounded-xl shadow-sm overflow-hidden" style={{ background: "#fff", border: "1px solid #D6E8EE" }}>
                  <div className="px-5 py-4 border-b" style={{ borderColor: "#D6E8EE", background: "#F7FBFC" }}>
                    <h2 className="text-sm font-bold" style={{ color: "#164E63" }}>{cat}</h2>
                  </div>
                  <div className="divide-y" style={{ borderColor: "#EEF6F8" }}>
                    {settings.filter((s) => s.category === cat).map((s) => (
                      <div key={s.key} className="flex items-center justify-between gap-6 px-5 py-4">
                        <div className="flex-1">
                          <p className="text-sm font-semibold" style={{ color: "#164E63" }}>{s.label}</p>
                          <p className="text-xs mt-0.5" style={{ color: "#66869A" }}>{s.description}</p>
                        </div>
                        <div className="shrink-0">
                          {s.type === "toggle" && (
                            <button onClick={() => updateSetting(s.key, !s.value)}
                              className="relative w-12 h-6 rounded-full transition-all"
                              style={{ background: s.value ? "#0D6E6E" : "#D6E8EE" }}>
                              <span className="absolute top-1 w-4 h-4 rounded-full bg-white shadow transition-all"
                                style={{ left: s.value ? "calc(100% - 20px)" : "4px" }} />
                            </button>
                          )}
                          {s.type === "number" && (
                            <input type="number" className="w-20 border rounded-lg px-2 py-1.5 text-sm text-center outline-none"
                              style={{ borderColor: "#D6E8EE", color: "#315E6E", fontFamily: "var(--font-mono)" }}
                              value={s.value as number}
                              onChange={(e) => updateSetting(s.key, Number(e.target.value))} />
                          )}
                          {s.type === "text" && (
                            <input type="text" className="w-56 border rounded-lg px-3 py-1.5 text-sm outline-none"
                              style={{ borderColor: "#D6E8EE", color: "#315E6E" }}
                              value={s.value as string}
                              onChange={(e) => updateSetting(s.key, e.target.value)} />
                          )}
                          {s.type === "select" && (
                            <select className="border rounded-lg px-3 py-1.5 text-sm outline-none"
                              style={{ borderColor: "#D6E8EE", color: "#315E6E", background: "#fff" }}
                              value={s.value as string}
                              onChange={(e) => updateSetting(s.key, e.target.value)}>
                              {s.options?.map((o) => <option key={o}>{o}</option>)}
                            </select>
                          )}
                        </div>
                      </div>
                    ))}
                  </div>
                </div>
              ))}

              {/* Danger zone */}
              <div className="rounded-xl shadow-sm overflow-hidden" style={{ background: "#fff", border: "2px solid #fca5a5" }}>
                <div className="px-5 py-4 border-b" style={{ borderColor: "#fca5a5", background: "#fff5f5" }}>
                  <h2 className="text-sm font-bold" style={{ color: "#C0362C" }}>⚠ Zona Berbahaya</h2>
                  <p className="text-xs mt-0.5" style={{ color: "#991b1b" }}>Tindakan di bawah ini tidak dapat dibatalkan. Lakukan dengan hati-hati.</p>
                </div>
                <div className="divide-y" style={{ borderColor: "#FDE7E7" }}>
                  {[
                    { label: "Reset Semua Sesi Aktif", desc: "Paksa logout semua pengguna yang sedang login", btn: "Reset Sesi" },
                    { label: "Hapus Log Lama (> 90 hari)", desc: "Bersihkan log aktivitas yang sudah melebihi batas retensi", btn: "Hapus Log" },
                    { label: "Inisialisasi Ulang Cache", desc: "Bersihkan cache sistem — aplikasi mungkin lebih lambat sementara", btn: "Flush Cache" },
                  ].map((a) => (
                    <div key={a.label} className="flex items-center justify-between gap-6 px-5 py-4">
                      <div>
                        <p className="text-sm font-semibold" style={{ color: "#164E63" }}>{a.label}</p>
                        <p className="text-xs mt-0.5" style={{ color: "#66869A" }}>{a.desc}</p>
                      </div>
                      <button onClick={() => showToast(`${a.btn} berhasil dieksekusi`)}
                        className="text-xs px-4 py-2 rounded-lg font-semibold border shrink-0"
                        style={{ borderColor: "#fca5a5", color: "#C0362C" }}>{a.btn}</button>
                    </div>
                  ))}
                </div>
              </div>
            </div>
          )}

          {/* ════ LOG ════ */}
          {view === "log" && (
            <div className="p-6 space-y-5">
              <div>
                <h1 className="text-xl font-bold" style={{ color: "#164E63" }}>Log Aktivitas Sistem</h1>
                <p className="text-xs mt-0.5" style={{ color: "#66869A" }}>Audit trail seluruh tindakan pengguna dan sistem</p>
              </div>

              {/* Level summary */}
              <div className="grid grid-cols-4 gap-3">
                {(["info", "success", "warning", "error"] as LogLevel[]).map((lvl) => {
                  const ls = LOG_STYLE[lvl];
                  const count = ACTIVITY_LOGS.filter((l) => l.level === lvl).length;
                  return (
                    <div key={lvl} className="rounded-xl p-4 flex items-center gap-3 shadow-sm" style={{ background: "#fff", border: "1px solid #D6E8EE" }}>
                      <span className="w-9 h-9 rounded-lg flex items-center justify-center text-lg font-bold"
                        style={{ background: ls.bg, color: ls.color }}>{ls.icon}</span>
                      <div>
                        <p className="text-xl font-bold" style={{ fontFamily: "var(--font-mono)", color: ls.color }}>{count}</p>
                        <p className="text-xs capitalize" style={{ color: "#66869A" }}>{lvl}</p>
                      </div>
                    </div>
                  );
                })}
              </div>

              {/* Filters */}
              <div className="flex gap-3">
                <div className="relative flex-1" style={{ maxWidth: 320 }}>
                  <span className="absolute left-3 top-1/2 -translate-y-1/2" style={{ color: "#8CA5B0", fontSize: 13 }}>🔍</span>
                  <input className="w-full border rounded-lg pl-9 pr-4 py-2 text-sm outline-none"
                    style={{ borderColor: "#D6E8EE", background: "#fff", color: "#315E6E" }}
                    placeholder="Cari pengguna, aksi, detail..."
                    value={logSearch} onChange={(e) => setLogSearch(e.target.value)} />
                </div>
                <div className="flex gap-1.5">
                  {["Semua", "info", "success", "warning", "error"].map((l) => (
                    <button key={l} onClick={() => setLogLevel(l)}
                      className="text-xs px-3 py-2 rounded-lg border font-medium capitalize"
                      style={{ background: logLevel === l ? "#0D6E6E" : "#fff", color: logLevel === l ? "#fff" : "#66869A", borderColor: logLevel === l ? "#0D6E6E" : "#D6E8EE" }}>
                      {l === "Semua" ? "Semua" : l}
                    </button>
                  ))}
                </div>
                <button onClick={() => showToast("Log berhasil diunduh sebagai CSV")}
                  className="flex items-center gap-1.5 px-4 py-2 rounded-lg text-sm font-semibold border"
                  style={{ borderColor: "#D6E8EE", color: "#315E6E" }}>
                  ⬇ Export CSV
                </button>
              </div>

              {/* Log table */}
              <div className="rounded-xl shadow-sm overflow-hidden" style={{ background: "#fff", border: "1px solid #D6E8EE" }}>
                <table className="w-full text-sm">
                  <thead>
                    <tr style={{ background: "#F7FBFC", borderBottom: "1px solid #D6E8EE" }}>
                      {["WAKTU", "LEVEL", "PENGGUNA", "ROLE", "AKSI", "MODUL", "IP", "DETAIL"].map((h) => (
                        <th key={h} className="text-left text-xs font-semibold px-4 py-3" style={{ color: "#66869A" }}>{h}</th>
                      ))}
                    </tr>
                  </thead>
                  <tbody className="divide-y" style={{ borderColor: "#EEF6F8" }}>
                    {filteredLogs.map((l) => {
                      const ls = LOG_STYLE[l.level];
                      return (
                        <tr key={l.id} className="hover:bg-slate-50 transition-colors">
                          <td className="px-4 py-3 text-xs whitespace-nowrap" style={{ fontFamily: "var(--font-mono)", color: "#8CA5B0" }}>
                            {l.timestamp}
                          </td>
                          <td className="px-4 py-3">
                            <span className="text-xs px-2 py-0.5 rounded-full font-bold"
                              style={{ background: ls.bg, color: ls.color }}>{ls.icon} {l.level}</span>
                          </td>
                          <td className="px-4 py-3 text-xs font-semibold" style={{ color: "#315E6E" }}>{l.user}</td>
                          <td className="px-4 py-3">
                            <span className="text-xs px-1.5 py-0.5 rounded" style={{ background: "#EEF6F8", color: "#456D7D" }}>{l.role}</span>
                          </td>
                          <td className="px-4 py-3">
                            <span className="text-xs font-mono px-1.5 py-0.5 rounded" style={{ fontFamily: "var(--font-mono)", background: "#EAF6F8", color: "#0D6E6E", fontSize: "10px" }}>
                              {l.action}
                            </span>
                          </td>
                          <td className="px-4 py-3 text-xs" style={{ color: "#66869A" }}>{l.module}</td>
                          <td className="px-4 py-3 text-xs" style={{ fontFamily: "var(--font-mono)", color: "#8CA5B0" }}>{l.ip}</td>
                          <td className="px-4 py-3 text-xs" style={{ color: "#315E6E", maxWidth: 260 }}>{l.detail}</td>
                        </tr>
                      );
                    })}
                    {filteredLogs.length === 0 && (
                      <tr><td colSpan={8} className="px-4 py-10 text-center text-sm" style={{ color: "#8CA5B0" }}>Tidak ada log ditemukan</td></tr>
                    )}
                  </tbody>
                </table>
              </div>
            </div>
          )}
        </main>
      </div>

      {/* Add User Modal */}
      {addModal && (
        <div className="fixed inset-0 z-50 flex items-center justify-center" style={{ background: "rgba(0,0,0,0.4)" }}>
          <div className="rounded-2xl p-6 w-[520px] shadow-2xl max-h-[90vh] overflow-y-auto" style={{ background: "#fff" }}>
            <p className="text-lg font-bold mb-4" style={{ color: "#164E63" }}>Tambah Pengguna Baru</p>
            <div className="space-y-3">
              {([
                { key: "fullName", label: "Nama Lengkap *", placeholder: "Nama lengkap beserta gelar" },
                { key: "username", label: "Username *", placeholder: "Tanpa spasi, mis. dr.budi" },
                { key: "email",    label: "Email *", placeholder: "email@rssukasehat.id" },
                { key: "phone",    label: "No. HP", placeholder: "08xxxxxxxxxx" },
                { key: "unit",     label: "Unit / Bagian *", placeholder: "Mis. Rawat Jalan, IGD" },
              ] as const).map((f) => (
                <div key={f.key}>
                  <label className="text-xs font-semibold block mb-1" style={{ color: "#315E6E" }}>{f.label}</label>
                  <input className="w-full border rounded-lg px-3 py-2.5 text-sm outline-none"
                    style={{ borderColor: newUserErrors[f.key] ? "#C0362C" : "#D6E8EE", color: "#315E6E" }}
                    placeholder={f.placeholder}
                    value={newUser[f.key]}
                    onChange={(e) => { setNewUser((v) => ({ ...v, [f.key]: e.target.value })); setNewUserErrors((er) => ({ ...er, [f.key]: "" })); }} />
                  {newUserErrors[f.key] && <p className="text-xs mt-0.5" style={{ color: "#C0362C" }}>⚠ {newUserErrors[f.key]}</p>}
                </div>
              ))}
              <div>
                <label className="text-xs font-semibold block mb-1" style={{ color: "#315E6E" }}>Role *</label>
                <select className="w-full border rounded-lg px-3 py-2.5 text-sm outline-none"
                  style={{ borderColor: "#D6E8EE", color: "#315E6E", background: "#fff" }}
                  value={newUser.role} onChange={(e) => setNewUser((v) => ({ ...v, role: e.target.value as UserRole }))}>
                  {ALL_ROLES.map((r) => <option key={r}>{r}</option>)}
                </select>
              </div>
            </div>
            <div className="flex gap-3 mt-5">
              <button onClick={() => { setAddModal(false); setNewUserErrors({}); }}
                className="flex-1 py-2.5 rounded-lg border text-sm font-semibold"
                style={{ borderColor: "#D6E8EE", color: "#315E6E" }}>Batal</button>
              <button onClick={submitNewUser}
                className="flex-1 py-2.5 rounded-lg text-sm font-semibold text-white"
                style={{ background: "#0D6E6E" }}>Simpan Pengguna</button>
            </div>
          </div>
        </div>
      )}

      {/* Confirm unlock modal */}
      {confirmLock && (
        <div className="fixed inset-0 z-50 flex items-center justify-center" style={{ background: "rgba(0,0,0,0.4)" }}>
          <div className="rounded-2xl p-6 w-96 shadow-2xl" style={{ background: "#fff" }}>
            <p className="text-base font-bold mb-1" style={{ color: "#A66700" }}>🔓 Buka Kunci Akun</p>
            <p className="text-sm mb-5" style={{ color: "#66869A" }}>
              Akun <strong>{users.find((u) => u.id === confirmLock)?.fullName}</strong> akan dibuka kuncinya. Pengguna dapat login kembali.
            </p>
            <div className="flex gap-3">
              <button onClick={() => setConfirmLock(null)}
                className="flex-1 py-2.5 rounded-lg border text-sm font-semibold"
                style={{ borderColor: "#D6E8EE", color: "#315E6E" }}>Batal</button>
              <button onClick={() => unlockUser(confirmLock!)}
                className="flex-1 py-2.5 rounded-lg text-sm font-semibold text-white"
                style={{ background: "#A66700" }}>Buka Kunci</button>
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
