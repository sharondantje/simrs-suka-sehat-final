import { useState } from "react";
import type { UserInfo } from "../types/user";
import logoSrc from "../assets/logo.png";

interface PerawatProps {
  user: UserInfo;
  onLogout: () => void;
}

type PerawatView = "dashboard" | "vitals" | "catatan" | "obat" | "observasi";

type TriageLevel = "P1" | "P2" | "P3" | "P4";
type PatientStatus = "kritis" | "stabil" | "membaik" | "observasi";
type MedStatus = "belum" | "diberikan" | "ditunda" | "skip";
type ObsShift = "pagi" | "siang" | "malam";

interface WardPatient {
  id: string;
  rm: string;
  name: string;
  age: number;
  gender: "L" | "P";
  room: string;
  bed: string;
  diagnosis: string;
  icd: string;
  admitDate: string;
  days: number;
  triage: TriageLevel;
  status: PatientStatus;
  insurance: string;
  doctor: string;
  lastVital?: string;
  alert?: string;
}

interface VitalRecord {
  id: string;
  patientId: string;
  time: string;
  nurse: string;
  bp: string;
  hr: number;
  rr: number;
  temp: number;
  spo2: number;
  pain: number;
  gcs: number;
  notes: string;
}

interface NursingNote {
  id: string;
  patientId: string;
  time: string;
  nurse: string;
  shift: ObsShift;
  category: string;
  note: string;
  followUp: string;
}

interface Medication {
  id: string;
  patientId: string;
  drug: string;
  dose: string;
  route: string;
  frequency: string;
  times: string[];
  status: MedStatus[];
  prescribedBy: string;
}

interface ObsEntry {
  id: string;
  patientId: string;
  shift: ObsShift;
  date: string;
  nurse: string;
  generalCondition: string;
  consciousness: string;
  diet: string;
  elimination: string;
  mobility: string;
  pain: string;
  notes: string;
}

// ── Seed data ─────────────────────────────────────────────────────────────────

const PATIENTS: WardPatient[] = [
  { id: "p1", rm: "RM-2026-000219", name: "Herman Suryadi", age: 67, gender: "L", room: "VIP-01", bed: "VIP-01A", diagnosis: "Gagal Jantung Kongestif", icd: "I50.0", admitDate: "16/07/2026", days: 71, triage: "P1", status: "stabil", insurance: "BPJS", doctor: "dr. Ahmad Fauzi, Sp.PD", lastVital: "10 mnt lalu", alert: "Pantau intake cairan" },
  { id: "p2", rm: "RM-2026-000220", name: "Siti Nurhaliza", age: 54, gender: "P", room: "Kelas I-01", bed: "I-01A", diagnosis: "Diabetes Mellitus Tipe 2", icd: "E11", admitDate: "20/08/2026", days: 36, triage: "P2", status: "membaik", insurance: "BPJS", doctor: "dr. Rina Soekartini, Sp.PD", lastVital: "1 jam lalu" },
  { id: "p3", rm: "RM-2026-000221", name: "Bambang Irawan", age: 45, gender: "L", room: "Kelas I-04", bed: "I-04A", diagnosis: "Pneumonia", icd: "J18.9", admitDate: "01/09/2026", days: 24, triage: "P2", status: "observasi", insurance: "Mandiri", doctor: "dr. Budi Hartono, Sp.P", lastVital: "30 mnt lalu", alert: "Saturasi rendah kemarin" },
  { id: "p4", rm: "RM-2026-000222", name: "Wati Susanti", age: 38, gender: "P", room: "Kelas II-04", bed: "II-04A", diagnosis: "Post Op Appendektomi", icd: "Z48.0", admitDate: "05/09/2026", days: 20, triage: "P3", status: "membaik", insurance: "Asuransi Swasta", doctor: "dr. Andi Wijaya, Sp.B", lastVital: "2 jam lalu" },
  { id: "p5", rm: "RM-2026-000223", name: "Joko Widodo", age: 59, gender: "L", room: "Kelas III-02", bed: "III-02A", diagnosis: "Hipertensi Grade II", icd: "I10", admitDate: "04/09/2026", days: 21, triage: "P3", status: "stabil", insurance: "BPJS", doctor: "dr. Ahmad Fauzi, Sp.PD", lastVital: "3 jam lalu" },
  { id: "p6", rm: "RM-2026-000224", name: "Ahmad Rizal", age: 72, gender: "L", room: "ICU-01", bed: "ICU-01", diagnosis: "Stroke Iskemik", icd: "I63.9", admitDate: "06/09/2026", days: 19, triage: "P1", status: "kritis", insurance: "BPJS", doctor: "dr. Candra Utama, Sp.S", lastVital: "5 mnt lalu", alert: "Monitor ketat — GCS turun" },
];

const VITALS_HISTORY: VitalRecord[] = [
  { id: "v1", patientId: "p6", time: "07:00", nurse: "Ns. Rina", bp: "180/100", hr: 98, rr: 22, temp: 37.8, spo2: 94, pain: 0, gcs: 9, notes: "Pasien tidak sadar penuh" },
  { id: "v2", patientId: "p1", time: "08:00", nurse: "Ns. Rina", bp: "130/80", hr: 78, rr: 18, temp: 36.8, spo2: 97, pain: 2, gcs: 15, notes: "Edema pergelangan masih ada" },
  { id: "v3", patientId: "p3", time: "09:00", nurse: "Ns. Dewi", bp: "120/75", hr: 90, rr: 24, temp: 38.2, spo2: 95, pain: 3, gcs: 15, notes: "Batuk produktif" },
];

const MEDICATIONS: Medication[] = [
  { id: "m1", patientId: "p1", drug: "Furosemide", dose: "40 mg", route: "IV", frequency: "2x sehari", times: ["06:00", "18:00"], status: ["diberikan", "belum"], prescribedBy: "dr. Ahmad Fauzi" },
  { id: "m2", patientId: "p1", drug: "Spironolakton", dose: "25 mg", route: "Oral", frequency: "1x sehari", times: ["08:00"], status: ["diberikan"], prescribedBy: "dr. Ahmad Fauzi" },
  { id: "m3", patientId: "p3", drug: "Amoksisilin", dose: "500 mg", route: "IV", frequency: "3x sehari", times: ["06:00", "14:00", "22:00"], status: ["diberikan", "diberikan", "belum"], prescribedBy: "dr. Budi Hartono" },
  { id: "m4", patientId: "p6", drug: "Alteplase", dose: "0.9 mg/kg", route: "IV", frequency: "1x", times: ["08:00"], status: ["diberikan"], prescribedBy: "dr. Candra Utama" },
  { id: "m5", patientId: "p6", drug: "Aspirin", dose: "80 mg", route: "Oral", frequency: "1x sehari", times: ["08:00"], status: ["belum"], prescribedBy: "dr. Candra Utama" },
  { id: "m6", patientId: "p2", drug: "Metformin", dose: "500 mg", route: "Oral", frequency: "2x sehari", times: ["07:00", "19:00"], status: ["diberikan", "belum"], prescribedBy: "dr. Rina Soekartini" },
];

