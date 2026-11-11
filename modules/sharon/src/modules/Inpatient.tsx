import { useState } from "react";

type RoomClass = "VIP" | "Kelas I" | "Kelas II" | "Kelas III" | "ICU";
type BedStatus = "tersedia" | "terisi" | "maintenance";
type AdmissionStatus = "aktif" | "transfer" | "selesai" | "menunggu";

interface Bed {
  id: string;
  room: string;
  class: RoomClass;
  status: BedStatus;
  patientName?: string;
  patientRm?: string;
  admitDate?: string;
  days?: number;
}

interface InPatient {
  id: string;
  rm: string;
  name: string;
  age: number;
  gender: string;
  diagnosis: string;
  icd10: string;
  bedId: string;
  admitDate: string;
  days: number;
  doctor: string;
  status: AdmissionStatus;
  notes: string;
}

const CLASS_COLOR: Record<RoomClass, { bg: string; color: string }> = {
  VIP: { bg: "#FFF0D5", color: "#A66700" },
  "Kelas I": { bg: "#E1EEFB", color: "#1D5FA8" },
  "Kelas II": { bg: "#EAF6F8", color: "#0D6E6E" },
  "Kelas III": { bg: "#EEF6F8", color: "#456D7D" },
  ICU: { bg: "#FDE7E7", color: "#C0362C" },
};

const STATUS_BADGE: Record<AdmissionStatus, { bg: string; color: string; label: string }> = {
  aktif: { bg: "#D9F3EA", color: "#117A68", label: "Aktif" },
  transfer: { bg: "#FFF0D5", color: "#A66700", label: "Transfer" },
  selesai: { bg: "#E1EEFB", color: "#1D5FA8", label: "Selesai" },
  menunggu: { bg: "#EEF6F8", color: "#66869A", label: "Menunggu" },
};

const INITIAL_BEDS: Bed[] = [
  { id: "VIP-01", room: "VIP-01", class: "VIP", status: "terisi", patientName: "Herman Suryadi", patientRm: "RM-2026-000219", admitDate: "16/07/2026", days: 45 },
  { id: "VIP-02", room: "VIP-02", class: "VIP", status: "tersedia" },
  { id: "I-01", room: "Kelas I-01", class: "Kelas I", status: "terisi", patientName: "Siti Nurhaliza", patientRm: "RM-2026-000220", admitDate: "20/08/2026", days: 18 },
  { id: "I-02", room: "Kelas I-02", class: "Kelas I", status: "tersedia" },
  { id: "I-03", room: "Kelas I-03", class: "Kelas I", status: "tersedia" },
  { id: "I-04", room: "Kelas I-04", class: "Kelas I", status: "terisi", patientName: "Bambang Irawan", patientRm: "RM-2026-000221", admitDate: "01/09/2026", days: 6 },
  { id: "II-01", room: "Kelas II-01", class: "Kelas II", status: "tersedia" },
  { id: "II-02", room: "Kelas II-02", class: "Kelas II", status: "tersedia" },
  { id: "II-03", room: "Kelas II-03", class: "Kelas II", status: "maintenance" },
  { id: "II-04", room: "Kelas II-04", class: "Kelas II", status: "terisi", patientName: "Wati Susanti", patientRm: "RM-2026-000222", admitDate: "05/09/2026", days: 2 },
  { id: "III-01", room: "Kelas III-01", class: "Kelas III", status: "tersedia" },
  { id: "III-02", room: "Kelas III-02", class: "Kelas III", status: "terisi", patientName: "Joko Widodo", patientRm: "RM-2026-000223", admitDate: "04/09/2026", days: 3 },
  { id: "III-03", room: "Kelas III-03", class: "Kelas III", status: "tersedia" },
  { id: "ICU-01", room: "ICU-01", class: "ICU", status: "terisi", patientName: "Ahmad Rizal", patientRm: "RM-2026-000224", admitDate: "06/09/2026", days: 1 },
  { id: "ICU-02", room: "ICU-02", class: "ICU", status: "tersedia" },
];

