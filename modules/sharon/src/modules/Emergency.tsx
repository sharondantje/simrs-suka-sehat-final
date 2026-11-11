import { useState } from "react";

type TriageLevel = "P1" | "P2" | "P3" | "P4";
type PatientStatus = "triase" | "pemeriksaan" | "observasi" | "selesai" | "dipulangkan";

interface Patient {
  id: string;
  rm: string;
  name: string;
  age: number;
  gender: string;
  arrivalTime: string;
  chiefComplaint: string;
  triageLevel: TriageLevel | null;
  status: PatientStatus;
  bp: string;
  pulse: number;
  spo2: number;
  temp: number;
  diagnosis: string;
  icd10: string;
  actions: string[];
}

const TRIAGE_COLOR: Record<TriageLevel, { bg: string; text: string; label: string }> = {
  P1: { bg: "#FDE7E7", text: "#C0362C", label: "P1 — Merah (Kritis)" },
  P2: { bg: "#FFF0D5", text: "#A66700", label: "P2 — Kuning (Segera)" },
  P3: { bg: "#D9F3EA", text: "#117A68", label: "P3 — Hijau (Tidak Mendesak)" },
  P4: { bg: "#E1EEFB", text: "#1D5FA8", label: "P4 — Hitam/Biru (Meninggal/Expectant)" },
};

const STATUS_BADGE: Record<PatientStatus, { cls: string; label: string }> = {
  triase: { cls: "badge-yellow", label: "Triase" },
  pemeriksaan: { cls: "badge-blue", label: "Pemeriksaan" },
  observasi: { cls: "badge-yellow", label: "Observasi" },
  selesai: { cls: "badge-green", label: "Selesai" },
  dipulangkan: { cls: "badge-green", label: "Dipulangkan" },
};

const INITIAL_PATIENTS: Patient[] = [
  {
    id: "1", rm: "RM-2026-000105", name: "Budi Santoso", age: 52, gender: "L",
    arrivalTime: "08:14", chiefComplaint: "Nyeri dada berat, sesak napas",
    triageLevel: "P1", status: "pemeriksaan",
    bp: "160/100", pulse: 112, spo2: 92, temp: 37.2,
    diagnosis: "Suspek STEMI", icd10: "I21.9", actions: ["EKG 12 lead", "O2 nasal canule 4L/m"],
  },
  {
    id: "2", rm: "RM-2026-000106", name: "Siti Rahayu", age: 34, gender: "P",
    arrivalTime: "08:31", chiefComplaint: "Kecelakaan lalu lintas, luka robek kepala",
    triageLevel: "P2", status: "triase",
    bp: "110/70", pulse: 98, spo2: 97, temp: 36.8,
    diagnosis: "", icd10: "", actions: [],
  },
  {
    id: "3", rm: "RM-2026-000107", name: "Ahmad Wijaya", age: 67, gender: "L",
    arrivalTime: "09:05", chiefComplaint: "Penurunan kesadaran mendadak",
    triageLevel: "P1", status: "observasi",
    bp: "180/110", pulse: 88, spo2: 94, temp: 37.5,
    diagnosis: "Stroke Hemoragik", icd10: "I61.9", actions: ["CT Scan kepala", "Infus NaCl 0.9%"],
  },
  {
    id: "4", rm: "RM-2026-000108", name: "Dewi Kusuma", age: 28, gender: "P",
    arrivalTime: "09:22", chiefComplaint: "Mual, muntah, demam",
    triageLevel: "P3", status: "triase",
    bp: "100/65", pulse: 92, spo2: 99, temp: 38.9,
    diagnosis: "", icd10: "", actions: [],
  },
  {
    id: "5", rm: "RM-2026-000109", name: "Rudi Hermawan", age: 45, gender: "L",
    arrivalTime: "09:45", chiefComplaint: "Luka bakar tangan kanan",
    triageLevel: "P2", status: "selesai",
    bp: "120/80", pulse: 82, spo2: 98, temp: 36.9,
    diagnosis: "Combustio Gr II Manus Dextra", icd10: "T23.1", actions: ["Wound care", "Analgetik"],
  },
];

