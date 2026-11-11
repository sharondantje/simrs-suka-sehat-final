import { useState } from "react";

type InsuranceType = "BPJS" | "Mandiri" | "Asuransi Swasta" | "Perusahaan";
type BillingStatus = "draft" | "diverifikasi" | "diproses" | "selesai" | "ditolak";
type ClaimStatus = "pending" | "diajukan" | "disetujui" | "ditolak";
type PaymentMethod = "Tunai" | "Transfer Bank" | "BPJS" | "Asuransi" | "Kartu Kredit";

interface InsuranceInfo {
  type: InsuranceType;
  memberNo: string;
  policyNo: string;
  validUntil: string;
  coverageLimit: number;
  verified: boolean;
}

interface BillingItem {
  name: string;
  qty: number;
  price: number;
  total: number;
}

interface Transaction {
  id: string;
  rm: string;
  name: string;
  module: string;
  admitDate: string;
  dischargeDate: string;
  insurance: InsuranceInfo;
  items: BillingItem[];
  total: number;
  covered: number;
  patientShare: number;
  status: BillingStatus;
  claimStatus: ClaimStatus;
  paymentMethod?: PaymentMethod;
  notes: string;
}

const BILLING_STATUS: Record<BillingStatus, { bg: string; color: string; label: string }> = {
  draft: { bg: "#EEF6F8", color: "#66869A", label: "Draft" },
  diverifikasi: { bg: "#E1EEFB", color: "#1D5FA8", label: "Diverifikasi" },
  diproses: { bg: "#FFF0D5", color: "#A66700", label: "Diproses" },
  selesai: { bg: "#D9F3EA", color: "#117A68", label: "Selesai" },
  ditolak: { bg: "#FDE7E7", color: "#C0362C", label: "Ditolak" },
};

const CLAIM_STATUS: Record<ClaimStatus, { bg: string; color: string; label: string }> = {
  pending: { bg: "#EEF6F8", color: "#66869A", label: "Pending" },
  diajukan: { bg: "#FFF0D5", color: "#A66700", label: "Diajukan" },
  disetujui: { bg: "#D9F3EA", color: "#117A68", label: "Disetujui" },
  ditolak: { bg: "#FDE7E7", color: "#C0362C", label: "Ditolak" },
};

const INITIAL_TRANSACTIONS: Transaction[] = [
  {
    id: "TRX-2026-0091", rm: "RM-2026-000219", name: "Herman Suryadi", module: "Rawat Inap",
    admitDate: "16/07/2026", dischargeDate: "07/09/2026",
    insurance: { type: "BPJS", memberNo: "0001234567890", policyNo: "BPJS-001", validUntil: "31/12/2026", coverageLimit: 50000000, verified: true },
    items: [
      { name: "Biaya Kamar VIP (45 hari)", qty: 45, price: 500000, total: 22500000 },
      { name: "Visite Dokter Spesialis", qty: 45, price: 150000, total: 6750000 },
      { name: "Obat & BHP", qty: 1, price: 3200000, total: 3200000 },
      { name: "Tindakan Medis", qty: 1, price: 2100000, total: 2100000 },
      { name: "Laboratorium", qty: 1, price: 1450000, total: 1450000 },
    ],
    total: 36000000, covered: 30000000, patientShare: 6000000,
    status: "diproses", claimStatus: "diajukan", notes: "Klaim BPJS menunggu verifikasi",
  },
  {
    id: "TRX-2026-0092", rm: "RM-2026-000205", name: "Maya Putri", module: "Rawat Jalan",
    admitDate: "07/09/2026", dischargeDate: "07/09/2026",
    insurance: { type: "Mandiri", memberNo: "", policyNo: "", validUntil: "", coverageLimit: 0, verified: false },
    items: [
      { name: "Pendaftaran & Administrasi", qty: 1, price: 35000, total: 35000 },
      { name: "Konsultasi Dokter Gigi", qty: 1, price: 100000, total: 100000 },
      { name: "Obat", qty: 1, price: 85000, total: 85000 },
    ],
    total: 220000, covered: 0, patientShare: 220000,
    status: "draft", claimStatus: "pending", notes: "",
  },
  {
    id: "TRX-2026-0093", rm: "RM-2026-000201", name: "Rina Wulandari", module: "Rawat Jalan",
    admitDate: "07/09/2026", dischargeDate: "07/09/2026",
    insurance: { type: "BPJS", memberNo: "0009876543210", policyNo: "BPJS-002", validUntil: "31/12/2026", coverageLimit: 10000000, verified: true },
    items: [
      { name: "Pendaftaran & Administrasi", qty: 1, price: 35000, total: 35000 },
      { name: "Konsultasi Spesialis Jantung", qty: 1, price: 250000, total: 250000 },
      { name: "EKG", qty: 1, price: 150000, total: 150000 },
      { name: "Obat", qty: 1, price: 180000, total: 180000 },
    ],
    total: 615000, covered: 615000, patientShare: 0,
    status: "diverifikasi", claimStatus: "diajukan", notes: "",
  },
];