const INITIAL_PATIENTS: InPatient[] = [
  { id: "1", rm: "RM-2026-000219", name: "Herman Suryadi", age: 62, gender: "L", diagnosis: "Gagal Jantung Kongestif", icd10: "I50.0", bedId: "VIP-01", admitDate: "16/07/2026", days: 45, doctor: "dr. Ahmad Fauzi, Sp.JP", status: "aktif", notes: "Monitoring ketat" },
  { id: "2", rm: "RM-2026-000220", name: "Siti Nurhaliza", age: 48, gender: "P", diagnosis: "Diabetes Melitus Tipe 2", icd10: "E11.9", bedId: "I-01", admitDate: "20/08/2026", days: 18, doctor: "dr. Rina Kuswati, Sp.PD", status: "aktif", notes: "" },
  { id: "3", rm: "RM-2026-000221", name: "Bambang Irawan", age: 71, gender: "L", diagnosis: "Pneumonia Berat", icd10: "J18.9", bedId: "I-04", admitDate: "01/09/2026", days: 6, doctor: "dr. Nina Kartika, Sp.P", status: "aktif", notes: "O2 4L/m" },
  { id: "4", rm: "RM-2026-000222", name: "Wati Susanti", age: 35, gender: "P", diagnosis: "Apendisitis Akut Post Op", icd10: "K35.8", bedId: "II-04", admitDate: "05/09/2026", days: 2, doctor: "dr. Tono Prabowo, Sp.B", status: "aktif", notes: "Post appendektomi H+2" },
  { id: "5", rm: "RM-2026-000223", name: "Joko Widodo", age: 58, gender: "L", diagnosis: "Stroke Iskemik", icd10: "I63.9", bedId: "III-02", admitDate: "04/09/2026", days: 3, doctor: "dr. Adi Santoso, Sp.S", status: "aktif", notes: "" },
];

type ActiveView = "bed" | "pasien" | "admisi" | "transfer" | "finalisasi";