const NURSING_NOTES: NursingNote[] = [
  { id: "n1", patientId: "p6", time: "07:30", nurse: "Ns. Rina", shift: "pagi", category: "Neurologi", note: "GCS E2V2M5, pupil anisokor kanan lebih lebar. Segera lapor DPJP.", followUp: "Laporan sudah disampaikan ke dr. Candra" },
  { id: "n2", patientId: "p1", time: "08:15", nurse: "Ns. Rina", shift: "pagi", category: "Kardiovaskular", note: "Edema bilateral grade 2. Balance cairan -200 mL dari target.", followUp: "Lanjutkan pembatasan cairan 1000 mL/24 jam" },
  { id: "n3", patientId: "p3", time: "09:30", nurse: "Ns. Dewi", shift: "pagi", category: "Respirasi", note: "SpO2 membaik ke 96% setelah nebulisasi. Batuk berkurang.", followUp: "Nebulisasi ulang jam 13:00" },
];

const OBS_ENTRIES: ObsEntry[] = [
  { id: "o1", patientId: "p1", shift: "pagi", date: "07/09/2026", nurse: "Ns. Rina", generalCondition: "Lemah, composmentis", consciousness: "Composmentis, GCS 15", diet: "Habis 3/4 porsi, diet rendah garam", elimination: "BAK 400 mL/6 jam, BAB belum", mobility: "Bed rest dengan bantuan", pain: "Skala 2/10 — nyeri ringan", notes: "Pantau edema dan output urin tiap jam" },
];

// ── Helpers ───────────────────────────────────────────────────────────────────

const TRIAGE_STYLE: Record<TriageLevel, { bg: string; color: string; label: string }> = {
  P1: { bg: "#FDE7E7", color: "#C0362C", label: "P1 – Kritis" },
  P2: { bg: "#FFF0D5", color: "#A66700", label: "P2 – Darurat" },
  P3: { bg: "#D9F3EA", color: "#117A68", label: "P3 – Segera" },
  P4: { bg: "#E1EEFB", color: "#1D5FA8", label: "P4 – Minor" },
};

const STATUS_STYLE: Record<PatientStatus, { bg: string; color: string; label: string; dot: string }> = {
  kritis:    { bg: "#FDE7E7", color: "#C0362C", label: "Kritis",   dot: "#C0362C" },
  stabil:    { bg: "#E1EEFB", color: "#1D5FA8", label: "Stabil",   dot: "#1D5FA8" },
  membaik:   { bg: "#D9F3EA", color: "#117A68", label: "Membaik",  dot: "#117A68" },
  observasi: { bg: "#FFF0D5", color: "#A66700", label: "Observasi",dot: "#A66700" },
};

const MED_STYLE: Record<MedStatus, { bg: string; color: string; label: string }> = {
  belum:     { bg: "#FFF0D5", color: "#A66700", label: "Belum" },
  diberikan: { bg: "#D9F3EA", color: "#117A68", label: "✓ OK" },
  ditunda:   { bg: "#E1EEFB", color: "#1D5FA8", label: "Ditunda" },
  skip:      { bg: "#FDE7E7", color: "#C0362C", label: "Skip" },
};

const SHIFT_LABEL: Record<ObsShift, string> = { pagi: "Pagi (06-14)", siang: "Siang (14-22)", malam: "Malam (22-06)" };

const NOTE_CATS = ["Neurologi", "Kardiovaskular", "Respirasi", "Gastrointestinal", "Musculoskeletal", "Psikososial", "Luka & Integritas Kulit", "Lainnya"];

const SIDEBAR: { key: PerawatView; label: string; icon: string }[] = [
  { key: "dashboard",  label: "Dashboard Bangsal",   icon: "📊" },
  { key: "vitals",     label: "Tanda Vital",          icon: "💓" },
  { key: "catatan",    label: "Catatan Keperawatan",  icon: "📋" },
  { key: "obat",       label: "Pemberian Obat",       icon: "💊" },
  { key: "observasi",  label: "Observasi Pasien",     icon: "👁" },
];

function nowTime() { return new Date().toLocaleTimeString("id-ID", { hour: "2-digit", minute: "2-digit" }); }

// ── Component ─────────────────────────────────────────────────────────────────