type ActiveView = "list" | "triage" | "pemeriksaan" | "observasi" | "icd";

export default function Emergency() {
  const [patients, setPatients] = useState<Patient[]>(INITIAL_PATIENTS);
  const [selectedId, setSelectedId] = useState<string | null>(null);
  const [activeView, setActiveView] = useState<ActiveView>("list");
  const [toast, setToast] = useState<string | null>(null);

  const [triageForm, setTriageForm] = useState({ level: "" as TriageLevel | "", urgency: "", notes: "" });
  const [examForm, setExamForm] = useState({ bp: "", pulse: "", spo2: "", temp: "", diagnosis: "", actions: "" });
  const [obsForm, setObsForm] = useState({ note: "", condition: "", decision: "rawat_inap" as "rawat_inap" | "dipulangkan" });
  const [icdForm, setIcdForm] = useState({ icd10: "", finalDiagnosis: "" });

  const selected = patients.find((p) => p.id === selectedId) ?? null;

  function showToast(msg: string) {
    setToast(msg);
    setTimeout(() => setToast(null), 2500);
  }

  function selectPatient(p: Patient) {
    setSelectedId(p.id);
    setTriageForm({ level: p.triageLevel ?? "", urgency: "", notes: "" });
    setExamForm({ bp: p.bp, pulse: String(p.pulse), spo2: String(p.spo2), temp: String(p.temp), diagnosis: p.diagnosis, actions: p.actions.join(", ") });
    setIcdForm({ icd10: p.icd10, finalDiagnosis: p.diagnosis });
    setActiveView("list");
  }

  function saveTriage() {
    if (!triageForm.level) return;
    setPatients((prev) => prev.map((p) => p.id === selectedId ? { ...p, triageLevel: triageForm.level as TriageLevel, status: "pemeriksaan" } : p));
    showToast("Data triase berhasil disimpan");
    setActiveView("list");
  }

  function saveExam() {
    setPatients((prev) => prev.map((p) => p.id === selectedId ? {
      ...p, bp: examForm.bp, pulse: Number(examForm.pulse), spo2: Number(examForm.spo2),
      temp: Number(examForm.temp), diagnosis: examForm.diagnosis,
      actions: examForm.actions.split(",").map((s) => s.trim()).filter(Boolean),
      status: "observasi",
    } : p));
    showToast("Data pemeriksaan tersimpan");
    setActiveView("list");
  }

  function saveObs() {
    const newStatus: PatientStatus = obsForm.decision === "dipulangkan" ? "dipulangkan" : "selesai";
    setPatients((prev) => prev.map((p) => p.id === selectedId ? { ...p, status: newStatus } : p));
    showToast("Observasi & keputusan pasien tersimpan");
    setActiveView("list");
  }

  function saveIcd() {
    if (!icdForm.icd10.match(/^[A-Z]\d{2}(\.\d)?$/i)) {
      showToast("⚠ Format ICD-10 tidak valid. Contoh: I21.9");
      return;
    }
    setPatients((prev) => prev.map((p) => p.id === selectedId ? { ...p, icd10: icdForm.icd10, diagnosis: icdForm.finalDiagnosis, status: "selesai" } : p));
    showToast("Finalisasi ICD berhasil. Data tersimpan.");
    setActiveView("list");
  }

  const steps: { key: ActiveView; label: string }[] = [
    { key: "triage", label: "Triase" },
    { key: "pemeriksaan", label: "Pemeriksaan & Tindakan" },
    { key: "observasi", label: "Observasi" },
    { key: "icd", label: "Finalisasi ICD" },
  ];

  return (
    <div className="flex h-full">
      {/* Patient list panel */}
      <div className="w-80 shrink-0 border-r flex flex-col" style={{ background: "#fff", borderColor: "#D6E8EE" }}>
        <div className="p-4 border-b" style={{ borderColor: "#D6E8EE" }}>
          <h1 className="text-lg font-bold" style={{ color: "#164E63" }}>IGD / Emergency</h1>
          <p className="text-xs mt-0.5" style={{ color: "#66869A" }}>
            {new Date().toLocaleDateString("id-ID", { weekday: "long", day: "2-digit", month: "long", year: "numeric" })}
          </p>
          <div className="flex gap-2 mt-3">
            {(["P1", "P2", "P3"] as TriageLevel[]).map((l) => (
              <div key={l} className="flex-1 rounded px-1.5 py-1 text-center text-xs font-semibold" style={{ background: TRIAGE_COLOR[l].bg, color: TRIAGE_COLOR[l].text }}>
                {l}: {patients.filter((p) => p.triageLevel === l).length}
              </div>
            ))}
          </div>
        </div>
        <div className="flex-1 overflow-y-auto divide-y" style={{ borderColor: "#D6E8EE" }}>
          {patients.map((p) => {
            const tc = p.triageLevel ? TRIAGE_COLOR[p.triageLevel] : null;
            const isSelected = selectedId === p.id;
            return (
              <div
                key={p.id}
                onClick={() => selectPatient(p)}
                className="p-3 cursor-pointer transition-colors"
                style={{ background: isSelected ? "#EAF6F8" : undefined }}
              >
                <div className="flex items-start justify-between gap-2">
                  <div className="min-w-0">
                    <p className="text-sm font-semibold truncate" style={{ color: "#164E63" }}>{p.name}</p>
                    <p className="text-xs" style={{ fontFamily: "var(--font-mono)", color: "#66869A" }}>{p.rm}</p>
                    <p className="text-xs mt-0.5" style={{ color: "#66869A" }}>{p.age}th • {p.gender} • {p.arrivalTime}</p>
                    <p className="text-xs mt-1 truncate" style={{ color: "#315E6E" }}>{p.chiefComplaint}</p>
                  </div>
                  <div className="shrink-0 flex flex-col items-end gap-1">
                    {tc && (
                      <span className="text-xs font-bold px-1.5 py-0.5 rounded" style={{ background: tc.bg, color: tc.text }}>
                        {p.triageLevel}
                      </span>
                    )}
                    <span className={`text-xs px-1.5 py-0.5 rounded ${STATUS_BADGE[p.status].cls}`}>
                      {STATUS_BADGE[p.status].label}
                    </span>
                  </div>
                </div>
              </div>
            );
          })}
        </div>
        <div className="p-3 border-t" style={{ borderColor: "#D6E8EE" }}>
          <button
            className="w-full py-2 rounded text-sm font-semibold text-white transition-colors"
            style={{ background: "#0D6E6E" }}
          >
            + Daftarkan Pasien Baru
          </button>
        </div>
      </div>

      {/* Detail panel */}
      <div className="flex-1 flex flex-col overflow-hidden">
        {!selected ? (
          <div className="flex-1 flex items-center justify-center" style={{ color: "#8CA5B0" }}>
            <div className="text-center">
              <p className="text-4xl mb-3">🚨</p>
              <p className="font-semibold">Pilih pasien dari daftar</p>
              <p className="text-sm mt-1">untuk melihat detail dan melakukan tindakan</p>
            </div>
          </div>
        ) : (
          <>
            {/* Patient header bar */}
            <div className="px-6 py-4 border-b flex items-center justify-between" style={{ background: "#fff", borderColor: "#D6E8EE" }}>
              <div>
                <div className="flex items-center gap-3">
                  <h2 className="text-lg font-bold" style={{ color: "#164E63" }}>{selected.name}</h2>
                  {selected.triageLevel && (
                    <span className="text-xs font-bold px-2 py-0.5 rounded" style={{ background: TRIAGE_COLOR[selected.triageLevel].bg, color: TRIAGE_COLOR[selected.triageLevel].text }}>
                      {TRIAGE_COLOR[selected.triageLevel].label}
                    </span>
                  )}
                  <span className={`text-xs px-2 py-0.5 rounded font-medium ${STATUS_BADGE[selected.status].cls}`}>
                    {STATUS_BADGE[selected.status].label}
                  </span>
                </div>
                <p className="text-xs mt-0.5" style={{ fontFamily: "var(--font-mono)", color: "#66869A" }}>
                  {selected.rm} • {selected.age} thn • {selected.gender} • Tiba: {selected.arrivalTime}
                </p>
              </div>
              <div className="flex gap-2">
                {steps.map((s) => (
                  <button
                    key={s.key}
                    onClick={() => setActiveView(s.key)}
                    className="text-xs px-3 py-1.5 rounded font-medium transition-colors border"
                    style={{
                      background: activeView === s.key ? "#0D6E6E" : "#fff",
                      color: activeView === s.key ? "#fff" : "#315E6E",
                      borderColor: activeView === s.key ? "#0D6E6E" : "#D6E8EE",
                    }}
                  >
                    {s.label}
                  </button>
                ))}
              </div>
            </div>

            <div className="flex-1 overflow-y-auto p-6">
              {/* Summary cards */}
              {activeView === "list" && (
                <div className="space-y-5">
                  <div className="grid grid-cols-4 gap-4">
                    {[
                      { label: "Tekanan Darah", val: selected.bp || "—", unit: "mmHg" },
                      { label: "Nadi", val: selected.pulse || "—", unit: "bpm" },
                      { label: "SpO₂", val: selected.spo2 ? `${selected.spo2}%` : "—", unit: "" },
                      { label: "Suhu", val: selected.temp ? `${selected.temp}°C` : "—", unit: "" },
                    ].map((v) => (
                      <div key={v.label} className="rounded-lg p-4" style={{ background: "#fff", border: "1px solid #D6E8EE" }}>
                        <p className="text-xs mb-1" style={{ color: "#66869A" }}>{v.label}</p>
                        <p className="text-xl font-bold" style={{ fontFamily: "var(--font-mono)", color: "#0D6E6E" }}>{v.val}</p>
                        {v.unit && <p className="text-xs" style={{ color: "#8CA5B0" }}>{v.unit}</p>}
                      </div>
                    ))}
                  </div>
                  <div className="rounded-lg p-4" style={{ background: "#fff", border: "1px solid #D6E8EE" }}>
                    <h3 className="text-sm font-semibold mb-2" style={{ color: "#164E63" }}>Keluhan Utama</h3>
                    <p className="text-sm" style={{ color: "#315E6E" }}>{selected.chiefComplaint}</p>
                  </div>
                  {selected.diagnosis && (
                    <div className="rounded-lg p-4" style={{ background: "#fff", border: "1px solid #D6E8EE" }}>
                      <h3 className="text-sm font-semibold mb-2" style={{ color: "#164E63" }}>Diagnosis & ICD-10</h3>
                      <div className="flex items-center gap-3">
                        <p className="text-sm flex-1" style={{ color: "#315E6E" }}>{selected.diagnosis}</p>
                        {selected.icd10 && (
                          <span className="text-xs font-bold px-2 py-0.5 rounded" style={{ fontFamily: "var(--font-mono)", background: "#E1EEFB", color: "#1D5FA8" }}>
                            {selected.icd10}
                          </span>
                        )}
                      </div>
                    </div>
                  )}
                  {selected.actions.length > 0 && (
                    <div className="rounded-lg p-4" style={{ background: "#fff", border: "1px solid #D6E8EE" }}>
                      <h3 className="text-sm font-semibold mb-2" style={{ color: "#164E63" }}>Tindakan yang Diberikan</h3>
                      <ul className="space-y-1">
                        {selected.actions.map((a, i) => (
                          <li key={i} className="flex items-center gap-2 text-sm" style={{ color: "#315E6E" }}>
                            <span style={{ color: "#117A68" }}>✓</span> {a}
                          </li>
                        ))}
                      </ul>
                    </div>
                  )}
                </div>
              )}

              {/* Triage form */}
              {activeView === "triage" && (
                <div className="max-w-xl space-y-5">
                  <div className="rounded-lg p-5" style={{ background: "#fff", border: "1px solid #D6E8EE" }}>
                    <h3 className="text-base font-semibold mb-4" style={{ color: "#164E63" }}>Pengelolaan Triase Pasien</h3>
                    <div className="space-y-4">
                      <div>
                        <label className="text-xs font-semibold block mb-1.5" style={{ color: "#315E6E" }}>Level Triase *</label>
                        <div className="grid grid-cols-2 gap-2">
                          {(Object.keys(TRIAGE_COLOR) as TriageLevel[]).map((l) => (
                            <button
                              key={l}
                              onClick={() => setTriageForm((f) => ({ ...f, level: l }))}
                              className="py-2 px-3 rounded text-xs font-semibold text-left border-2 transition-all"
                              style={{
                                background: triageForm.level === l ? TRIAGE_COLOR[l].bg : "#F7FBFC",
                                color: TRIAGE_COLOR[l].text,
                                borderColor: triageForm.level === l ? TRIAGE_COLOR[l].text : "#D6E8EE",
                              }}
                            >
                              {TRIAGE_COLOR[l].label}
                            </button>
                          ))}
                        </div>
                        {!triageForm.level && <p className="text-xs mt-1" style={{ color: "#C0362C" }}>⚠ Level triase wajib dipilih</p>}
                      </div>
                      <div>
                        <label className="text-xs font-semibold block mb-1.5" style={{ color: "#315E6E" }}>Tingkat Kedaruratan</label>
                        <select
                          className="w-full border rounded px-3 py-2 text-sm"
                          style={{ borderColor: "#D6E8EE", color: "#315E6E" }}
                          value={triageForm.urgency}
                          onChange={(e) => setTriageForm((f) => ({ ...f, urgency: e.target.value }))}
                        >
                          <option value="">— Pilih tingkat —</option>
                          <option>Mengancam jiwa</option>
                          <option>Urgent — perlu penanganan segera</option>
                          <option>Tidak mendesak</option>
                          <option>Meninggal / tidak dapat ditolong</option>
                        </select>
                      </div>
                      <div>
                        <label className="text-xs font-semibold block mb-1.5" style={{ color: "#315E6E" }}>Catatan Triase</label>
                        <textarea
                          rows={3}
                          className="w-full border rounded px-3 py-2 text-sm resize-none"
                          style={{ borderColor: "#D6E8EE", color: "#315E6E" }}
                          placeholder="Keterangan tambahan triase..."
                          value={triageForm.notes}
                          onChange={(e) => setTriageForm((f) => ({ ...f, notes: e.target.value }))}
                        />
                      </div>
                    </div>
                    <div className="flex justify-end gap-2 mt-5">
                      <button onClick={() => setActiveView("list")} className="px-4 py-2 text-sm rounded border" style={{ borderColor: "#D6E8EE", color: "#315E6E" }}>Batal</button>
                      <button
                        onClick={saveTriage}
                        disabled={!triageForm.level}
                        className="px-5 py-2 text-sm rounded font-semibold text-white transition-opacity disabled:opacity-40"
                        style={{ background: "#0D6E6E" }}
                      >
                        Simpan Triase
                      </button>
                    </div>
                  </div>
                </div>
              )}

              {/* Pemeriksaan form */}
              {activeView === "pemeriksaan" && (
                <div className="max-w-2xl space-y-5">
                  <div className="rounded-lg p-5" style={{ background: "#fff", border: "1px solid #D6E8EE" }}>
                    <h3 className="text-base font-semibold mb-4" style={{ color: "#164E63" }}>Pemeriksaan & Tindakan Darurat</h3>
                    <div className="grid grid-cols-2 gap-4 mb-4">
                      {[
                        { label: "Tekanan Darah (mmHg) *", key: "bp", placeholder: "120/80" },
                        { label: "Nadi (bpm) *", key: "pulse", placeholder: "80" },
                        { label: "SpO₂ (%) *", key: "spo2", placeholder: "98" },
                        { label: "Suhu (°C) *", key: "temp", placeholder: "36.5" },
                      ].map((f) => (
                        <div key={f.key}>
                          <label className="text-xs font-semibold block mb-1.5" style={{ color: "#315E6E" }}>{f.label}</label>
                          <input
                            className="w-full border rounded px-3 py-2 text-sm"
                            style={{ borderColor: "#D6E8EE", fontFamily: "var(--font-mono)", color: "#315E6E" }}
                            placeholder={f.placeholder}
                            value={(examForm as Record<string, string>)[f.key]}
                            onChange={(e) => setExamForm((prev) => ({ ...prev, [f.key]: e.target.value }))}
                          />
                        </div>
                      ))}
                    </div>
                    <div className="mb-4">
                      <label className="text-xs font-semibold block mb-1.5" style={{ color: "#315E6E" }}>Diagnosis Sementara *</label>
                      <input
                        className="w-full border rounded px-3 py-2 text-sm"
                        style={{ borderColor: "#D6E8EE", color: "#315E6E" }}
                        placeholder="Tulis diagnosis klinis..."
                        value={examForm.diagnosis}
                        onChange={(e) => setExamForm((f) => ({ ...f, diagnosis: e.target.value }))}
                      />
                    </div>
                    <div>
                      <label className="text-xs font-semibold block mb-1.5" style={{ color: "#315E6E" }}>Tindakan yang Dilakukan</label>
                      <textarea
                        rows={3}
                        className="w-full border rounded px-3 py-2 text-sm resize-none"
                        style={{ borderColor: "#D6E8EE", color: "#315E6E" }}
                        placeholder="Pisahkan dengan koma (contoh: EKG, O2 nasal, Infus NaCl)"
                        value={examForm.actions}
                        onChange={(e) => setExamForm((f) => ({ ...f, actions: e.target.value }))}
                      />
                    </div>
                    <div className="flex justify-end gap-2 mt-5">
                      <button onClick={() => setActiveView("list")} className="px-4 py-2 text-sm rounded border" style={{ borderColor: "#D6E8EE", color: "#315E6E" }}>Batal</button>
                      <button onClick={saveExam} className="px-5 py-2 text-sm rounded font-semibold text-white" style={{ background: "#0D6E6E" }}>Simpan Pemeriksaan</button>
                    </div>
                  </div>
                </div>
              )}

              {/* Observasi form */}
              {activeView === "observasi" && (
                <div className="max-w-xl space-y-5">
                  <div className="rounded-lg p-5" style={{ background: "#fff", border: "1px solid #D6E8EE" }}>
                    <h3 className="text-base font-semibold mb-4" style={{ color: "#164E63" }}>Observasi & Keputusan Pasien</h3>
                    <div className="space-y-4">
                      <div>
                        <label className="text-xs font-semibold block mb-1.5" style={{ color: "#315E6E" }}>Hasil Observasi</label>
                        <textarea
                          rows={4}
                          className="w-full border rounded px-3 py-2 text-sm resize-none"
                          style={{ borderColor: "#D6E8EE", color: "#315E6E" }}
                          placeholder="Catatan perkembangan kondisi pasien selama observasi..."
                          value={obsForm.note}
                          onChange={(e) => setObsForm((f) => ({ ...f, note: e.target.value }))}
                        />
                      </div>
                      <div>
                        <label className="text-xs font-semibold block mb-1.5" style={{ color: "#315E6E" }}>Kondisi Pasien</label>
                        <select
                          className="w-full border rounded px-3 py-2 text-sm"
                          style={{ borderColor: "#D6E8EE", color: "#315E6E" }}
                          value={obsForm.condition}
                          onChange={(e) => setObsForm((f) => ({ ...f, condition: e.target.value }))}
                        >
                          <option value="">— Pilih kondisi —</option>
                          <option>Membaik — stabil</option>
                          <option>Tidak ada perubahan</option>
                          <option>Memburuk — perlu penanganan lanjut</option>
                          <option>Kritis — butuh ICU</option>
                        </select>
                      </div>
                      <div>
                        <label className="text-xs font-semibold block mb-1.5" style={{ color: "#315E6E" }}>Keputusan Akhir *</label>
                        <div className="grid grid-cols-2 gap-2">
                          {[{ val: "rawat_inap", label: "Rawat Inap", color: "#E1EEFB", textColor: "#1D5FA8" }, { val: "dipulangkan", label: "Dipulangkan", color: "#D9F3EA", textColor: "#117A68" }].map((opt) => (
                            <button
                              key={opt.val}
                              onClick={() => setObsForm((f) => ({ ...f, decision: opt.val as "rawat_inap" | "dipulangkan" }))}
                              className="py-2.5 px-3 rounded text-sm font-semibold border-2 transition-all"
                              style={{
                                background: obsForm.decision === opt.val ? opt.color : "#F7FBFC",
                                color: opt.textColor,
                                borderColor: obsForm.decision === opt.val ? opt.textColor : "#D6E8EE",
                              }}
                            >
                              {opt.label}
                            </button>
                          ))}
                        </div>
                      </div>
                    </div>
                    <div className="flex justify-end gap-2 mt-5">
                      <button onClick={() => setActiveView("list")} className="px-4 py-2 text-sm rounded border" style={{ borderColor: "#D6E8EE", color: "#315E6E" }}>Batal</button>
                      <button onClick={saveObs} className="px-5 py-2 text-sm rounded font-semibold text-white" style={{ background: "#0D6E6E" }}>Simpan Observasi</button>
                    </div>
                  </div>
                </div>
              )}

              {/* ICD finalization */}
              {activeView === "icd" && (
                <div className="max-w-xl space-y-5">
                  <div className="rounded-lg p-5" style={{ background: "#fff", border: "1px solid #D6E8EE" }}>
                    <h3 className="text-base font-semibold mb-1" style={{ color: "#164E63" }}>Finalisasi ICD-10</h3>
                    <p className="text-xs mb-4" style={{ color: "#66869A" }}>Validasi kode ICD-10 sebelum menyimpan. Format: [Huruf][2 digit]-[Desimal], contoh: I21.9</p>
                    <div className="rounded p-3 mb-4" style={{ background: "#F7FBFC", border: "1px solid #D6E8EE" }}>
                      <p className="text-xs font-semibold mb-1" style={{ color: "#66869A" }}>Riwayat Rekam Medis</p>
                      <p className="text-sm" style={{ color: "#315E6E" }}>{selected.chiefComplaint}</p>
                      {selected.actions.length > 0 && <p className="text-xs mt-1" style={{ color: "#8CA5B0" }}>Tindakan: {selected.actions.join(", ")}</p>}
                    </div>
                    <div className="space-y-4">
                      <div>
                        <label className="text-xs font-semibold block mb-1.5" style={{ color: "#315E6E" }}>Diagnosis Final *</label>
                        <input
                          className="w-full border rounded px-3 py-2 text-sm"
                          style={{ borderColor: "#D6E8EE", color: "#315E6E" }}
                          placeholder="Diagnosis klinis final..."
                          value={icdForm.finalDiagnosis}
                          onChange={(e) => setIcdForm((f) => ({ ...f, finalDiagnosis: e.target.value }))}
                        />
                      </div>
                      <div>
                        <label className="text-xs font-semibold block mb-1.5" style={{ color: "#315E6E" }}>Kode ICD-10 *</label>
                        <input
                          className="w-full border rounded px-3 py-2 text-sm"
                          style={{ borderColor: "#D6E8EE", fontFamily: "var(--font-mono)", color: "#315E6E", letterSpacing: "0.05em" }}
                          placeholder="Ketik atau cari kode... (contoh: I21.9)"
                          value={icdForm.icd10}
                          onChange={(e) => setIcdForm((f) => ({ ...f, icd10: e.target.value.toUpperCase() }))}
                        />
                        {icdForm.icd10 && !icdForm.icd10.match(/^[A-Z]\d{2}(\.\d)?$/i) && (
                          <p className="text-xs mt-1 flex items-center gap-1" style={{ color: "#C0362C" }}>
                            ⚠ Kode ICD-10 tidak valid. Format: [Huruf][2 digit].[Desimal]
                          </p>
                        )}
                      </div>
                    </div>
                    <div className="flex justify-end gap-2 mt-5">
                      <button onClick={() => setActiveView("list")} className="px-4 py-2 text-sm rounded border" style={{ borderColor: "#D6E8EE", color: "#315E6E" }}>Batal</button>
                      <button onClick={saveIcd} className="px-5 py-2 text-sm rounded font-semibold text-white" style={{ background: "#0D6E6E" }}>Finalisasi & Simpan</button>
                    </div>
                  </div>
                </div>
              )}
            </div>
          </>
        )}
      </div>

      {toast && (
        <div className="fixed bottom-5 right-5 px-4 py-3 rounded-lg shadow-lg text-sm font-medium text-white z-50" style={{ background: "#0D6E6E" }}>
          {toast}
        </div>
      )}
    </div>
  );
}