type ActiveView = "list" | "verifikasi" | "billing" | "payment" | "finalisasi";

function formatRp(n: number) {
  return "Rp " + n.toLocaleString("id-ID");
}

export default function Billing() {
  const [transactions, setTransactions] = useState<Transaction[]>(INITIAL_TRANSACTIONS);
  const [selectedId, setSelectedId] = useState<string | null>(null);
  const [activeView, setActiveView] = useState<ActiveView>("list");
  const [toast, setToast] = useState<string | null>(null);
  const [filterStatus, setFilterStatus] = useState<string>("Semua");

  const [verifyForm, setVerifyForm] = useState({ memberNo: "", policyNo: "", validUntil: "", verified: false });
  const [payForm, setPayForm] = useState({ method: "Tunai" as PaymentMethod, confirm: false });

  const selected = transactions.find((t) => t.id === selectedId) ?? null;

  function showToast(msg: string) {
    setToast(msg);
    setTimeout(() => setToast(null), 2500);
  }

  const filtered = filterStatus === "Semua" ? transactions : transactions.filter((t) => t.status === filterStatus);

  function verifyInsurance() {
    if (!verifyForm.memberNo) return;
    setTransactions((prev) => prev.map((t) => t.id === selectedId ? {
      ...t,
      insurance: { ...t.insurance, memberNo: verifyForm.memberNo, policyNo: verifyForm.policyNo, validUntil: verifyForm.validUntil, verified: true },
      status: "diverifikasi",
    } : t));
    showToast("Informasi penjamin berhasil diverifikasi");
    setActiveView("list");
  }

  function approveClaim() {
    setTransactions((prev) => prev.map((t) => t.id === selectedId ? { ...t, claimStatus: "disetujui", status: "diproses" } : t));
    showToast("Klaim disetujui dan diteruskan untuk pembayaran");
    setActiveView("list");
  }

  function processPayment() {
    if (!payForm.confirm) return;
    setTransactions((prev) => prev.map((t) => t.id === selectedId ? { ...t, status: "selesai", claimStatus: "disetujui", paymentMethod: payForm.method } : t));
    showToast("Transaksi berhasil diselesaikan. Notifikasi dikirim ke pasien.");
    setSelectedId(null);
    setActiveView("list");
  }

  const steps: { key: ActiveView; label: string }[] = [
    { key: "verifikasi", label: "Verifikasi Penjamin" },
    { key: "billing", label: "Kelola Billing" },
    { key: "payment", label: "Pembayaran & Klaim" },
    { key: "finalisasi", label: "Finalisasi" },
  ];

  const summary = {
    total: transactions.reduce((s, t) => s + t.total, 0),
    collected: transactions.filter((t) => t.status === "selesai").reduce((s, t) => s + t.patientShare, 0),
    pending: transactions.filter((t) => t.status !== "selesai").reduce((s, t) => s + t.patientShare, 0),
    claimed: transactions.reduce((s, t) => s + t.covered, 0),
  };

  return (
    <div className="flex h-full flex-col">
      {/* Header */}
      <div className="px-6 py-4 border-b" style={{ background: "#fff", borderColor: "#D6E8EE" }}>
        <div className="flex items-center justify-between mb-4">
          <div>
            <h1 className="text-lg font-bold" style={{ color: "#164E63" }}>Billing & Asuransi</h1>
            <p className="text-xs" style={{ color: "#66869A" }}>
              {new Date().toLocaleDateString("id-ID", { weekday: "long", day: "2-digit", month: "long", year: "numeric" })}
            </p>
          </div>
          {selected && (
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
              <button onClick={() => { setSelectedId(null); setActiveView("list"); }} className="text-xs px-2 py-1.5 rounded border" style={{ borderColor: "#D6E8EE", color: "#66869A" }}>✕ Tutup</button>
            </div>
          )}
        </div>
        {/* Summary KPIs */}
        <div className="grid grid-cols-4 gap-3">
          {[
            { label: "Total Tagihan", val: formatRp(summary.total), color: "#0D6E6E" },
            { label: "Terkumpul", val: formatRp(summary.collected), color: "#117A68" },
            { label: "Menunggu Bayar", val: formatRp(summary.pending), color: "#A66700" },
            { label: "Diklaim Asuransi", val: formatRp(summary.claimed), color: "#1D5FA8" },
          ].map((k) => (
            <div key={k.label} className="rounded-lg px-4 py-3" style={{ background: "#F7FBFC", border: "1px solid #D6E8EE" }}>
              <p className="text-xs mb-1" style={{ color: "#66869A" }}>{k.label}</p>
              <p className="text-sm font-bold" style={{ fontFamily: "var(--font-mono)", color: k.color }}>{k.val}</p>
            </div>
          ))}
        </div>
      </div>

      <div className="flex flex-1 overflow-hidden">
        {/* Transaction list */}
        <div className="w-80 shrink-0 border-r flex flex-col" style={{ background: "#fff", borderColor: "#D6E8EE" }}>
          <div className="p-3 border-b" style={{ borderColor: "#D6E8EE" }}>
            <div className="flex flex-wrap gap-1">
              {["Semua", "draft", "diverifikasi", "diproses", "selesai"].map((s) => (
                <button
                  key={s}
                  onClick={() => setFilterStatus(s)}
                  className="text-xs px-2 py-0.5 rounded-full border capitalize transition-colors"
                  style={{
                    background: filterStatus === s ? "#0D6E6E" : "#F7FBFC",
                    color: filterStatus === s ? "#fff" : "#66869A",
                    borderColor: filterStatus === s ? "#0D6E6E" : "#D6E8EE",
                  }}
                >
                  {s === "Semua" ? s : BILLING_STATUS[s as BillingStatus]?.label ?? s}
                </button>
              ))}
            </div>
          </div>
          <div className="flex-1 overflow-y-auto divide-y" style={{ borderColor: "#D6E8EE" }}>
            {filtered.map((t) => {
              const s = BILLING_STATUS[t.status];
              const isSelected = selectedId === t.id;
              return (
                <div
                  key={t.id}
                  onClick={() => { setSelectedId(t.id); setVerifyForm({ memberNo: t.insurance.memberNo, policyNo: t.insurance.policyNo, validUntil: t.insurance.validUntil, verified: t.insurance.verified }); setActiveView("list"); }}
                  className="p-3 cursor-pointer transition-colors"
                  style={{ background: isSelected ? "#EAF6F8" : undefined }}
                >
                  <div className="flex items-start justify-between gap-2">
                    <div className="min-w-0">
                      <p className="text-sm font-semibold truncate" style={{ color: "#164E63" }}>{t.name}</p>
                      <p className="text-xs" style={{ fontFamily: "var(--font-mono)", color: "#66869A" }}>{t.id}</p>
                      <p className="text-xs mt-0.5" style={{ color: "#66869A" }}>{t.module} • {t.insurance.type}</p>
                      <p className="text-xs font-bold mt-1" style={{ fontFamily: "var(--font-mono)", color: "#0D6E6E" }}>{formatRp(t.total)}</p>
                    </div>
                    <span className="text-xs px-1.5 py-0.5 rounded shrink-0 font-medium" style={{ background: s.bg, color: s.color }}>{s.label}</span>
                  </div>
                </div>
              );
            })}
          </div>
          <div className="p-3 border-t" style={{ borderColor: "#D6E8EE" }}>
            <button className="w-full py-2 rounded text-sm font-semibold text-white" style={{ background: "#0D6E6E" }}>
              + Buat Tagihan Baru
            </button>
          </div>
        </div>

        {/* Detail panel */}
        <div className="flex-1 overflow-y-auto p-6">
          {!selected ? (
            <div className="h-full flex items-center justify-center" style={{ color: "#8CA5B0" }}>
              <div className="text-center">
                <p className="text-4xl mb-3">💳</p>
                <p className="font-semibold">Pilih transaksi dari daftar</p>
                <p className="text-sm mt-1">untuk melihat detail billing</p>
              </div>
            </div>
          ) : (
            <div className="space-y-5 max-w-3xl">
              {/* Summary header */}
              {(activeView === "list" || activeView === "billing") && (
                <>
                  <div className="grid grid-cols-3 gap-4">
                    <div className="rounded-lg p-4 col-span-2" style={{ background: "#fff", border: "1px solid #D6E8EE" }}>
                      <h3 className="text-sm font-semibold mb-3" style={{ color: "#164E63" }}>Informasi Pasien & Transaksi</h3>
                      <div className="grid grid-cols-2 gap-x-6 gap-y-1.5">
                        {[
                          ["Pasien", selected.name],
                          ["No. RM", selected.rm],
                          ["Modul", selected.module],
                          ["Tgl Masuk", selected.admitDate],
                          ["Tgl Keluar", selected.dischargeDate],
                          ["No. Transaksi", selected.id],
                        ].map(([k, v]) => (
                          <div key={k} className="flex justify-between text-xs">
                            <span style={{ color: "#66869A" }}>{k}</span>
                            <span className="font-medium" style={{ color: "#315E6E", fontFamily: ["No. RM", "No. Transaksi"].includes(k) ? "var(--font-mono)" : undefined }}>{v}</span>
                          </div>
                        ))}
                      </div>
                    </div>
                    <div className="rounded-lg p-4" style={{ background: "#fff", border: "1px solid #D6E8EE" }}>
                      <h3 className="text-sm font-semibold mb-3" style={{ color: "#164E63" }}>Penjamin</h3>
                      <p className="text-sm font-bold" style={{ color: "#0D6E6E" }}>{selected.insurance.type}</p>
                      {selected.insurance.verified ? (
                        <span className="text-xs px-1.5 py-0.5 rounded font-medium mt-1 inline-block" style={{ background: "#D9F3EA", color: "#117A68" }}>✓ Terverifikasi</span>
                      ) : (
                        <span className="text-xs px-1.5 py-0.5 rounded font-medium mt-1 inline-block" style={{ background: "#FDE7E7", color: "#C0362C" }}>Belum Diverifikasi</span>
                      )}
                      {selected.insurance.memberNo && (
                        <p className="text-xs mt-2" style={{ fontFamily: "var(--font-mono)", color: "#66869A" }}>No. Peserta: {selected.insurance.memberNo}</p>
                      )}
                      {selected.claimStatus && (
                        <div className="mt-2">
                          <p className="text-xs" style={{ color: "#66869A" }}>Status Klaim</p>
                          <span className="text-xs px-1.5 py-0.5 rounded font-medium" style={{ background: CLAIM_STATUS[selected.claimStatus].bg, color: CLAIM_STATUS[selected.claimStatus].color }}>
                            {CLAIM_STATUS[selected.claimStatus].label}
                          </span>
                        </div>
                      )}
                    </div>
                  </div>

                  {/* Billing items */}
                  <div className="rounded-lg overflow-hidden" style={{ background: "#fff", border: "1px solid #D6E8EE" }}>
                    <div className="px-4 py-3 border-b" style={{ borderColor: "#D6E8EE" }}>
                      <h3 className="text-sm font-semibold" style={{ color: "#164E63" }}>Rincian Tagihan</h3>
                    </div>
                    <table className="w-full text-sm">
                      <thead>
                        <tr style={{ background: "#F7FBFC", borderBottom: "1px solid #D6E8EE" }}>
                          {["NAMA LAYANAN", "QTY", "HARGA SATUAN", "TOTAL"].map((h) => (
                            <th key={h} className="text-left text-xs font-semibold px-4 py-2.5" style={{ color: "#66869A" }}>{h}</th>
                          ))}
                        </tr>
                      </thead>
                      <tbody className="divide-y" style={{ borderColor: "#EEF6F8" }}>
                        {selected.items.map((item, i) => (
                          <tr key={i}>
                            <td className="px-4 py-2.5 text-sm" style={{ color: "#315E6E" }}>{item.name}</td>
                            <td className="px-4 py-2.5 text-xs text-center" style={{ color: "#66869A" }}>{item.qty}</td>
                            <td className="px-4 py-2.5 text-xs" style={{ fontFamily: "var(--font-mono)", color: "#66869A" }}>{formatRp(item.price)}</td>
                            <td className="px-4 py-2.5 text-sm font-semibold" style={{ fontFamily: "var(--font-mono)", color: "#315E6E" }}>{formatRp(item.total)}</td>
                          </tr>
                        ))}
                      </tbody>
                    </table>
                    <div className="px-4 py-3 border-t space-y-1.5" style={{ borderColor: "#D6E8EE" }}>
                      {[
                        { label: "Total Tagihan", val: selected.total, bold: true, color: "#164E63" },
                        { label: "Ditanggung Asuransi", val: -selected.covered, bold: false, color: "#117A68" },
                        { label: "Tanggung Pasien", val: selected.patientShare, bold: true, color: "#C0362C" },
                      ].map((row) => (
                        <div key={row.label} className="flex justify-between text-sm">
                          <span style={{ color: "#66869A", fontWeight: row.bold ? 600 : 400 }}>{row.label}</span>
                          <span className={row.bold ? "font-bold" : "font-medium"} style={{ fontFamily: "var(--font-mono)", color: row.color }}>
                            {row.val < 0 ? `- ${formatRp(-row.val)}` : formatRp(row.val)}
                          </span>
                        </div>
                      ))}
                    </div>
                  </div>
                </>
              )}

              {/* Verifikasi form */}
              {activeView === "verifikasi" && (
                <div className="rounded-lg p-5" style={{ background: "#fff", border: "1px solid #D6E8EE" }}>
                  <h3 className="text-base font-semibold mb-4" style={{ color: "#164E63" }}>Verifikasi Informasi Penjamin</h3>
                  <div className="space-y-4">
                    <div>
                      <label className="text-xs font-semibold block mb-1.5" style={{ color: "#315E6E" }}>Jenis Penjamin</label>
                      <div className="px-3 py-2 rounded text-sm" style={{ background: "#F7FBFC", border: "1px solid #D6E8EE", color: "#315E6E" }}>{selected.insurance.type}</div>
                    </div>
                    <div>
                      <label className="text-xs font-semibold block mb-1.5" style={{ color: "#315E6E" }}>No. Peserta / Member *</label>
                      <input
                        className="w-full border rounded px-3 py-2 text-sm"
                        style={{ borderColor: "#D6E8EE", fontFamily: "var(--font-mono)", color: "#315E6E" }}
                        placeholder="Nomor anggota / peserta..."
                        value={verifyForm.memberNo}
                        onChange={(e) => setVerifyForm((f) => ({ ...f, memberNo: e.target.value }))}
                      />
                      {!verifyForm.memberNo && <p className="text-xs mt-1" style={{ color: "#C0362C" }}>⚠ No. Peserta wajib diisi</p>}
                    </div>
                    <div>
                      <label className="text-xs font-semibold block mb-1.5" style={{ color: "#315E6E" }}>No. Polis</label>
                      <input
                        className="w-full border rounded px-3 py-2 text-sm"
                        style={{ borderColor: "#D6E8EE", fontFamily: "var(--font-mono)", color: "#315E6E" }}
                        placeholder="Nomor polis asuransi..."
                        value={verifyForm.policyNo}
                        onChange={(e) => setVerifyForm((f) => ({ ...f, policyNo: e.target.value }))}
                      />
                    </div>
                    <div>
                      <label className="text-xs font-semibold block mb-1.5" style={{ color: "#315E6E" }}>Berlaku Hingga</label>
                      <input
                        type="date"
                        className="w-full border rounded px-3 py-2 text-sm"
                        style={{ borderColor: "#D6E8EE", color: "#315E6E" }}
                        value={verifyForm.validUntil}
                        onChange={(e) => setVerifyForm((f) => ({ ...f, validUntil: e.target.value }))}
                      />
                    </div>
                  </div>
                  <div className="flex justify-end gap-2 mt-5">
                    <button onClick={() => setActiveView("list")} className="px-4 py-2 text-sm rounded border" style={{ borderColor: "#D6E8EE", color: "#315E6E" }}>Batal</button>
                    <button onClick={verifyInsurance} disabled={!verifyForm.memberNo} className="px-5 py-2 text-sm rounded font-semibold text-white disabled:opacity-40" style={{ background: "#0D6E6E" }}>Verifikasi Penjamin</button>
                  </div>
                </div>
              )}

              {/* Pembayaran & Klaim */}
              {activeView === "payment" && (
                <div className="rounded-lg p-5" style={{ background: "#fff", border: "1px solid #D6E8EE" }}>
                  <h3 className="text-base font-semibold mb-4" style={{ color: "#164E63" }}>Pembayaran & Klaim</h3>
                  <div className="rounded p-3 mb-4" style={{ background: "#F7FBFC", border: "1px solid #D6E8EE" }}>
                    <div className="flex justify-between text-sm mb-1">
                      <span style={{ color: "#66869A" }}>Total Tagihan</span>
                      <span className="font-bold" style={{ fontFamily: "var(--font-mono)", color: "#164E63" }}>{formatRp(selected.total)}</span>
                    </div>
                    <div className="flex justify-between text-sm mb-1">
                      <span style={{ color: "#66869A" }}>Tanggungan Asuransi</span>
                      <span className="font-medium" style={{ fontFamily: "var(--font-mono)", color: "#117A68" }}>- {formatRp(selected.covered)}</span>
                    </div>
                    <div className="flex justify-between text-sm border-t pt-2 mt-2" style={{ borderColor: "#D6E8EE" }}>
                      <span className="font-semibold" style={{ color: "#164E63" }}>Tanggung Pasien</span>
                      <span className="font-bold" style={{ fontFamily: "var(--font-mono)", color: "#C0362C" }}>{formatRp(selected.patientShare)}</span>
                    </div>
                  </div>
                  <div className="mb-4">
                    <label className="text-xs font-semibold block mb-2" style={{ color: "#315E6E" }}>Metode Pembayaran</label>
                    <div className="grid grid-cols-3 gap-2">
                      {(["Tunai", "Transfer Bank", "BPJS", "Asuransi", "Kartu Kredit"] as PaymentMethod[]).map((m) => (
                        <button
                          key={m}
                          onClick={() => setPayForm((f) => ({ ...f, method: m }))}
                          className="py-2 px-2 rounded text-xs font-medium border-2 transition-all"
                          style={{
                            background: payForm.method === m ? "#EAF6F8" : "#F7FBFC",
                            color: payForm.method === m ? "#0D6E6E" : "#315E6E",
                            borderColor: payForm.method === m ? "#0D6E6E" : "#D6E8EE",
                          }}
                        >
                          {m}
                        </button>
                      ))}
                    </div>
                  </div>
                  {selected.covered > 0 && (
                    <button
                      onClick={approveClaim}
                      className="w-full py-2 rounded text-sm font-semibold text-white mb-3"
                      style={{ background: "#1D5FA8" }}
                    >
                      Ajukan / Setujui Klaim Asuransi
                    </button>
                  )}
                  <div className="flex items-center gap-2 mb-4">
                    <input
                      type="checkbox"
                      id="confirm-pay"
                      checked={payForm.confirm}
                      onChange={(e) => setPayForm((f) => ({ ...f, confirm: e.target.checked }))}
                    />
                    <label htmlFor="confirm-pay" className="text-xs" style={{ color: "#315E6E" }}>
                      Saya konfirmasi pembayaran sebesar <strong>{formatRp(selected.patientShare)}</strong> telah diterima via <strong>{payForm.method}</strong>
                    </label>
                  </div>
                  <div className="flex justify-end gap-2">
                    <button onClick={() => setActiveView("list")} className="px-4 py-2 text-sm rounded border" style={{ borderColor: "#D6E8EE", color: "#315E6E" }}>Batal</button>
                    <button onClick={processPayment} disabled={!payForm.confirm} className="px-5 py-2 text-sm rounded font-semibold text-white disabled:opacity-40" style={{ background: "#0D6E6E" }}>Konfirmasi Metode Pembayaran</button>
                  </div>
                </div>
              )}

              {/* Finalisasi */}
              {activeView === "finalisasi" && (
                <div className="rounded-lg p-5" style={{ background: "#fff", border: "1px solid #D6E8EE" }}>
                  <h3 className="text-base font-semibold mb-4" style={{ color: "#164E63" }}>Finalisasi Transaksi</h3>
                  <div className="rounded p-4 mb-4 space-y-2" style={{ background: "#F7FBFC", border: "1px solid #D6E8EE" }}>
                    {[
                      ["No. Transaksi", selected.id],
                      ["Pasien", selected.name],
                      ["No. RM", selected.rm],
                      ["Total Tagihan", formatRp(selected.total)],
                      ["Ditanggung Asuransi", formatRp(selected.covered)],
                      ["Tanggung Pasien", formatRp(selected.patientShare)],
                      ["Metode Bayar", selected.paymentMethod ?? "Belum dipilih"],
                      ["Status Klaim", CLAIM_STATUS[selected.claimStatus].label],
                    ].map(([k, v]) => (
                      <div key={k} className="flex justify-between text-xs">
                        <span style={{ color: "#66869A" }}>{k}</span>
                        <span className="font-semibold" style={{ color: "#315E6E", fontFamily: ["No. Transaksi", "No. RM"].includes(k) ? "var(--font-mono)" : undefined }}>{v}</span>
                      </div>
                    ))}
                  </div>
                  <p className="text-xs mb-4" style={{ color: "#66869A" }}>
                    Setelah finalisasi, transaksi terkunci dan dikirim ke laporan keuangan. Pastikan semua data benar.
                  </p>
                  <div className="flex justify-end gap-2">
                    <button onClick={() => setActiveView("payment")} className="px-4 py-2 text-sm rounded border" style={{ borderColor: "#D6E8EE", color: "#315E6E" }}>Kembali</button>
                    <button onClick={processPayment} className="px-5 py-2 text-sm rounded font-semibold text-white" style={{ background: "#0D6E6E" }}>Finalisasi Transaksi</button>
                  </div>
                </div>
              )}
            </div>
          )}
        </div>
      </div>

      {toast && (
        <div className="fixed bottom-5 right-5 px-4 py-3 rounded-lg shadow-lg text-sm font-medium text-white z-50" style={{ background: "#0D6E6E" }}>
          {toast}
        </div>
      )}
    </div>
  );
}