export default function Perawat({ user, onLogout }: PerawatProps) {
  const [view, setView]           = useState<PerawatView>("dashboard");
  const [collapsed, setCollapsed] = useState(false);
  const [notif]                   = useState(3);

  const [patients]                = useState<WardPatient[]>(PATIENTS);
  const [vitals, setVitals]       = useState<VitalRecord[]>(VITALS_HISTORY);
  const [meds, setMeds]           = useState<Medication[]>(MEDICATIONS);
  const [notes, setNotes]         = useState<NursingNote[]>(NURSING_NOTES);
  const [obs, setObs]             = useState<ObsEntry[]>(OBS_ENTRIES);

  const [toast, setToast]         = useState<string | null>(null);
  const [selectedPid, setSelectedPid] = useState<string>("p1");

  // Vital form
  const [vForm, setVForm] = useState({ bp: "", hr: "", rr: "", temp: "", spo2: "", pain: "0", gcs: "15", notes: "" });
  const [vErrors, setVErrors] = useState<Record<string, string>>({});

  // Note form
  const [noteForm, setNoteForm] = useState({ category: "Kardiovaskular", shift: "pagi" as ObsShift, note: "", followUp: "" });
  const [noteErrors, setNoteErrors] = useState<Record<string, string>>({});

  // Obs form
  const [obsForm, setObsForm] = useState({ shift: "pagi" as ObsShift, generalCondition: "", consciousness: "", diet: "", elimination: "", mobility: "", pain: "", notes: "" });
  const [obsErrors, setObsErrors] = useState<Record<string, string>>({});

  // Filters
  const [medPatientId, setMedPatientId] = useState("p1");
  const [notePatientId, setNotePatientId] = useState("p1");
  const [obsPatientId, setObsPatientId] = useState("p1");

  function showToast(msg: string) {
    setToast(msg);
    setTimeout(() => setToast(null), 2500);
  }

  const selectedPatient = patients.find((p) => p.id === selectedPid)!;

  // ── Vital submit ──────────────────────────────────────────────────────────
  function submitVital() {
    const e: Record<string, string> = {};
    if (!vForm.bp.match(/^\d{2,3}\/\d{2,3}$/)) e.bp = "Format TD: sistolik/diastolik (mis. 120/80)";
    if (!vForm.hr || Number(vForm.hr) < 30 || Number(vForm.hr) > 250) e.hr = "Nadi harus antara 30-250 bpm";
    if (!vForm.rr || Number(vForm.rr) < 5 || Number(vForm.rr) > 60) e.rr = "RR harus antara 5-60 x/mnt";
    if (!vForm.temp || Number(vForm.temp) < 35 || Number(vForm.temp) > 42) e.temp = "Suhu 35–42 °C";
    if (!vForm.spo2 || Number(vForm.spo2) < 50 || Number(vForm.spo2) > 100) e.spo2 = "SpO₂ 50–100 %";
    if (Object.keys(e).length) { setVErrors(e); return; }

    const v: VitalRecord = {
      id: String(Date.now()), patientId: selectedPid, time: nowTime(), nurse: user.fullName,
      bp: vForm.bp, hr: Number(vForm.hr), rr: Number(vForm.rr), temp: Number(vForm.temp),
      spo2: Number(vForm.spo2), pain: Number(vForm.pain), gcs: Number(vForm.gcs), notes: vForm.notes,
    };
    setVitals((prev) => [v, ...prev]);
    setVForm({ bp: "", hr: "", rr: "", temp: "", spo2: "", pain: "0", gcs: "15", notes: "" });
    setVErrors({});
    showToast(`Tanda vital ${selectedPatient.name} berhasil disimpan`);
  }

  // ── Note submit ───────────────────────────────────────────────────────────
  function submitNote() {
    const e: Record<string, string> = {};
    if (!noteForm.note.trim()) e.note = "Catatan tidak boleh kosong";
    if (Object.keys(e).length) { setNoteErrors(e); return; }

    const n: NursingNote = {
      id: String(Date.now()), patientId: notePatientId, time: nowTime(), nurse: user.fullName,
      shift: noteForm.shift, category: noteForm.category, note: noteForm.note, followUp: noteForm.followUp,
    };
    setNotes((prev) => [n, ...prev]);
    setNoteForm({ category: "Kardiovaskular", shift: "pagi", note: "", followUp: "" });
    setNoteErrors({});
    showToast("Catatan keperawatan berhasil disimpan");
  }

  // ── Obs submit ────────────────────────────────────────────────────────────
  function submitObs() {
    const e: Record<string, string> = {};
    if (!obsForm.generalCondition.trim()) e.generalCondition = "Kondisi umum wajib diisi";
    if (Object.keys(e).length) { setObsErrors(e); return; }

    const entry: ObsEntry = {
      id: String(Date.now()), patientId: obsPatientId, shift: obsForm.shift,
      date: new Date().toLocaleDateString("id-ID"), nurse: user.fullName,
      generalCondition: obsForm.generalCondition, consciousness: obsForm.consciousness,
      diet: obsForm.diet, elimination: obsForm.elimination, mobility: obsForm.mobility,
      pain: obsForm.pain, notes: obsForm.notes,
    };
    setObs((prev) => [entry, ...prev]);
    setObsForm({ shift: "pagi", generalCondition: "", consciousness: "", diet: "", elimination: "", mobility: "", pain: "", notes: "" });
    setObsErrors({});
    showToast("Observasi berhasil dicatat");
  }

  // ── Med toggle ────────────────────────────────────────────────────────────
  function toggleMed(medId: string, idx: number) {
    setMeds((prev) => prev.map((m) => {
      if (m.id !== medId) return m;
      const ns = [...m.status];
      ns[idx] = ns[idx] === "belum" ? "diberikan" : "belum";
      return { ...m, status: ns };
    }));
    showToast("Status pemberian obat diperbarui");
  }

  // ── Derived ───────────────────────────────────────────────────────────────
  const kritisCount  = patients.filter((p) => p.status === "kritis").length;
  const stabil       = patients.filter((p) => p.status === "stabil").length;
  const membaik      = patients.filter((p) => p.status === "membaik").length;
  const obsCount     = patients.filter((p) => p.status === "observasi").length;

  const pendingMeds  = meds.filter((m) => m.status.some((s) => s === "belum")).length;

  const patientVitals   = vitals.filter((v) => v.patientId === selectedPid);
  const patientNotes    = notes.filter((n) => n.patientId === notePatientId);
  const patientMeds     = meds.filter((m) => m.patientId === medPatientId);
  const patientObs      = obs.filter((o) => o.patientId === obsPatientId);

  // ── Patient picker row ────────────────────────────────────────────────────
  function PatientPicker({ value, onChange }: { value: string; onChange: (id: string) => void }) {
    return (
      <div className="flex gap-2 flex-wrap">
        {patients.map((p) => {
          const ts = TRIAGE_STYLE[p.triage];
          const active = value === p.id;
          return (
            <button key={p.id} onClick={() => onChange(p.id)}
              className="flex items-center gap-2 px-3 py-2 rounded-xl border-2 text-left transition-all"
              style={{ background: active ? "#EAF6F8" : "#fff", borderColor: active ? "#0D6E6E" : "#D6E8EE" }}>
              <span className="text-xs font-bold px-1.5 py-0.5 rounded" style={{ background: ts.bg, color: ts.color }}>{p.triage}</span>
              <div>
                <p className="text-xs font-semibold" style={{ color: "#164E63" }}>{p.name}</p>
                <p className="text-xs" style={{ color: "#8CA5B0" }}>{p.room}</p>
              </div>
              {p.alert && <span style={{ color: "#C0362C", fontSize: 14 }}>⚠</span>}
            </button>
          );
        })}
      </div>
    );
  }

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
          <span className="hidden md:inline text-white/40 text-xs">— Perawat / Bidan</span>
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
              <p className="text-white/60 text-xs leading-tight">Perawat — {user.unit}</p>
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
              <p className="text-xs font-semibold uppercase tracking-widest" style={{ color: "#8CA5B0" }}>Keperawatan</p>
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
                  {item.key === "obat" && pendingMeds > 0 && !collapsed && (
                    <span className="ml-auto text-xs font-bold px-1.5 py-0.5 rounded-full text-white"
                      style={{ background: "#C0362C", fontSize: "9px" }}>{pendingMeds}</span>
                  )}
                  {active && !collapsed && <span className="w-1.5 h-1.5 rounded-full ml-auto" style={{ background: "#0D6E6E" }} />}
                </button>
              );
            })}
          </nav>
          {!collapsed && (
            <div className="p-3 border-t mx-2 mb-2 rounded-lg" style={{ borderColor: "#D6E8EE", background: "#F7FBFC" }}>
              <p className="text-xs font-semibold" style={{ color: "#315E6E" }}>{user.fullName}</p>
              <p className="text-xs mt-0.5" style={{ color: "#8CA5B0" }}>Perawat · {user.unit}</p>
            </div>
          )}
        </aside>

        {/* Main */}
        <main className="flex-1 overflow-y-auto" style={{ background: "#F3FAFC" }}>

          {/* ════ DASHBOARD ════ */}
          {view === "dashboard" && (
            <div className="p-6 space-y-5">
              <div>
                <h1 className="text-xl font-bold" style={{ color: "#164E63" }}>Dashboard Bangsal</h1>
                <p className="text-xs mt-0.5" style={{ color: "#66869A" }}>
                  Shift Pagi · {new Date().toLocaleDateString("id-ID", { weekday: "long", day: "2-digit", month: "long", year: "numeric" })}
                </p>
              </div>

              {/* KPIs */}
              <div className="grid grid-cols-2 lg:grid-cols-4 gap-4">
                {[
                  { label: "Total Pasien Bangsal",   val: String(patients.length),  sub: "di bawah perawatan", icon: "🛏", accent: "#0D6E6E", bg: "#EAF6F8" },
                  { label: "Kondisi Kritis",          val: String(kritisCount),       sub: "butuh perhatian khusus", icon: "🚨", accent: "#C0362C", bg: "#FDE7E7" },
                  { label: "Observasi Ketat",         val: String(obsCount),          sub: "dalam pemantauan", icon: "👁",  accent: "#A66700", bg: "#FFF0D5" },
                  { label: "Obat Belum Diberikan",    val: String(pendingMeds),       sub: "item menunggu", icon: "💊", accent: "#A66700", bg: "#FFF0D5" },
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

              {/* Status overview + alerts */}
              <div className="grid grid-cols-3 gap-5">
                {/* Patient list */}
                <div className="col-span-2 rounded-xl shadow-sm overflow-hidden" style={{ background: "#fff", border: "1px solid #D6E8EE" }}>
                  <div className="px-5 py-4 border-b flex items-center justify-between" style={{ borderColor: "#D6E8EE" }}>
                    <h2 className="text-sm font-semibold" style={{ color: "#164E63" }}>Daftar Pasien Bangsal</h2>
                    <div className="flex gap-2 text-xs">
                      {[{ label: "Kritis", count: kritisCount, color: "#C0362C" }, { label: "Observasi", count: obsCount, color: "#A66700" }, { label: "Stabil", count: stabil, color: "#1D5FA8" }, { label: "Membaik", count: membaik, color: "#117A68" }].map((s) => (
                        <span key={s.label} className="flex items-center gap-1 px-2 py-1 rounded-full" style={{ background: "#F7FBFC" }}>
                          <span className="w-2 h-2 rounded-full" style={{ background: s.color }} />
                          <span style={{ color: "#66869A" }}>{s.label} {s.count}</span>
                        </span>
                      ))}
                    </div>
                  </div>
                  <div className="divide-y" style={{ borderColor: "#EEF6F8" }}>
                    {patients.map((p) => {
                      const ts = TRIAGE_STYLE[p.triage];
                      const ss = STATUS_STYLE[p.status];
                      return (
                        <div key={p.id}
                          className="flex items-center gap-4 px-5 py-3 hover:bg-slate-50 transition-colors cursor-pointer"
                          onClick={() => { setSelectedPid(p.id); setView("vitals"); }}>
                          <div className="flex flex-col items-center gap-1 shrink-0">
                            <span className="text-xs font-bold px-1.5 py-0.5 rounded" style={{ background: ts.bg, color: ts.color }}>{p.triage}</span>
                            <span className="text-xs" style={{ color: "#8CA5B0", fontFamily: "var(--font-mono)" }}>{p.room}</span>
                          </div>
                          <div className="flex-1 min-w-0">
                            <div className="flex items-center gap-2">
                              <p className="text-sm font-semibold" style={{ color: "#164E63" }}>{p.name}</p>
                              {p.alert && <span className="text-xs px-1.5 py-0.5 rounded font-medium" style={{ background: "#FDE7E7", color: "#C0362C" }}>⚠ Alert</span>}
                            </div>
                            <p className="text-xs truncate" style={{ color: "#66869A" }}>{p.diagnosis} · {p.days} hr rawat · {p.doctor}</p>
                          </div>
                          <div className="flex items-center gap-2 shrink-0">
                            <span className="flex items-center gap-1 text-xs font-medium">
                              <span className="w-1.5 h-1.5 rounded-full" style={{ background: ss.dot }} />
                              <span style={{ color: ss.color }}>{ss.label}</span>
                            </span>
                            {p.lastVital && <span className="text-xs px-2 py-0.5 rounded" style={{ background: "#EEF6F8", color: "#8CA5B0" }}>TTV {p.lastVital}</span>}
                          </div>
                        </div>
                      );
                    })}
                  </div>
                </div>

                {/* Alert & recent vitals */}
                <div className="space-y-4">
                  {/* Alerts */}
                  <div className="rounded-xl shadow-sm overflow-hidden" style={{ background: "#fff", border: "1px solid #D6E8EE" }}>
                    <div className="px-4 py-3 border-b" style={{ borderColor: "#D6E8EE", background: "#FFF0D5" }}>
                      <h3 className="text-xs font-bold" style={{ color: "#A66700" }}>⚠ Peringatan Aktif</h3>
                    </div>
                    <div className="divide-y" style={{ borderColor: "#EEF6F8" }}>
                      {patients.filter((p) => p.alert).map((p) => (
                        <div key={p.id} className="p-3">
                          <p className="text-xs font-semibold" style={{ color: "#164E63" }}>{p.name}</p>
                          <p className="text-xs mt-0.5" style={{ color: "#C0362C" }}>{p.alert}</p>
                          <p className="text-xs mt-0.5" style={{ color: "#8CA5B0" }}>{p.room}</p>
                        </div>
                      ))}
                      {patients.filter((p) => p.alert).length === 0 && (
                        <p className="p-4 text-xs text-center" style={{ color: "#8CA5B0" }}>Tidak ada peringatan</p>
                      )}
                    </div>
                  </div>

                  {/* Pending meds */}
                  <div className="rounded-xl shadow-sm overflow-hidden" style={{ background: "#fff", border: "1px solid #D6E8EE" }}>
                    <div className="px-4 py-3 border-b" style={{ borderColor: "#D6E8EE" }}>
                      <h3 className="text-xs font-bold" style={{ color: "#164E63" }}>💊 Obat Belum Diberikan</h3>
                    </div>
                    <div className="divide-y" style={{ borderColor: "#EEF6F8" }}>
                      {meds.filter((m) => m.status.some((s) => s === "belum")).map((m) => {
                        const patient = patients.find((p) => p.id === m.patientId);
                        const pendingIdx = m.status.map((s, i) => s === "belum" ? i : -1).filter((i) => i !== -1);
                        return (
                          <div key={m.id} className="p-3">
                            <p className="text-xs font-semibold" style={{ color: "#164E63" }}>{m.drug} {m.dose}</p>
                            <p className="text-xs" style={{ color: "#66869A" }}>{patient?.name} · {m.times[pendingIdx[0]]} · {m.route}</p>
                          </div>
                        );
                      })}
                    </div>
                    <div className="px-3 pb-3">
                      <button onClick={() => setView("obat")} className="w-full text-xs py-1.5 rounded-lg border font-medium"
                        style={{ borderColor: "#D6E8EE", color: "#0D6E6E" }}>Kelola Pemberian Obat →</button>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          )}

          {/* ════ TANDA VITAL ════ */}
          {view === "vitals" && (
            <div className="p-6 space-y-5">
              <div>
                <h1 className="text-xl font-bold" style={{ color: "#164E63" }}>Pengukuran Tanda Vital</h1>
                <p className="text-xs mt-0.5" style={{ color: "#66869A" }}>Pilih pasien, masukkan hasil pengukuran</p>
              </div>

              {/* Patient picker */}
              <div className="rounded-xl p-4 shadow-sm" style={{ background: "#fff", border: "1px solid #D6E8EE" }}>
                <p className="text-xs font-semibold mb-3" style={{ color: "#315E6E" }}>Pilih Pasien</p>
                <PatientPicker value={selectedPid} onChange={setSelectedPid} />
              </div>

              <div className="grid grid-cols-5 gap-5">
                {/* Form */}
                <div className="col-span-2 rounded-xl p-5 shadow-sm space-y-4" style={{ background: "#fff", border: "1px solid #D6E8EE" }}>
                  <div>
                    <h2 className="text-sm font-semibold" style={{ color: "#164E63" }}>Input Tanda Vital</h2>
                    <p className="text-xs mt-0.5" style={{ color: "#66869A" }}>{selectedPatient?.name} · {selectedPatient?.room}</p>
                  </div>

                  {([
                    { key: "bp",   label: "Tekanan Darah (TD)", placeholder: "120/80", unit: "mmHg", hint: "Format: sistolik/diastolik" },
                    { key: "hr",   label: "Nadi (HR)", placeholder: "80", unit: "bpm", hint: "30–250" },
                    { key: "rr",   label: "Frekuensi Napas (RR)", placeholder: "18", unit: "x/mnt", hint: "5–60" },
                    { key: "temp", label: "Suhu Tubuh", placeholder: "36.5", unit: "°C", hint: "35.0–42.0" },
                    { key: "spo2", label: "Saturasi O₂ (SpO₂)", placeholder: "98", unit: "%", hint: "50–100" },
                  ] as const).map((f) => (
                    <div key={f.key}>
                      <label className="text-xs font-semibold block mb-1" style={{ color: "#315E6E" }}>{f.label} *</label>
                      <div className="relative">
                        <input className="w-full border rounded-lg px-3 py-2.5 pr-14 text-sm outline-none"
                          style={{ borderColor: vErrors[f.key] ? "#C0362C" : "#D6E8EE", color: "#315E6E", fontFamily: "var(--font-mono)" }}
                          placeholder={f.placeholder}
                          value={vForm[f.key as keyof typeof vForm]}
                          onChange={(e) => { setVForm((v) => ({ ...v, [f.key]: e.target.value })); setVErrors((ev) => ({ ...ev, [f.key]: "" })); }} />
                        <span className="absolute right-3 top-1/2 -translate-y-1/2 text-xs" style={{ color: "#8CA5B0" }}>{f.unit}</span>
                      </div>
                      {vErrors[f.key] && <p className="text-xs mt-0.5" style={{ color: "#C0362C" }}>⚠ {vErrors[f.key]}</p>}
                    </div>
                  ))}

                  <div className="grid grid-cols-2 gap-3">
                    <div>
                      <label className="text-xs font-semibold block mb-1" style={{ color: "#315E6E" }}>Skala Nyeri (0–10)</label>
                      <select className="w-full border rounded-lg px-3 py-2.5 text-sm outline-none"
                        style={{ borderColor: "#D6E8EE", color: "#315E6E", background: "#fff" }}
                        value={vForm.pain} onChange={(e) => setVForm((v) => ({ ...v, pain: e.target.value }))}>
                        {Array.from({ length: 11 }, (_, i) => <option key={i} value={i}>{i}{i === 0 ? " (Tidak Nyeri)" : i >= 8 ? " (Berat)" : ""}</option>)}
                      </select>
                    </div>
                    <div>
                      <label className="text-xs font-semibold block mb-1" style={{ color: "#315E6E" }}>GCS (3–15)</label>
                      <input type="number" min={3} max={15} className="w-full border rounded-lg px-3 py-2.5 text-sm outline-none"
                        style={{ borderColor: "#D6E8EE", color: "#315E6E", fontFamily: "var(--font-mono)" }}
                        value={vForm.gcs} onChange={(e) => setVForm((v) => ({ ...v, gcs: e.target.value }))} />
                    </div>
                  </div>

                  <div>
                    <label className="text-xs font-semibold block mb-1" style={{ color: "#315E6E" }}>Catatan Tambahan</label>
                    <textarea rows={2} className="w-full border rounded-lg px-3 py-2.5 text-sm outline-none resize-none"
                      style={{ borderColor: "#D6E8EE", color: "#315E6E" }}
                      placeholder="Kondisi tambahan yang perlu dicatat..."
                      value={vForm.notes} onChange={(e) => setVForm((v) => ({ ...v, notes: e.target.value }))} />
                  </div>

                  <button onClick={submitVital} className="w-full py-2.5 rounded-lg text-sm font-semibold text-white"
                    style={{ background: "#0D6E6E" }}>
                    💓 Simpan Tanda Vital
                  </button>
                </div>

                {/* History */}
                <div className="col-span-3 space-y-4">
                  {/* Latest vitals card */}
                  {patientVitals[0] && (
                    <div className="rounded-xl p-5 shadow-sm" style={{ background: "#fff", border: "1px solid #D6E8EE" }}>
                      <div className="flex items-center justify-between mb-4">
                        <h3 className="text-sm font-semibold" style={{ color: "#164E63" }}>Tanda Vital Terkini</h3>
                        <span className="text-xs px-2 py-0.5 rounded-full" style={{ background: "#D9F3EA", color: "#117A68" }}>
                          Diukur {patientVitals[0].time} · {patientVitals[0].nurse}
                        </span>
                      </div>
                      <div className="grid grid-cols-3 gap-4">
                        {[
                          { label: "Tekanan Darah", val: patientVitals[0].bp, unit: "mmHg", icon: "🫀", warn: false },
                          { label: "Nadi", val: String(patientVitals[0].hr), unit: "bpm", icon: "💓", warn: patientVitals[0].hr > 100 || patientVitals[0].hr < 60 },
                          { label: "Pernapasan", val: String(patientVitals[0].rr), unit: "x/mnt", icon: "🌬", warn: patientVitals[0].rr > 20 },
                          { label: "Suhu", val: String(patientVitals[0].temp), unit: "°C", icon: "🌡", warn: patientVitals[0].temp > 37.5 },
                          { label: "SpO₂", val: String(patientVitals[0].spo2), unit: "%", icon: "🩸", warn: patientVitals[0].spo2 < 95 },
                          { label: "Skala Nyeri", val: String(patientVitals[0].pain), unit: "/10", icon: "😣", warn: patientVitals[0].pain >= 7 },
                        ].map((v) => (
                          <div key={v.label} className="rounded-lg p-3 text-center" style={{ background: v.warn ? "#FFF0D5" : "#F7FBFC" }}>
                            <p className="text-lg mb-1">{v.icon}</p>
                            <p className="text-xl font-bold" style={{ fontFamily: "var(--font-mono)", color: v.warn ? "#A66700" : "#0D6E6E" }}>{v.val}</p>
                            <p className="text-xs" style={{ color: "#8CA5B0" }}>{v.unit}</p>
                            <p className="text-xs mt-0.5 font-medium" style={{ color: "#66869A" }}>{v.label}</p>
                            {v.warn && <p className="text-xs mt-0.5 font-bold" style={{ color: "#C0362C" }}>⚠ Abnormal</p>}
                          </div>
                        ))}
                      </div>
                      {patientVitals[0].notes && (
                        <p className="mt-3 text-xs p-2 rounded-lg" style={{ background: "#EEF6F8", color: "#66869A" }}>📝 {patientVitals[0].notes}</p>
                      )}
                    </div>
                  )}

                  {/* History table */}
                  <div className="rounded-xl shadow-sm overflow-hidden" style={{ background: "#fff", border: "1px solid #D6E8EE" }}>
                    <div className="px-5 py-4 border-b" style={{ borderColor: "#D6E8EE" }}>
                      <h3 className="text-sm font-semibold" style={{ color: "#164E63" }}>Riwayat Tanda Vital</h3>
                    </div>
                    {patientVitals.length > 0 ? (
                      <table className="w-full text-sm">
                        <thead>
                          <tr style={{ background: "#F7FBFC", borderBottom: "1px solid #D6E8EE" }}>
                            {["WAKTU", "TD", "HR", "RR", "SUHU", "SPO₂", "NYERI", "GCS", "PERAWAT"].map((h) => (
                              <th key={h} className="text-left text-xs font-semibold px-3 py-3" style={{ color: "#66869A" }}>{h}</th>
                            ))}
                          </tr>
                        </thead>
                        <tbody className="divide-y" style={{ borderColor: "#EEF6F8" }}>
                          {patientVitals.map((v) => (
                            <tr key={v.id} className="hover:bg-slate-50">
                              <td className="px-3 py-2.5 text-xs" style={{ fontFamily: "var(--font-mono)", color: "#66869A" }}>{v.time}</td>
                              <td className="px-3 py-2.5 text-xs font-medium" style={{ fontFamily: "var(--font-mono)", color: "#315E6E" }}>{v.bp}</td>
                              <td className="px-3 py-2.5 text-xs" style={{ fontFamily: "var(--font-mono)", color: (v.hr > 100 || v.hr < 60) ? "#C0362C" : "#315E6E" }}>{v.hr}</td>
                              <td className="px-3 py-2.5 text-xs" style={{ fontFamily: "var(--font-mono)", color: v.rr > 20 ? "#C0362C" : "#315E6E" }}>{v.rr}</td>
                              <td className="px-3 py-2.5 text-xs" style={{ fontFamily: "var(--font-mono)", color: v.temp > 37.5 ? "#C0362C" : "#315E6E" }}>{v.temp}</td>
                              <td className="px-3 py-2.5 text-xs" style={{ fontFamily: "var(--font-mono)", color: v.spo2 < 95 ? "#C0362C" : "#315E6E" }}>{v.spo2}%</td>
                              <td className="px-3 py-2.5 text-xs text-center" style={{ color: v.pain >= 7 ? "#C0362C" : "#315E6E" }}>{v.pain}/10</td>
                              <td className="px-3 py-2.5 text-xs text-center" style={{ color: v.gcs < 12 ? "#C0362C" : "#315E6E" }}>{v.gcs}</td>
                              <td className="px-3 py-2.5 text-xs" style={{ color: "#8CA5B0" }}>{v.nurse}</td>
                            </tr>
                          ))}
                        </tbody>
                      </table>
                    ) : (
                      <p className="py-8 text-center text-sm" style={{ color: "#8CA5B0" }}>Belum ada data tanda vital untuk pasien ini</p>
                    )}
                  </div>
                </div>
              </div>
            </div>
          )}

          {/* ════ CATATAN KEPERAWATAN ════ */}
          {view === "catatan" && (
            <div className="p-6 space-y-5">
              <div>
                <h1 className="text-xl font-bold" style={{ color: "#164E63" }}>Catatan Keperawatan</h1>
                <p className="text-xs mt-0.5" style={{ color: "#66869A" }}>Dokumentasi asuhan keperawatan berdasarkan SBAR / SOAP</p>
              </div>

              {/* Patient picker */}
              <div className="rounded-xl p-4 shadow-sm" style={{ background: "#fff", border: "1px solid #D6E8EE" }}>
                <p className="text-xs font-semibold mb-3" style={{ color: "#315E6E" }}>Pilih Pasien</p>
                <PatientPicker value={notePatientId} onChange={setNotePatientId} />
              </div>

              <div className="grid grid-cols-5 gap-5">
                {/* Note form */}
                <div className="col-span-2 rounded-xl p-5 shadow-sm space-y-4" style={{ background: "#fff", border: "1px solid #D6E8EE" }}>
                  <h2 className="text-sm font-semibold" style={{ color: "#164E63" }}>Tambah Catatan Baru</h2>

                  <div>
                    <label className="text-xs font-semibold block mb-1.5" style={{ color: "#315E6E" }}>Shift</label>
                    <div className="grid grid-cols-3 gap-1.5">
                      {(["pagi", "siang", "malam"] as ObsShift[]).map((s) => (
                        <button key={s} onClick={() => setNoteForm((f) => ({ ...f, shift: s }))}
                          className="py-2 rounded-lg text-xs font-semibold border-2 capitalize"
                          style={{ background: noteForm.shift === s ? "#EAF6F8" : "#F7FBFC", color: noteForm.shift === s ? "#0D6E6E" : "#66869A", borderColor: noteForm.shift === s ? "#0D6E6E" : "#D6E8EE" }}>
                          {s}
                        </button>
                      ))}
                    </div>
                  </div>

                  <div>
                    <label className="text-xs font-semibold block mb-1.5" style={{ color: "#315E6E" }}>Kategori</label>
                    <select className="w-full border rounded-lg px-3 py-2.5 text-sm outline-none"
                      style={{ borderColor: "#D6E8EE", color: "#315E6E", background: "#fff" }}
                      value={noteForm.category} onChange={(e) => setNoteForm((f) => ({ ...f, category: e.target.value }))}>
                      {NOTE_CATS.map((c) => <option key={c}>{c}</option>)}
                    </select>
                  </div>

                  <div>
                    <label className="text-xs font-semibold block mb-1.5" style={{ color: "#315E6E" }}>Catatan Keperawatan *</label>
                    <textarea rows={5} className="w-full border rounded-lg px-3 py-2.5 text-sm outline-none resize-none"
                      style={{ borderColor: noteErrors.note ? "#C0362C" : "#D6E8EE", color: "#315E6E" }}
                      placeholder="Tuliskan observasi, intervensi, dan respons pasien..."
                      value={noteForm.note} onChange={(e) => { setNoteForm((f) => ({ ...f, note: e.target.value })); setNoteErrors((er) => ({ ...er, note: "" })); }} />
                    {noteErrors.note && <p className="text-xs mt-0.5" style={{ color: "#C0362C" }}>⚠ {noteErrors.note}</p>}
                  </div>

                  <div>
                    <label className="text-xs font-semibold block mb-1.5" style={{ color: "#315E6E" }}>Tindak Lanjut / Rencana</label>
                    <textarea rows={2} className="w-full border rounded-lg px-3 py-2.5 text-sm outline-none resize-none"
                      style={{ borderColor: "#D6E8EE", color: "#315E6E" }}
                      placeholder="Rencana asuhan keperawatan berikutnya..."
                      value={noteForm.followUp} onChange={(e) => setNoteForm((f) => ({ ...f, followUp: e.target.value }))} />
                  </div>

                  <button onClick={submitNote} className="w-full py-2.5 rounded-lg text-sm font-semibold text-white"
                    style={{ background: "#0D6E6E" }}>
                    📋 Simpan Catatan
                  </button>
                </div>

                {/* Notes list */}
                <div className="col-span-3 space-y-3">
                  {patientNotes.length > 0 ? patientNotes.map((n) => (
                    <div key={n.id} className="rounded-xl p-4 shadow-sm" style={{ background: "#fff", border: "1px solid #D6E8EE" }}>
                      <div className="flex items-start justify-between gap-3 mb-2">
                        <div className="flex items-center gap-2">
                          <span className="text-xs px-2 py-0.5 rounded-full font-medium text-white capitalize"
                            style={{ background: n.shift === "pagi" ? "#0D6E6E" : n.shift === "siang" ? "#A66700" : "#164E63" }}>
                            {SHIFT_LABEL[n.shift]}
                          </span>
                          <span className="text-xs px-2 py-0.5 rounded-full font-medium" style={{ background: "#EAF6F8", color: "#0D6E6E" }}>{n.category}</span>
                        </div>
                        <div className="text-right shrink-0">
                          <p className="text-xs" style={{ fontFamily: "var(--font-mono)", color: "#8CA5B0" }}>{n.time}</p>
                          <p className="text-xs" style={{ color: "#66869A" }}>{n.nurse}</p>
                        </div>
                      </div>
                      <p className="text-sm" style={{ color: "#315E6E" }}>{n.note}</p>
                      {n.followUp && (
                        <div className="mt-2 p-2 rounded-lg" style={{ background: "#F7FBFC" }}>
                          <p className="text-xs font-semibold mb-0.5" style={{ color: "#66869A" }}>Tindak Lanjut:</p>
                          <p className="text-xs" style={{ color: "#456D7D" }}>{n.followUp}</p>
                        </div>
                      )}
                    </div>
                  )) : (
                    <div className="rounded-xl p-8 text-center shadow-sm" style={{ background: "#fff", border: "1px solid #D6E8EE" }}>
                      <p className="text-sm" style={{ color: "#8CA5B0" }}>Belum ada catatan untuk pasien ini</p>
                    </div>
                  )}
                </div>
              </div>
            </div>
          )}

          {/* ════ PEMBERIAN OBAT ════ */}
          {view === "obat" && (
            <div className="p-6 space-y-5">
              <div>
                <h1 className="text-xl font-bold" style={{ color: "#164E63" }}>Pemberian Obat</h1>
                <p className="text-xs mt-0.5" style={{ color: "#66869A" }}>Konfirmasi pemberian setiap obat sesuai resep dokter</p>
              </div>

              {/* Patient picker */}
              <div className="rounded-xl p-4 shadow-sm" style={{ background: "#fff", border: "1px solid #D6E8EE" }}>
                <p className="text-xs font-semibold mb-3" style={{ color: "#315E6E" }}>Pilih Pasien</p>
                <PatientPicker value={medPatientId} onChange={setMedPatientId} />
              </div>

              {/* Medication cards */}
              <div className="space-y-3">
                {patientMeds.length > 0 ? patientMeds.map((m) => (
                  <div key={m.id} className="rounded-xl p-5 shadow-sm" style={{ background: "#fff", border: "1px solid #D6E8EE" }}>
                    <div className="flex items-start justify-between mb-3">
                      <div>
                        <div className="flex items-center gap-2">
                          <p className="text-sm font-bold" style={{ color: "#164E63" }}>{m.drug}</p>
                          <span className="text-xs px-1.5 py-0.5 rounded font-mono font-medium" style={{ background: "#EAF6F8", color: "#0D6E6E" }}>{m.dose}</span>
                          <span className="text-xs px-1.5 py-0.5 rounded font-medium" style={{ background: "#EEF6F8", color: "#456D7D" }}>{m.route}</span>
                        </div>
                        <p className="text-xs mt-0.5" style={{ color: "#66869A" }}>{m.frequency} · Diresepkan oleh {m.prescribedBy}</p>
                      </div>
                      <span className="text-xs px-2 py-0.5 rounded-full font-medium"
                        style={{ background: m.status.every((s) => s === "diberikan") ? "#D9F3EA" : "#FFF0D5", color: m.status.every((s) => s === "diberikan") ? "#117A68" : "#A66700" }}>
                        {m.status.filter((s) => s === "diberikan").length}/{m.status.length} terpenuhi
                      </span>
                    </div>
                    <div className="flex gap-3 flex-wrap">
                      {m.times.map((t, idx) => {
                        const ms = MED_STYLE[m.status[idx]];
                        return (
                          <button key={idx} onClick={() => toggleMed(m.id, idx)}
                            className="flex items-center gap-2 px-4 py-2.5 rounded-xl border-2 font-semibold text-sm transition-all hover:shadow-sm"
                            style={{ background: ms.bg, borderColor: m.status[idx] === "diberikan" ? "#86efac" : "#D6E8EE", color: ms.color }}>
                            <span style={{ fontFamily: "var(--font-mono)", fontSize: "11px" }}>{t}</span>
                            <span className="text-xs">{ms.label}</span>
                          </button>
                        );
                      })}
                    </div>
                    {m.status.some((s) => s === "belum") && (
                      <p className="mt-2 text-xs" style={{ color: "#A66700" }}>
                        ⚠ {m.status.filter((s) => s === "belum").length} dosis belum dikonfirmasi — klik tombol waktu untuk update
                      </p>
                    )}
                  </div>
                )) : (
                  <div className="rounded-xl p-8 text-center shadow-sm" style={{ background: "#fff", border: "1px solid #D6E8EE" }}>
                    <p className="text-sm" style={{ color: "#8CA5B0" }}>Tidak ada resep obat aktif untuk pasien ini</p>
                  </div>
                )}
              </div>

              {/* Legend */}
              <div className="flex gap-4 flex-wrap">
                {(Object.entries(MED_STYLE) as [MedStatus, typeof MED_STYLE[MedStatus]][]).map(([k, s]) => (
                  <span key={k} className="flex items-center gap-1.5 text-xs">
                    <span className="w-3 h-3 rounded-sm" style={{ background: s.bg, border: `1px solid ${s.color}` }} />
                    <span style={{ color: "#66869A" }}>{s.label} = {k}</span>
                  </span>
                ))}
              </div>
            </div>
          )}

          {/* ════ OBSERVASI ════ */}
          {view === "observasi" && (
            <div className="p-6 space-y-5">
              <div>
                <h1 className="text-xl font-bold" style={{ color: "#164E63" }}>Observasi Pasien</h1>
                <p className="text-xs mt-0.5" style={{ color: "#66869A" }}>Formulir observasi komprehensif per shift keperawatan</p>
              </div>

              {/* Patient picker */}
              <div className="rounded-xl p-4 shadow-sm" style={{ background: "#fff", border: "1px solid #D6E8EE" }}>
                <p className="text-xs font-semibold mb-3" style={{ color: "#315E6E" }}>Pilih Pasien</p>
                <PatientPicker value={obsPatientId} onChange={setObsPatientId} />
              </div>

              <div className="grid grid-cols-5 gap-5">
                {/* Form */}
                <div className="col-span-2 rounded-xl p-5 shadow-sm space-y-4" style={{ background: "#fff", border: "1px solid #D6E8EE" }}>
                  <h2 className="text-sm font-semibold" style={{ color: "#164E63" }}>Form Observasi Shift</h2>

                  <div>
                    <label className="text-xs font-semibold block mb-1.5" style={{ color: "#315E6E" }}>Shift</label>
                    <div className="grid grid-cols-3 gap-1.5">
                      {(["pagi", "siang", "malam"] as ObsShift[]).map((s) => (
                        <button key={s} onClick={() => setObsForm((f) => ({ ...f, shift: s }))}
                          className="py-2 rounded-lg text-xs font-semibold border-2 capitalize"
                          style={{ background: obsForm.shift === s ? "#EAF6F8" : "#F7FBFC", color: obsForm.shift === s ? "#0D6E6E" : "#66869A", borderColor: obsForm.shift === s ? "#0D6E6E" : "#D6E8EE" }}>
                          {s}
                        </button>
                      ))}
                    </div>
                  </div>

                  {([
                    { key: "generalCondition", label: "Kondisi Umum *", placeholder: "Mis. Lemah, composmentis, tampak sesak" },
                    { key: "consciousness",    label: "Kesadaran",       placeholder: "Mis. Composmentis, GCS 15" },
                    { key: "diet",             label: "Nutrisi & Diit", placeholder: "Mis. Habis 3/4 porsi, diit lunak" },
                    { key: "elimination",      label: "Eliminasi",       placeholder: "Mis. BAK 400 mL/6 jam, BAB 1x" },
                    { key: "mobility",         label: "Mobilisasi",      placeholder: "Mis. Bed rest dengan bantuan" },
                    { key: "pain",             label: "Nyeri",           placeholder: "Mis. Skala 2/10, nyeri perut kanan" },
                  ] as const).map((f) => (
                    <div key={f.key}>
                      <label className="text-xs font-semibold block mb-1" style={{ color: "#315E6E" }}>{f.label}</label>
                      <input className="w-full border rounded-lg px-3 py-2.5 text-sm outline-none"
                        style={{ borderColor: obsErrors[f.key] ? "#C0362C" : "#D6E8EE", color: "#315E6E" }}
                        placeholder={f.placeholder}
                        value={obsForm[f.key as keyof typeof obsForm]}
                        onChange={(e) => { setObsForm((v) => ({ ...v, [f.key]: e.target.value })); setObsErrors((er) => ({ ...er, [f.key]: "" })); }} />
                      {obsErrors[f.key] && <p className="text-xs mt-0.5" style={{ color: "#C0362C" }}>⚠ {obsErrors[f.key]}</p>}
                    </div>
                  ))}

                  <div>
                    <label className="text-xs font-semibold block mb-1" style={{ color: "#315E6E" }}>Catatan Tambahan Shift</label>
                    <textarea rows={3} className="w-full border rounded-lg px-3 py-2.5 text-sm outline-none resize-none"
                      style={{ borderColor: "#D6E8EE", color: "#315E6E" }}
                      placeholder="Hal penting lain yang perlu dilaporkan..."
                      value={obsForm.notes} onChange={(e) => setObsForm((v) => ({ ...v, notes: e.target.value }))} />
                  </div>

                  <button onClick={submitObs} className="w-full py-2.5 rounded-lg text-sm font-semibold text-white"
                    style={{ background: "#0D6E6E" }}>
                    👁 Simpan Observasi Shift
                  </button>
                </div>

                {/* Obs history */}
                <div className="col-span-3 space-y-3">
                  {patientObs.length > 0 ? patientObs.map((o) => (
                    <div key={o.id} className="rounded-xl p-5 shadow-sm" style={{ background: "#fff", border: "1px solid #D6E8EE" }}>
                      <div className="flex items-center justify-between mb-4">
                        <div className="flex items-center gap-2">
                          <span className="text-xs px-2 py-0.5 rounded-full font-medium text-white capitalize"
                            style={{ background: o.shift === "pagi" ? "#0D6E6E" : o.shift === "siang" ? "#A66700" : "#164E63" }}>
                            {SHIFT_LABEL[o.shift]}
                          </span>
                          <span className="text-xs" style={{ color: "#8CA5B0" }}>{o.date}</span>
                        </div>
                        <p className="text-xs" style={{ color: "#66869A" }}>{o.nurse}</p>
                      </div>
                      <div className="grid grid-cols-2 gap-3">
                        {[
                          { label: "Kondisi Umum", val: o.generalCondition, icon: "🧍" },
                          { label: "Kesadaran",    val: o.consciousness,    icon: "🧠" },
                          { label: "Nutrisi/Diit", val: o.diet,             icon: "🍱" },
                          { label: "Eliminasi",    val: o.elimination,      icon: "💧" },
                          { label: "Mobilisasi",   val: o.mobility,         icon: "🚶" },
                          { label: "Nyeri",        val: o.pain,             icon: "😣" },
                        ].filter((r) => r.val).map((r) => (
                          <div key={r.label} className="p-2 rounded-lg" style={{ background: "#F7FBFC" }}>
                            <p className="text-xs font-semibold mb-0.5" style={{ color: "#66869A" }}>{r.icon} {r.label}</p>
                            <p className="text-xs" style={{ color: "#315E6E" }}>{r.val}</p>
                          </div>
                        ))}
                      </div>
                      {o.notes && (
                        <p className="mt-3 text-xs p-2 rounded-lg" style={{ background: "#FFF0D5", color: "#A66700" }}>📝 {o.notes}</p>
                      )}
                    </div>
                  )) : (
                    <div className="rounded-xl p-8 text-center shadow-sm" style={{ background: "#fff", border: "1px solid #D6E8EE" }}>
                      <p className="text-sm" style={{ color: "#8CA5B0" }}>Belum ada observasi shift untuk pasien ini</p>
                    </div>
                  )}
                </div>
              </div>
            </div>
          )}
        </main>
      </div>

      {/* Toast */}
      {toast && (
        <div className="fixed bottom-5 right-5 px-4 py-3 rounded-lg shadow-lg text-sm font-medium text-white z-50"
          style={{ background: "#0D6E6E" }}>{toast}</div>
      )}
    </div>
  );
}
