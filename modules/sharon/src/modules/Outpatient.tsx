import { useState } from "react";

type VisitStatus = "menunggu" | "dipanggil" | "periksa" | "selesai" | "batal";
type ServiceType = "Umum" | "Spesialis" | "Gigi" | "Mata" | "Jantung" | "Paru";

interface Visit {
  id: string;
  rm: string;
  name: string;
  age: number;
  gender: string;
  queue: number;
  poli: ServiceType;
  doctor: string;
  status: VisitStatus;
  insurance: string;
  complaint: string;
  diagnosis: string;
  icd10: string;
  prescription: string;
  followUp: string;
}

const STATUS_BADGE: Record<VisitStatus, { bg: string; color: string; label: string }> = {
  menunggu: { bg: "#FFF0D5", color: "#A66700", label: "Menunggu" },
  dipanggil: { bg: "#E1EEFB", color: "#1D5FA8", label: "Dipanggil" },
  periksa: { bg: "#EAF6F8", color: "#0D6E6E", label: "Diperiksa" },
  selesai: { bg: "#D9F3EA", color: "#117A68", label: "Selesai" },
  batal: { bg: "#FDE7E7", color: "#C0362C", label: "Dibatalkan" },
};

const INITIAL_VISITS: Visit[] = [
  { id: "1", rm: "RM-2026-000201", name: "Rina Wulandari", age: 41, gender: "P", queue: 1, poli: "Jantung", doctor: "dr. Ahmad Fauzi, Sp.PD", status: "periksa", insurance: "BPJS", complaint: "Dada berdebar, sesak napas ringan", diagnosis: "Palpitasi jantung", icd10: "R00.2", prescription: "Bisoprolol 2.5mg 1x1", followUp: "Kontrol 2 minggu" },
  { id: "2", rm: "RM-2026-000202", name: "Hendra Gunawan", age: 55, gender: "L", queue: 2, poli: "Umum", doctor: "dr. Sari Dewi", status: "menunggu", insurance: "Mandiri", complaint: "Batuk >2 minggu, demam", diagnosis: "", icd10: "", prescription: "", followUp: "" },
  { id: "3", rm: "RM-2026-000203", name: "Fitri Handayani", age: 29, gender: "P", queue: 3, poli: "Mata", doctor: "dr. Budi Santoso, Sp.M", status: "dipanggil", insurance: "BPJS", complaint: "Penglihatan buram, mata merah", diagnosis: "", icd10: "", prescription: "", followUp: "" },
  { id: "4", rm: "RM-2026-000204", name: "Agus Setiawan", age: 63, gender: "L", queue: 4, poli: "Paru", doctor: "dr. Nina Kartika, Sp.P", status: "menunggu", insurance: "Asuransi Swasta", complaint: "Sesak napas kronik, batuk berdahak", diagnosis: "", icd10: "", prescription: "", followUp: "" },
  { id: "5", rm: "RM-2026-000205", name: "Maya Putri", age: 22, gender: "P", queue: 5, poli: "Gigi", doctor: "drg. Tono Susanto", status: "selesai", insurance: "Mandiri", complaint: "Sakit gigi kiri bawah", diagnosis: "Karies gigi #36", icd10: "K02.1", prescription: "Amoxicillin 500mg 3x1, Asam Mefenamat 500mg 3x1", followUp: "Tambal gigi 1 minggu lagi" },
];

const POLI_LIST: ServiceType[] = ["Umum", "Spesialis", "Gigi", "Mata", "Jantung", "Paru"];

type ActiveView = "list" | "kunjungan" | "pelayanan" | "tindaklanjut" | "finalisasi";