export default function Inpatient() {
  const [beds, setBeds] = useState<Bed[]>(INITIAL_BEDS);
  const [patients, setPatients] = useState<InPatient[]>(INITIAL_PATIENTS);
  const [activeView, setActiveView] = useState<ActiveView>("bed");
  const [selectedPatientId, setSelectedPatientId] = useState<string | null>(null);
  const [toast, setToast] = useState<string | null>(null);
  const [filterClass, setFilterClass] = useState<string>("Semua");

  const [admisiForm, setAdmisiForm] = useState({ rm: "", name: "", age: "", gender: "L", diagnosis: "", icd10: "", bedId: "", doctor: "", notes: "" });
  const [transferForm, setTransferForm] = useState({ newBedId: "", reason: "" });
  const [finalForm, setFinalForm] = useState({ condition: "membaik", notes: "", date: "" });

  const selected = patients.find((p) => p.id === selectedPatientId) ?? null;

  function showToast(msg: string) {
    setToast(msg);
    setTimeout(() => setToast(null), 2500);
  }

  const availableBeds = beds.filter((b) => b.status === "tersedia");
  const classSummary = (["VIP", "Kelas I", "Kelas II", "Kelas III", "ICU"] as RoomClass[]).map((c) => {
    const total = beds.filter((b) => b.class === c).length;
    const occupied = beds.filter((b) => b.class === c && b.status === "terisi").length;
    return { class: c, total, occupied, available: total - occupied };
  });

  const filteredBeds = filterClass === "Semua" ? beds : beds.filter((b) => b.class === filterClass);

  function saveAdmisi() {
    if (!admisiForm.rm || !admisiForm.name || !admisiForm.bedId) return;
    const newPatient: InPatient = {
      id: String(Date.now()), rm: admisiForm.rm, name: admisiForm.name, age: Number(admisiForm.age),
      gender: admisiForm.gender, diagnosis: admisiForm.diagnosis, icd10: admisiForm.icd10,
      bedId: admisiForm.bedId, admitDate: new Date().toLocaleDateString("id-ID"),
      days: 0, doctor: admisiForm.doctor, status: "aktif", notes: admisiForm.notes,
    };
    setPatients((prev) => [...prev, newPatient]);
    setBeds((prev) => prev.map((b) => b.id === admisiForm.bedId ? { ...b, status: "terisi", patientName: admisiForm.name, patientRm: admisiForm.rm, admitDate: new Date().toLocaleDateString("id-ID"), days: 0 } : b));
    setAdmisiForm({ rm: "", name: "", age: "", gender: "L", diagnosis: "", icd10: "", bedId: "", doctor: "", notes: "" });
    showToast("Pasien berhasil diadmisi ke ruangan");
    setActiveView("pasien");
  }

  function saveTransfer() {
    if (!transferForm.newBedId || !selected) return;
    const oldBedId = selected.bedId;
    setBeds((prev) => prev.map((b) => {
      if (b.id === oldBedId) return { ...b, status: "tersedia", patientName: undefined, patientRm: undefined };
      if (b.id === transferForm.newBedId) return { ...b, status: "terisi", patientName: selected.name, patientRm: selected.rm };
      return b;
    }));
    setPatients((prev) => prev.map((p) => p.id === selectedPatientId ? { ...p, bedId: transferForm.newBedId, status: "aktif" } : p));
    showToast("Transfer ruangan berhasil");
    setActiveView("pasien");
  }

  function finalize() {
    if (!selected) return;
    setBeds((prev) => prev.map((b) => b.id === selected.bedId ? { ...b, status: "tersedia", patientName: undefined, patientRm: undefined } : b));
    setPatients((prev) => prev.map((p) => p.id === selectedPatientId ? { ...p, status: "selesai" } : p));
    showToast("Pelayanan rawat inap difinalisasi. Data tersimpan.");
    setSelectedPatientId(null);
    setActiveView("bed");
  }

  return (
    <div className="flex h-full flex-col">
      {/* Top navigation */}
      <div className="px-6 py-4 border-b flex items-center justify-between" style={{ background: "#fff", borderColor: "#D6E8EE" }}>
        <div>
          <h1 className="text-lg font-bold" style={{ color: "#164E63" }}>Rawat Inap</h1>
          <p className="text-xs" style={{ color: "#66869A" }}>
            {new Date().toLocaleDateString("id-ID", { weekday: "long", day: "2-digit", month: "long", year: "numeric" })}
          </p>
        </div>
        <div className="flex gap-2">
          {([
            { key: "bed", label: "Manajemen Kamar" },
            { key: "pasien", label: "Pasien Aktif" },
            { key: "admisi", label: "Admisi Baru" },
            { key: "transfer", label: "Transfer" },
            { key: "finalisasi", label: "Finalisasi" },
          ] as { key: ActiveView; label: string }[]).map((tab) => (
            <button
              key={tab.key}
              onClick={() => setActiveView(tab.key)}
              className="text-xs px-3 py-1.5 rounded font-medium border transition-colors"
              style={{
                background: activeView === tab.key ? "#0D6E6E" : "#fff",
                color: activeView === tab.key ? "#fff" : "#315E6E",
                borderColor: activeView === tab.key ? "#0D6E6E" : "#D6E8EE",
              }}
            >
              {tab.label}
            </button>
          ))}
        </div>
      </div>

      <div className="flex-1 overflow-y-auto p-6">
        {/* Bed Management */}
        {activeView === "bed" && (
          <div className="space-y-5">
            {/* Summary stats */}
            <div className="grid grid-cols-5 gap-3">
              {classSummary.map((c) => (
                <div key={c.class} className="rounded-lg p-4" style={{ background: "#fff", border: "1px solid #D6E8EE" }}>
                  <div className="flex items-center justify-between mb-2">
                    <span className="text-xs font-semibold" style={{ color: "#66869A" }}>{c.class}</span>
                    <span className="text-xs px-1.5 py-0.5 rounded font-medium" style={{ background: CLASS_COLOR[c.class as RoomClass].bg, color: CLASS_COLOR[c.class as RoomClass].color }}>{c.class}</span>
                  </div>
                  <p className="text-2xl font-bold" style={{ fontFamily: "var(--font-mono)", color: "#164E63" }}>{c.occupied}/{c.total}</p>
                  <p className="text-xs mt-0.5" style={{ color: "#117A68" }}>{c.available} tempat kosong</p>
                </div>
              ))}
            </div>

            {/* Filter */}
            <div className="flex gap-2">
              {["Semua", "VIP", "Kelas I", "Kelas II", "Kelas III", "ICU"].map((c) => (
                <button
                  key={c}
                  onClick={() => setFilterClass(c)}
                  className="text-xs px-3 py-1 rounded-full border transition-colors"
                  style={{
                    background: filterClass === c ? "#0D6E6E" : "#fff",
                    color: filterClass === c ? "#fff" : "#66869A",
                    borderColor: filterClass === c ? "#0D6E6E" : "#D6E8EE",
                  }}
                >
                  {c}
                </button>
              ))}
            </div>

            {/* Bed grid */}
            <div className="grid grid-cols-4 gap-3">
              {filteredBeds.map((bed) => (
                <div
                  key={bed.id}
                  className="rounded-lg p-4"
                  style={{
                    background: bed.status === "tersedia" ? "#fff" : bed.status === "terisi" ? "#F7FBFC" : "#fafafa",
                    border: `1px solid ${bed.status === "tersedia" ? "#D6E8EE" : bed.status === "terisi" ? "#C7DDE5" : "#D6E8EE"}`,
                  }}
                >
                  <div className="flex items-center justify-between mb-2">
                    <span className="text-sm font-semibold" style={{ color: "#164E63" }}>{bed.room}</span>
                    <span
                      className="text-xs px-1.5 py-0.5 rounded"
                      style={{
                        background: bed.status === "tersedia" ? "#D9F3EA" : bed.status === "terisi" ? "#FDE7E7" : "#EEF6F8",
                        color: bed.status === "tersedia" ? "#117A68" : bed.status === "terisi" ? "#C0362C" : "#66869A",
                      }}
                    >
                      {bed.status === "tersedia" ? "Kosong" : bed.status === "terisi" ? "Terisi" : "Maintenance"}
                    </span>
                  </div>
                  <span className="text-xs px-1.5 py-0.5 rounded" style={{ background: CLASS_COLOR[bed.class].bg, color: CLASS_COLOR[bed.class].color }}>{bed.class}</span>
                  {bed.patientName && (
                    <div className="mt-2 pt-2 border-t" style={{ borderColor: "#D6E8EE" }}>
                      <p className="text-xs font-semibold truncate" style={{ color: "#315E6E" }}>{bed.patientName}</p>
                      <p className="text-xs" style={{ fontFamily: "var(--font-mono)", color: "#8CA5B0" }}>{bed.patientRm}</p>
                      <p className="text-xs" style={{ color: "#8CA5B0" }}>Masuk: {bed.admitDate} • {bed.days} hr</p>
                    </div>
                  )}
                </div>
              ))}
            </div>
          </div>
        )}

        {/* Active Patients */}
        {activeView === "pasien" && (
          <div className="space-y-3">
            <h2 className="text-base font-semibold" style={{ color: "#164E63" }}>Pasien Rawat Inap Aktif ({patients.filter((p) => p.status === "aktif").length})</h2>
            <div className="rounded-lg overflow-hidden border" style={{ background: "#fff", borderColor: "#D6E8EE" }}>
              <table className="w-full text-sm">
                <thead>
                  <tr style={{ background: "#F7FBFC", borderBottom: "1px solid #D6E8EE" }}>
                    {["PASIEN", "NO. RM", "KAMAR", "DIAGNOSIS", "MASUK", "HARI", "DOKTER", "STATUS", "AKSI"].map((h) => (
                      <th key={h} className="text-left text-xs font-semibold px-4 py-3" style={{ color: "#66869A" }}>{h}</th>
                    ))}
                  </tr>
                </thead>
                <tbody className="divide-y" style={{ borderColor: "#EEF6F8" }}>
                  {patients.map((p) => {
                    const bed = beds.find((b) => b.id === p.bedId);
                    const s = STATUS_BADGE[p.status];
                    return (
                      <tr key={p.id} className="hover:bg-slate-50 transition-colors">
                        <td className="px-4 py-3 font-semibold" style={{ color: "#164E63" }}>{p.name}</td>
                        <td className="px-4 py-3 text-xs" style={{ fontFamily: "var(--font-mono)", color: "#66869A" }}>{p.rm}</td>
                        <td className="px-4 py-3 text-xs font-medium" style={{ color: "#315E6E" }}>{bed?.room ?? p.bedId}</td>
                        <td className="px-4 py-3">
                          <div className="flex items-center gap-1.5">
                            <span className="text-xs" style={{ color: "#315E6E" }}>{p.diagnosis}</span>
                            <span className="text-xs font-bold px-1.5 py-0.5 rounded" style={{ fontFamily: "var(--font-mono)", background: "#E1EEFB", color: "#1D5FA8" }}>{p.icd10}</span>
                          </div>
                        </td>
                        <td className="px-4 py-3 text-xs" style={{ color: "#66869A" }}>{p.admitDate}</td>
                        <td className="px-4 py-3 text-xs font-bold" style={{ fontFamily: "var(--font-mono)", color: p.days > 30 ? "#A66700" : "#315E6E" }}>{p.days} hr</td>
                        <td className="px-4 py-3 text-xs" style={{ color: "#66869A" }}>{p.doctor}</td>
                        <td className="px-4 py-3">
                          <span className="text-xs px-1.5 py-0.5 rounded font-medium" style={{ background: s.bg, color: s.color }}>{s.label}</span>
                        </td>
                        <td className="px-4 py-3">
                          <div className="flex gap-1">
                            <button
                              onClick={() => { setSelectedPatientId(p.id); setTransferForm({ newBedId: "", reason: "" }); setActiveView("transfer"); }}
                              className="text-xs px-2 py-1 rounded border"
                              style={{ borderColor: "#D6E8EE", color: "#315E6E" }}
                            >
                              Transfer
                            </button>
                            <button
                              onClick={() => { setSelectedPatientId(p.id); setFinalForm({ condition: "membaik", notes: "", date: "" }); setActiveView("finalisasi"); }}
                              className="text-xs px-2 py-1 rounded text-white"
                              style={{ background: "#0D6E6E" }}
                            >
                              Keluar
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

        {/* Admisi Baru */}
        {activeView === "admisi" && (
          <div className="max-w-2xl">
            <div className="rounded-lg p-5" style={{ background: "#fff", border: "1px solid #D6E8EE" }}>
              <h3 className="text-base font-semibold mb-4" style={{ color: "#164E63" }}>Admisi Pasien Rawat Inap</h3>
              <div className="grid grid-cols-2 gap-4 mb-4">
                {[
                  { label: "No. Rekam Medis *", key: "rm", placeholder: "RM-2026-XXXXXX", mono: true },
                  { label: "Nama Pasien *", key: "name", placeholder: "Nama lengkap" },
                  { label: "Usia (tahun)", key: "age", placeholder: "45" },
                  { label: "Dokter DPJP *", key: "doctor", placeholder: "dr. Nama Dokter, Sp.XX" },
                ].map((f) => (
                  <div key={f.key}>
                    <label className="text-xs font-semibold block mb-1.5" style={{ color: "#315E6E" }}>{f.label}</label>
                    <input
                      className="w-full border rounded px-3 py-2 text-sm"
                      style={{ borderColor: "#D6E8EE", color: "#315E6E", fontFamily: f.mono ? "var(--font-mono)" : undefined }}
                      placeholder={f.placeholder}
                      value={(admisiForm as Record<string, string>)[f.key]}
                      onChange={(e) => setAdmisiForm((prev) => ({ ...prev, [f.key]: e.target.value }))}
                    />
                  </div>
                ))}
              </div>
              <div className="mb-4">
                <label className="text-xs font-semibold block mb-1.5" style={{ color: "#315E6E" }}>Jenis Kelamin</label>
                <div className="flex gap-2">
                  {["L", "P"].map((g) => (
                    <button
                      key={g}
                      onClick={() => setAdmisiForm((f) => ({ ...f, gender: g }))}
                      className="px-4 py-1.5 rounded text-sm font-medium border-2 transition-all"
                      style={{
                        background: admisiForm.gender === g ? "#EAF6F8" : "#F7FBFC",
                        color: admisiForm.gender === g ? "#0D6E6E" : "#66869A",
                        borderColor: admisiForm.gender === g ? "#0D6E6E" : "#D6E8EE",
                      }}
                    >
                      {g === "L" ? "Laki-laki" : "Perempuan"}
                    </button>
                  ))}
                </div>
              </div>
              <div className="grid grid-cols-2 gap-4 mb-4">
                <div>
                  <label className="text-xs font-semibold block mb-1.5" style={{ color: "#315E6E" }}>Diagnosis *</label>
                  <input
                    className="w-full border rounded px-3 py-2 text-sm"
                    style={{ borderColor: "#D6E8EE", color: "#315E6E" }}
                    placeholder="Diagnosis klinis..."
                    value={admisiForm.diagnosis}
                    onChange={(e) => setAdmisiForm((f) => ({ ...f, diagnosis: e.target.value }))}
                  />
                </div>
                <div>
                  <label className="text-xs font-semibold block mb-1.5" style={{ color: "#315E6E" }}>Kode ICD-10</label>
                  <input
                    className="w-full border rounded px-3 py-2 text-sm"
                    style={{ borderColor: "#D6E8EE", fontFamily: "var(--font-mono)", color: "#315E6E" }}
                    placeholder="Contoh: J18.9"
                    value={admisiForm.icd10}
                    onChange={(e) => setAdmisiForm((f) => ({ ...f, icd10: e.target.value.toUpperCase() }))}
                  />
                </div>
              </div>
              <div className="mb-4">
                <label className="text-xs font-semibold block mb-1.5" style={{ color: "#315E6E" }}>Pilih Kamar / Tempat Tidur *</label>
                {availableBeds.length === 0 ? (
                  <div className="p-3 rounded text-sm" style={{ background: "#FDE7E7", color: "#C0362C" }}>Tidak ada kamar tersedia saat ini</div>
                ) : (
                  <div className="grid grid-cols-3 gap-2 max-h-36 overflow-y-auto">
                    {availableBeds.map((b) => (
                      <button
                        key={b.id}
                        onClick={() => setAdmisiForm((f) => ({ ...f, bedId: b.id }))}
                        className="py-2 px-3 rounded text-xs font-medium border-2 text-left transition-all"
                        style={{
                          background: admisiForm.bedId === b.id ? "#EAF6F8" : "#F7FBFC",
                          color: admisiForm.bedId === b.id ? "#0D6E6E" : "#315E6E",
                          borderColor: admisiForm.bedId === b.id ? "#0D6E6E" : "#D6E8EE",
                        }}
                      >
                        <span className="block font-semibold">{b.room}</span>
                        <span style={{ color: CLASS_COLOR[b.class].color }}>{b.class}</span>
                      </button>
                    ))}
                  </div>
                )}
              </div>
              <div className="mb-5">
                <label className="text-xs font-semibold block mb-1.5" style={{ color: "#315E6E" }}>Catatan Perawatan</label>
                <textarea
                  rows={2}
                  className="w-full border rounded px-3 py-2 text-sm resize-none"
                  style={{ borderColor: "#D6E8EE", color: "#315E6E" }}
                  placeholder="Instruksi / catatan khusus..."
                  value={admisiForm.notes}
                  onChange={(e) => setAdmisiForm((f) => ({ ...f, notes: e.target.value }))}
                />
              </div>
              <div className="flex justify-end gap-2">
                <button onClick={() => setActiveView("bed")} className="px-4 py-2 text-sm rounded border" style={{ borderColor: "#D6E8EE", color: "#315E6E" }}>Batal</button>
                <button
                  onClick={saveAdmisi}
                  disabled={!admisiForm.rm || !admisiForm.name || !admisiForm.bedId}
                  className="px-5 py-2 text-sm rounded font-semibold text-white disabled:opacity-40"
                  style={{ background: "#0D6E6E" }}
                >
                  Admisi Pasien
                </button>
              </div>
            </div>
          </div>
        )}

        {/* Transfer */}
        {activeView === "transfer" && (
          <div className="max-w-xl">
            <div className="rounded-lg p-5" style={{ background: "#fff", border: "1px solid #D6E8EE" }}>
              <h3 className="text-base font-semibold mb-4" style={{ color: "#164E63" }}>Transfer Ruangan</h3>
              {selected && (
                <div className="rounded p-3 mb-4" style={{ background: "#F7FBFC", border: "1px solid #D6E8EE" }}>
                  <p className="text-sm font-semibold" style={{ color: "#164E63" }}>{selected.name}</p>
                  <p className="text-xs" style={{ fontFamily: "var(--font-mono)", color: "#66869A" }}>{selected.rm}</p>
                  <p className="text-xs mt-1" style={{ color: "#66869A" }}>Kamar saat ini: <strong>{beds.find((b) => b.id === selected.bedId)?.room}</strong></p>
                </div>
              )}
              {!selected && (
                <div className="mb-4">
                  <label className="text-xs font-semibold block mb-1.5" style={{ color: "#315E6E" }}>Pilih Pasien</label>
                  <select
                    className="w-full border rounded px-3 py-2 text-sm"
                    style={{ borderColor: "#D6E8EE", color: "#315E6E" }}
                    onChange={(e) => setSelectedPatientId(e.target.value || null)}
                  >
                    <option value="">— Pilih pasien —</option>
                    {patients.filter((p) => p.status === "aktif").map((p) => (
                      <option key={p.id} value={p.id}>{p.name} — {beds.find((b) => b.id === p.bedId)?.room}</option>
                    ))}
                  </select>
                </div>
              )}
              <div className="mb-4">
                <label className="text-xs font-semibold block mb-1.5" style={{ color: "#315E6E" }}>Kamar Tujuan *</label>
                <div className="grid grid-cols-3 gap-2">
                  {availableBeds.filter((b) => b.id !== selected?.bedId).map((b) => (
                    <button
                      key={b.id}
                      onClick={() => setTransferForm((f) => ({ ...f, newBedId: b.id }))}
                      className="py-2 px-3 rounded text-xs font-medium border-2 text-left transition-all"
                      style={{
                        background: transferForm.newBedId === b.id ? "#EAF6F8" : "#F7FBFC",
                        color: transferForm.newBedId === b.id ? "#0D6E6E" : "#315E6E",
                        borderColor: transferForm.newBedId === b.id ? "#0D6E6E" : "#D6E8EE",
                      }}
                    >
                      <span className="block font-semibold">{b.room}</span>
                      <span style={{ color: CLASS_COLOR[b.class].color }}>{b.class}</span>
                    </button>
                  ))}
                </div>
              </div>
              <div className="mb-5">
                <label className="text-xs font-semibold block mb-1.5" style={{ color: "#315E6E" }}>Alasan Transfer</label>
                <input
                  className="w-full border rounded px-3 py-2 text-sm"
                  style={{ borderColor: "#D6E8EE", color: "#315E6E" }}
                  placeholder="Alasan transfer ruangan..."
                  value={transferForm.reason}
                  onChange={(e) => setTransferForm((f) => ({ ...f, reason: e.target.value }))}
                />
              </div>
              <div className="flex justify-end gap-2">
                <button onClick={() => setActiveView("pasien")} className="px-4 py-2 text-sm rounded border" style={{ borderColor: "#D6E8EE", color: "#315E6E" }}>Batal</button>
                <button onClick={saveTransfer} disabled={!transferForm.newBedId} className="px-5 py-2 text-sm rounded font-semibold text-white disabled:opacity-40" style={{ background: "#0D6E6E" }}>Konfirmasi Transfer</button>
              </div>
            </div>
          </div>
        )}

        {/* Finalisasi */}
        {activeView === "finalisasi" && (
          <div className="max-w-xl">
            <div className="rounded-lg p-5" style={{ background: "#fff", border: "1px solid #D6E8EE" }}>
              <h3 className="text-base font-semibold mb-4" style={{ color: "#164E63" }}>Finalisasi Pelayanan Rawat Inap</h3>
              {!selected && (
                <div className="mb-4">
                  <label className="text-xs font-semibold block mb-1.5" style={{ color: "#315E6E" }}>Pilih Pasien</label>
                  <select
                    className="w-full border rounded px-3 py-2 text-sm"
                    style={{ borderColor: "#D6E8EE", color: "#315E6E" }}
                    onChange={(e) => setSelectedPatientId(e.target.value || null)}
                  >
                    <option value="">— Pilih pasien —</option>
                    {patients.filter((p) => p.status === "aktif").map((p) => (
                      <option key={p.id} value={p.id}>{p.name} — {p.rm}</option>
                    ))}
                  </select>
                </div>
              )}
              {selected && (
                <div className="rounded p-3 mb-4" style={{ background: "#F7FBFC", border: "1px solid #D6E8EE" }}>
                  {[["Pasien", selected.name], ["No. RM", selected.rm], ["Diagnosis", selected.diagnosis], ["ICD-10", selected.icd10], ["Kamar", beds.find((b) => b.id === selected.bedId)?.room ?? selected.bedId], ["Lama Rawat", `${selected.days} hari`]].map(([k, v]) => (
                    <div key={k} className="flex justify-between text-xs py-1">
                      <span style={{ color: "#66869A" }}>{k}</span>
                      <span className="font-medium" style={{ color: "#315E6E", fontFamily: k === "No. RM" || k === "ICD-10" ? "var(--font-mono)" : undefined }}>{v}</span>
                    </div>
                  ))}
                </div>
              )}
              <div className="space-y-4 mb-5">
                <div>
                  <label className="text-xs font-semibold block mb-1.5" style={{ color: "#315E6E" }}>Kondisi Saat Keluar</label>
                  <select
                    className="w-full border rounded px-3 py-2 text-sm"
                    style={{ borderColor: "#D6E8EE", color: "#315E6E" }}
                    value={finalForm.condition}
                    onChange={(e) => setFinalForm((f) => ({ ...f, condition: e.target.value }))}
                  >
                    <option value="membaik">Membaik — dipulangkan</option>
                    <option value="dirujuk">Dirujuk ke RS lain</option>
                    <option value="atas_permintaan">Pulang Atas Permintaan Sendiri</option>
                    <option value="meninggal">Meninggal Dunia</option>
                  </select>
                </div>
                <div>
                  <label className="text-xs font-semibold block mb-1.5" style={{ color: "#315E6E" }}>Tanggal Keluar</label>
                  <input
                    type="date"
                    className="w-full border rounded px-3 py-2 text-sm"
                    style={{ borderColor: "#D6E8EE", color: "#315E6E" }}
                    value={finalForm.date}
                    onChange={(e) => setFinalForm((f) => ({ ...f, date: e.target.value }))}
                  />
                </div>
                <div>
                  <label className="text-xs font-semibold block mb-1.5" style={{ color: "#315E6E" }}>Catatan Keluar</label>
                  <textarea
                    rows={3}
                    className="w-full border rounded px-3 py-2 text-sm resize-none"
                    style={{ borderColor: "#D6E8EE", color: "#315E6E" }}
                    placeholder="Instruksi / catatan kepulangan..."
                    value={finalForm.notes}
                    onChange={(e) => setFinalForm((f) => ({ ...f, notes: e.target.value }))}
                  />
                </div>
              </div>
              <div className="flex justify-end gap-2">
                <button onClick={() => setActiveView("pasien")} className="px-4 py-2 text-sm rounded border" style={{ borderColor: "#D6E8EE", color: "#315E6E" }}>Kembali</button>
                <button onClick={finalize} disabled={!selected} className="px-5 py-2 text-sm rounded font-semibold text-white disabled:opacity-40" style={{ background: "#0D6E6E" }}>Finalisasi & Keluar Pasien</button>
              </div>
            </div>
          </div>
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