export default function Outpatient() {
  const [visits, setVisits] = useState<Visit[]>(INITIAL_VISITS);
  const [selectedId, setSelectedId] = useState<string | null>(null);
  const [activeView, setActiveView] = useState<ActiveView>("list");
  const [filterPoli, setFilterPoli] = useState<string>("Semua");
  const [toast, setToast] = useState<string | null>(null);
  const [confirmCancel, setConfirmCancel] = useState(false);

  const [examForm, setExamForm] = useState({ diagnosis: "", icd10: "", prescription: "", notes: "" });
  const [followForm, setFollowForm] = useState({ type: "kontrol", referral: "", date: "", instructions: "" });
  const [finalForm, setFinalForm] = useState({ icd10: "", diagnosis: "", status: "selesai" as VisitStatus });

  const selected = visits.find((v) => v.id === selectedId) ?? null;

  function showToast(msg: string) {
    setToast(msg);
    setTimeout(() => setToast(null), 2500);
  }

  const filtered = filterPoli === "Semua" ? visits : visits.filter((v) => v.poli === filterPoli);

  function callPatient(id: string) {
    setVisits((prev) => prev.map((v) => v.id === id ? { ...v, status: "dipanggil" } : v));
    showToast("Pasien dipanggil");
  }

  function startExam(id: string) {
    setVisits((prev) => prev.map((v) => v.id === id ? { ...v, status: "periksa" } : v));
  }

  function saveExam() {
    if (!examForm.diagnosis) return;
    setVisits((prev) => prev.map((v) => v.id === selectedId ? { ...v, diagnosis: examForm.diagnosis, icd10: examForm.icd10, prescription: examForm.prescription } : v));
    showToast("Data pemeriksaan tersimpan");
    setActiveView("list");
  }

  function saveFollowUp() {
    setVisits((prev) => prev.map((v) => v.id === selectedId ? { ...v, followUp: followForm.type === "referral" ? `Rujuk: ${followForm.referral}` : `Kontrol: ${followForm.date}` } : v));
    showToast("Tindak lanjut tersimpan");
    setActiveView("list");
  }

  function finalize() {
    if (!finalForm.icd10.match(/^[A-Z]\d{2}(\.\d)?$/i)) {
      showToast("⚠ Format ICD-10 tidak valid");
      return;
    }
    setVisits((prev) => prev.map((v) => v.id === selectedId ? { ...v, status: "selesai", icd10: finalForm.icd10, diagnosis: finalForm.diagnosis } : v));
    showToast("Kunjungan berhasil difinalisasi. Tersimpan ke rekam medis.");
    setSelectedId(null);
    setActiveView("list");
  }

  function cancelVisit() {
    setVisits((prev) => prev.map((v) => v.id === selectedId ? { ...v, status: "batal" } : v));
    showToast("Kunjungan dibatalkan");
    setConfirmCancel(false);
    setSelectedId(null);
    setActiveView("list");
  }

  const steps: { key: ActiveView; label: string }[] = [
    { key: "kunjungan", label: "Data Kunjungan" },
    { key: "pelayanan", label: "Pelayanan" },
    { key: "tindaklanjut", label: "Tindak Lanjut" },
    { key: "finalisasi", label: "Finalisasi" },
  ];

  return (
    <div className="flex h-full">
      {/* Patient queue list */}
      <div className="w-80 shrink-0 border-r flex flex-col" style={{ background: "#fff", borderColor: "#D6E8EE" }}>
        <div className="p-4 border-b" style={{ borderColor: "#D6E8EE" }}>
          <h1 className="text-lg font-bold" style={{ color: "#164E63" }}>Rawat Jalan</h1>
          <p className="text-xs mt-0.5" style={{ color: "#66869A" }}>
            {new Date().toLocaleDateString("id-ID", { weekday: "long", day: "2-digit", month: "long", year: "numeric" })}
          </p>
          <div className="mt-3 flex flex-wrap gap-1.5">
            {["Semua", ...POLI_LIST].map((p) => (
              <button
                key={p}
                onClick={() => setFilterPoli(p)}
                className="text-xs px-2 py-0.5 rounded-full border transition-colors"
                style={{
                  background: filterPoli === p ? "#0D6E6E" : "#F7FBFC",
                  color: filterPoli === p ? "#fff" : "#66869A",
                  borderColor: filterPoli === p ? "#0D6E6E" : "#D6E8EE",
                }}
              >
                {p}
              </button>
            ))}
          </div>
        </div>

        <div className="flex-1 overflow-y-auto divide-y" style={{ borderColor: "#D6E8EE" }}>
          {filtered.map((v) => {
            const s = STATUS_BADGE[v.status];
            const isSelected = selectedId === v.id;
            return (
              <div
                key={v.id}
                onClick={() => { setSelectedId(v.id); setExamForm({ diagnosis: v.diagnosis, icd10: v.icd10, prescription: v.prescription, notes: "" }); setFinalForm({ icd10: v.icd10, diagnosis: v.diagnosis, status: "selesai" }); setActiveView("list"); }}
                className="p-3 cursor-pointer transition-colors"
                style={{ background: isSelected ? "#EAF6F8" : undefined }}
              >
                <div className="flex items-start justify-between gap-2">
                  <div className="min-w-0">
                    <div className="flex items-center gap-1.5 mb-0.5">
                      <span className="text-xs font-bold rounded-full w-5 h-5 flex items-center justify-center text-white" style={{ background: "#0D6E6E", fontSize: "10px" }}>{v.queue}</span>
                      <p className="text-sm font-semibold truncate" style={{ color: "#164E63" }}>{v.name}</p>
                    </div>
                    <p className="text-xs" style={{ fontFamily: "var(--font-mono)", color: "#66869A" }}>{v.rm}</p>
                    <p className="text-xs mt-0.5" style={{ color: "#66869A" }}>{v.age}th • {v.poli} • {v.insurance}</p>
                  </div>
                  <span className="text-xs px-1.5 py-0.5 rounded shrink-0 font-medium" style={{ background: s.bg, color: s.color }}>{s.label}</span>
                </div>
                {v.status === "menunggu" && (
                  <button
                    onClick={(e) => { e.stopPropagation(); callPatient(v.id); }}
                    className="mt-2 w-full text-xs py-1 rounded font-medium text-white transition-colors"
                    style={{ background: "#0D6E6E" }}
                  >
                    Panggil Pasien
                  </button>
                )}
                {v.status === "dipanggil" && (
                  <button
                    onClick={(e) => { e.stopPropagation(); startExam(v.id); setSelectedId(v.id); setActiveView("pelayanan"); }}
                    className="mt-2 w-full text-xs py-1 rounded font-medium text-white"
                    style={{ background: "#A66700" }}
                  >
                    Mulai Pemeriksaan
                  </button>
                )}
              </div>
            );
          })}
        </div>
        <div className="p-3 border-t" style={{ borderColor: "#D6E8EE" }}>
          <button className="w-full py-2 rounded text-sm font-semibold text-white" style={{ background: "#0D6E6E" }}>
            + Daftarkan Pasien Baru
          </button>
        </div>
      </div>

      {/* Detail */}
      <div className="flex-1 flex flex-col overflow-hidden">
        {!selected ? (
          <div className="flex-1 flex items-center justify-center" style={{ color: "#8CA5B0" }}>
            <div className="text-center">
              <p className="text-4xl mb-3">🏥</p>
              <p className="font-semibold">Pilih pasien dari antrian</p>
              <p className="text-sm mt-1">untuk melihat detail kunjungan</p>
            </div>
          </div>
        ) : (
          <>
            <div className="px-6 py-4 border-b flex items-center justify-between" style={{ background: "#fff", borderColor: "#D6E8EE" }}>
              <div>
                <div className="flex items-center gap-3">
                  <h2 className="text-lg font-bold" style={{ color: "#164E63" }}>{selected.name}</h2>
                  <span className="text-xs font-medium px-2 py-0.5 rounded" style={{ background: STATUS_BADGE[selected.status].bg, color: STATUS_BADGE[selected.status].color }}>
                    {STATUS_BADGE[selected.status].label}
                  </span>
                </div>
                <p className="text-xs mt-0.5" style={{ fontFamily: "var(--font-mono)", color: "#66869A" }}>
                  {selected.rm} • {selected.poli} • {selected.doctor} • {selected.insurance}
                </p>
              </div>
              <div className="flex gap-2">
                {steps.map((s) => (
                  <button
                    key={s.key}
                    onClick={() => setActiveView(s.key)}
                    className="text-xs px-3 py-1.5 rounded font-medium border transition-colors"
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
              {/* Data Kunjungan */}
              {activeView === "list" || activeView === "kunjungan" ? (
                <div className="space-y-4 max-w-2xl">
                  <div className="grid grid-cols-2 gap-4">
                    <div className="rounded-lg p-4" style={{ background: "#fff", border: "1px solid #D6E8EE" }}>
                      <h3 className="text-sm font-semibold mb-3" style={{ color: "#164E63" }}>Identitas Pasien</h3>
                      {[
                        ["Nama", selected.name],
                        ["No. RM", selected.rm],
                        ["Usia", `${selected.age} tahun`],
                        ["Jenis Kelamin", selected.gender === "P" ? "Perempuan" : "Laki-laki"],
                        ["Penjamin", selected.insurance],
                      ].map(([k, v]) => (
                        <div key={k} className="flex justify-between text-xs py-1.5 border-b last:border-0" style={{ borderColor: "#EEF6F8" }}>
                          <span style={{ color: "#66869A" }}>{k}</span>
                          <span className="font-medium" style={{ color: "#164E63", fontFamily: k === "No. RM" ? "var(--font-mono)" : undefined }}>{v}</span>
                        </div>
                      ))}
                    </div>
                    <div className="rounded-lg p-4" style={{ background: "#fff", border: "1px solid #D6E8EE" }}>
                      <h3 className="text-sm font-semibold mb-3" style={{ color: "#164E63" }}>Detail Kunjungan</h3>
                      {[
                        ["No. Antrian", `#${selected.queue}`],
                        ["Poli", selected.poli],
                        ["Dokter", selected.doctor],
                        ["Keluhan", selected.complaint],
                      ].map(([k, v]) => (
                        <div key={k} className="flex justify-between text-xs py-1.5 border-b last:border-0" style={{ borderColor: "#EEF6F8" }}>
                          <span style={{ color: "#66869A" }}>{k}</span>
                          <span className="font-medium text-right max-w-40" style={{ color: "#164E63" }}>{v}</span>
                        </div>
                      ))}
                    </div>
                  </div>
                  {selected.diagnosis && (
                    <div className="rounded-lg p-4" style={{ background: "#fff", border: "1px solid #D6E8EE" }}>
                      <h3 className="text-sm font-semibold mb-2" style={{ color: "#164E63" }}>Hasil Pemeriksaan</h3>
                      <div className="flex items-center gap-2 mb-2">
                        <p className="text-sm flex-1" style={{ color: "#315E6E" }}>{selected.diagnosis}</p>
                        <span className="text-xs px-2 py-0.5 rounded font-bold" style={{ fontFamily: "var(--font-mono)", background: "#E1EEFB", color: "#1D5FA8" }}>{selected.icd10}</span>
                      </div>
                      {selected.prescription && <p className="text-xs" style={{ color: "#66869A" }}>Resep: {selected.prescription}</p>}
                      {selected.followUp && <p className="text-xs mt-1" style={{ color: "#66869A" }}>Tindak Lanjut: {selected.followUp}</p>}
                    </div>
                  )}
                  <button
                    onClick={() => setConfirmCancel(true)}
                    className="text-xs px-3 py-1.5 rounded border font-medium"
                    style={{ borderColor: "#C0362C", color: "#C0362C" }}
                  >
                    Batalkan Kunjungan
                  </button>
                </div>
              ) : null}

              {/* Pelayanan */}
              {activeView === "pelayanan" && (
                <div className="max-w-xl space-y-4">
                  <div className="rounded-lg p-5" style={{ background: "#fff", border: "1px solid #D6E8EE" }}>
                    <h3 className="text-base font-semibold mb-4" style={{ color: "#164E63" }}>Pelayanan Pasien — {selected.poli}</h3>
                    <div className="space-y-4">
                      <div>
                        <label className="text-xs font-semibold block mb-1.5" style={{ color: "#315E6E" }}>Diagnosis *</label>
                        <input
                          className="w-full border rounded px-3 py-2 text-sm"
                          style={{ borderColor: "#D6E8EE", color: "#315E6E" }}
                          placeholder="Diagnosis klinis..."
                          value={examForm.diagnosis}
                          onChange={(e) => setExamForm((f) => ({ ...f, diagnosis: e.target.value }))}
                        />
                        {!examForm.diagnosis && <p className="text-xs mt-1" style={{ color: "#C0362C" }}>⚠ Diagnosis wajib diisi</p>}
                      </div>
                      <div>
                        <label className="text-xs font-semibold block mb-1.5" style={{ color: "#315E6E" }}>Kode ICD-10</label>
                        <input
                          className="w-full border rounded px-3 py-2 text-sm"
                          style={{ borderColor: "#D6E8EE", fontFamily: "var(--font-mono)", color: "#315E6E" }}
                          placeholder="Contoh: J18.9"
                          value={examForm.icd10}
                          onChange={(e) => setExamForm((f) => ({ ...f, icd10: e.target.value.toUpperCase() }))}
                        />
                      </div>
                      <div>
                        <label className="text-xs font-semibold block mb-1.5" style={{ color: "#315E6E" }}>Resep / Tindakan</label>
                        <textarea
                          rows={3}
                          className="w-full border rounded px-3 py-2 text-sm resize-none"
                          style={{ borderColor: "#D6E8EE", color: "#315E6E" }}
                          placeholder="Nama obat, dosis, frekuensi..."
                          value={examForm.prescription}
                          onChange={(e) => setExamForm((f) => ({ ...f, prescription: e.target.value }))}
                        />
                      </div>
                    </div>
                    <div className="flex justify-end gap-2 mt-5">
                      <button onClick={() => setActiveView("list")} className="px-4 py-2 text-sm rounded border" style={{ borderColor: "#D6E8EE", color: "#315E6E" }}>Batal</button>
                      <button onClick={saveExam} disabled={!examForm.diagnosis} className="px-5 py-2 text-sm rounded font-semibold text-white disabled:opacity-40" style={{ background: "#0D6E6E" }}>Simpan Pelayanan</button>
                    </div>
                  </div>
                </div>
              )}

              {/* Tindak lanjut */}
              {activeView === "tindaklanjut" && (
                <div className="max-w-xl space-y-4">
                  <div className="rounded-lg p-5" style={{ background: "#fff", border: "1px solid #D6E8EE" }}>
                    <h3 className="text-base font-semibold mb-4" style={{ color: "#164E63" }}>Tindak Lanjut Pasien</h3>
                    <div className="space-y-4">
                      <div>
                        <label className="text-xs font-semibold block mb-1.5" style={{ color: "#315E6E" }}>Jenis Tindak Lanjut *</label>
                        <div className="grid grid-cols-3 gap-2">
                          {[{ val: "kontrol", label: "Kontrol Ulang" }, { val: "referral", label: "Rujukan" }, { val: "rawat_inap", label: "Rawat Inap" }].map((opt) => (
                            <button
                              key={opt.val}
                              onClick={() => setFollowForm((f) => ({ ...f, type: opt.val }))}
                              className="py-2 px-2 rounded text-xs font-semibold border-2 transition-all"
                              style={{
                                background: followForm.type === opt.val ? "#EAF6F8" : "#F7FBFC",
                                color: followForm.type === opt.val ? "#0D6E6E" : "#66869A",
                                borderColor: followForm.type === opt.val ? "#0D6E6E" : "#D6E8EE",
                              }}
                            >
                              {opt.label}
                            </button>
                          ))}
                        </div>
                      </div>
                      {followForm.type === "referral" && (
                        <div>
                          <label className="text-xs font-semibold block mb-1.5" style={{ color: "#315E6E" }}>Tujuan Rujukan *</label>
                          <input
                            className="w-full border rounded px-3 py-2 text-sm"
                            style={{ borderColor: "#D6E8EE", color: "#315E6E" }}
                            placeholder="RS/Poli tujuan rujukan..."
                            value={followForm.referral}
                            onChange={(e) => setFollowForm((f) => ({ ...f, referral: e.target.value }))}
                          />
                        </div>
                      )}
                      {followForm.type === "kontrol" && (
                        <div>
                          <label className="text-xs font-semibold block mb-1.5" style={{ color: "#315E6E" }}>Tanggal Kontrol *</label>
                          <input
                            type="date"
                            className="w-full border rounded px-3 py-2 text-sm"
                            style={{ borderColor: "#D6E8EE", color: "#315E6E" }}
                            value={followForm.date}
                            min={new Date().toISOString().split("T")[0]}
                            onChange={(e) => setFollowForm((f) => ({ ...f, date: e.target.value }))}
                          />
                        </div>
                      )}
                      <div>
                        <label className="text-xs font-semibold block mb-1.5" style={{ color: "#315E6E" }}>Instruksi / Catatan</label>
                        <textarea
                          rows={3}
                          className="w-full border rounded px-3 py-2 text-sm resize-none"
                          style={{ borderColor: "#D6E8EE", color: "#315E6E" }}
                          placeholder="Instruksi untuk pasien..."
                          value={followForm.instructions}
                          onChange={(e) => setFollowForm((f) => ({ ...f, instructions: e.target.value }))}
                        />
                      </div>
                    </div>
                    <div className="flex justify-end gap-2 mt-5">
                      <button onClick={() => setActiveView("list")} className="px-4 py-2 text-sm rounded border" style={{ borderColor: "#D6E8EE", color: "#315E6E" }}>Batal</button>
                      <button onClick={saveFollowUp} className="px-5 py-2 text-sm rounded font-semibold text-white" style={{ background: "#0D6E6E" }}>Simpan Tindak Lanjut</button>
                    </div>
                  </div>
                </div>
              )}

              {/* Finalisasi */}
              {activeView === "finalisasi" && (
                <div className="max-w-xl space-y-4">
                  <div className="rounded-lg p-5" style={{ background: "#fff", border: "1px solid #D6E8EE" }}>
                    <h3 className="text-base font-semibold mb-1" style={{ color: "#164E63" }}>Finalisasi Pelayanan</h3>
                    <p className="text-xs mb-4" style={{ color: "#66869A" }}>Periksa kelengkapan data sebelum finalisasi. Aksi ini tidak dapat dibatalkan.</p>
                    <div className="rounded p-3 mb-4 space-y-1.5" style={{ background: "#F7FBFC", border: "1px solid #D6E8EE" }}>
                      {[
                        ["Pasien", selected.name],
                        ["Poli", selected.poli],
                        ["Dokter", selected.doctor],
                        ["Diagnosis", selected.diagnosis || "—"],
                        ["Resep", selected.prescription || "—"],
                        ["Tindak Lanjut", selected.followUp || "—"],
                      ].map(([k, v]) => (
                        <div key={k} className="flex justify-between text-xs">
                          <span style={{ color: "#66869A" }}>{k}</span>
                          <span className="font-medium" style={{ color: "#315E6E" }}>{v}</span>
                        </div>
                      ))}
                    </div>
                    <div className="mb-4">
                      <label className="text-xs font-semibold block mb-1.5" style={{ color: "#315E6E" }}>Kode ICD-10 Final *</label>
                      <input
                        className="w-full border rounded px-3 py-2 text-sm"
                        style={{ borderColor: "#D6E8EE", fontFamily: "var(--font-mono)", color: "#315E6E" }}
                        placeholder="Contoh: K02.1"
                        value={finalForm.icd10}
                        onChange={(e) => setFinalForm((f) => ({ ...f, icd10: e.target.value.toUpperCase() }))}
                      />
                      {finalForm.icd10 && !finalForm.icd10.match(/^[A-Z]\d{2}(\.\d)?$/i) && (
                        <p className="text-xs mt-1" style={{ color: "#C0362C" }}>⚠ Format ICD-10 tidak valid. Contoh: K02.1</p>
                      )}
                    </div>
                    <div className="flex justify-end gap-2 mt-5">
                      <button onClick={() => setActiveView("list")} className="px-4 py-2 text-sm rounded border" style={{ borderColor: "#D6E8EE", color: "#315E6E" }}>Kembali</button>
                      <button onClick={finalize} className="px-5 py-2 text-sm rounded font-semibold text-white" style={{ background: "#0D6E6E" }}>Finalisasi Kunjungan</button>
                    </div>
                  </div>
                </div>
              )}
            </div>
          </>
        )}
      </div>

      {/* Confirm cancel modal */}
      {confirmCancel && (
        <div className="fixed inset-0 z-50 flex items-center justify-center" style={{ background: "rgba(0,0,0,0.4)" }}>
          <div className="rounded-xl p-6 w-96 shadow-2xl" style={{ background: "#fff" }}>
            <p className="text-lg font-bold text-center mb-2" style={{ color: "#C0362C" }}>[!] Konfirmasi Pembatalan</p>
            <p className="text-sm text-center mb-1" style={{ color: "#315E6E" }}>
              Kunjungan pasien <strong>{selected?.name}</strong> akan dibatalkan.
            </p>
            <p className="text-xs text-center mb-5" style={{ color: "#66869A" }}>Tindakan ini tidak dapat dibatalkan (undo).</p>
            <div className="flex gap-3">
              <button onClick={() => setConfirmCancel(false)} className="flex-1 py-2 rounded border text-sm font-semibold" style={{ borderColor: "#D6E8EE", color: "#315E6E" }}>Kembali</button>
              <button onClick={cancelVisit} className="flex-1 py-2 rounded text-sm font-semibold text-white" style={{ background: "#C0362C" }}>Batalkan Kunjungan</button>
            </div>
          </div>
        </div>
      )}

      {toast && (
        <div className="fixed bottom-5 right-5 px-4 py-3 rounded-lg shadow-lg text-sm font-medium text-white z-50" style={{ background: "#0D6E6E" }}>
          {toast}
        </div>
      )}
    </div>
  );
}
