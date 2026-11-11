import { useState } from "react";
import logoSrc from "../assets/logo.png";

interface RegisterProps {
  onGoLogin: () => void;
  onRegistered: () => void;
}

type Step = 1 | 2 | 3;

export default function Register({ onGoLogin, onRegistered }: RegisterProps) {
  const [step, setStep] = useState<Step>(1);
  const [form, setForm] = useState({
    fullName: "", nik: "", email: "", phone: "",
    username: "", password: "", confirmPassword: "",
    role: "Dokter", unit: "", employeeId: "",
  });
  const [errors, setErrors] = useState<Record<string, string>>({});
  const [showPass, setShowPass] = useState(false);
  const [loading, setLoading] = useState(false);
  const [done, setDone] = useState(false);

  const ROLES = ["Dokter", "Perawat / Bidan", "Resepsionis / Admisi", "Farmasi", "Kasir", "Admin / IT"];
  const UNITS = ["IGD / Emergency", "Rawat Jalan", "Rawat Inap", "ICU / ICCU", "Laboratorium", "Radiologi", "Farmasi", "Administrasi", "Keuangan"];

  function validateStep1() {
    const e: Record<string, string> = {};
    if (!form.fullName.trim()) e.fullName = "Nama lengkap wajib diisi";
    if (!form.nik || form.nik.length !== 16 || !/^\d+$/.test(form.nik)) e.nik = "NIK harus tepat 16 digit angka";
    if (!form.email.match(/^[^\s@]+@[^\s@]+\.[^\s@]+$/)) e.email = "Format email tidak valid";
    if (!form.phone.match(/^(08|\+628)\d{8,11}$/)) e.phone = "Format nomor HP tidak valid";
    return e;
  }

  function validateStep2() {
    const e: Record<string, string> = {};
    if (!form.username.trim() || form.username.length < 4) e.username = "Username minimal 4 karakter";
    if (!form.password || form.password.length < 8) e.password = "Password minimal 8 karakter";
    if (form.password !== form.confirmPassword) e.confirmPassword = "Password tidak cocok";
    return e;
  }

  function validateStep3() {
    const e: Record<string, string> = {};
    if (!form.employeeId.trim()) e.employeeId = "ID Pegawai wajib diisi";
    if (!form.unit) e.unit = "Unit kerja wajib dipilih";
    return e;
  }

  function next() {
    let e: Record<string, string> = {};
    if (step === 1) e = validateStep1();
    if (step === 2) e = validateStep2();
    if (step === 3) e = validateStep3();
    if (Object.keys(e).length) { setErrors(e); return; }
    setErrors({});
    if (step === 3) {
      setLoading(true);
      setTimeout(() => { setLoading(false); setDone(true); setTimeout(onRegistered, 2000); }, 1400);
    } else {
      setStep((s) => (s + 1) as Step);
    }
  }

  const stepLabels = ["Data Diri", "Akun & Keamanan", "Data Kepegawaian"];
  const progress = (step / 3) * 100;

  function Field({ label, name, type = "text", placeholder, mono = false, required = false }: {
    label: string; name: string; type?: string; placeholder?: string; mono?: boolean; required?: boolean;
  }) {
    return (
      <div>
        <label className="text-xs font-semibold block mb-1.5" style={{ color: "#315E6E" }}>{label}{required && " *"}</label>
        <input
          type={name === "password" && showPass ? "text" : name === "confirmPassword" && showPass ? "text" : type}
          className="w-full border rounded-lg px-3 py-2.5 text-sm outline-none transition-colors"
          style={{ borderColor: errors[name] ? "#C0362C" : "#D6E8EE", color: "#315E6E", background: "#fff", fontFamily: mono ? "var(--font-mono)" : undefined }}
          placeholder={placeholder}
          value={(form as Record<string, string>)[name]}
          onChange={(e) => { setForm((f) => ({ ...f, [name]: e.target.value })); setErrors((er) => ({ ...er, [name]: "" })); }}
        />
        {errors[name] && <p className="text-xs mt-1" style={{ color: "#C0362C" }}>⚠ {errors[name]}</p>}
      </div>
    );
  }

  return (
    <div className="min-h-screen flex" style={{ background: "#F3FAFC" }}>
      {/* Left decorative panel */}
      <div
        className="hidden lg:flex flex-col items-center justify-center flex-1 relative overflow-hidden"
        style={{ background: "linear-gradient(145deg, #0A4D68 0%, #0D6E6E 55%, #148aab 100%)" }}
      >
        <div className="absolute inset-0 pointer-events-none overflow-hidden">
          <div className="absolute rounded-full opacity-10" style={{ width: 500, height: 500, background: "#fff", top: -120, right: -120, animation: "pulse2 7s ease-in-out infinite" }} />
          <div className="absolute rounded-full opacity-10" style={{ width: 280, height: 280, background: "#fff", bottom: -80, left: -80, animation: "pulse2 9s ease-in-out infinite 3s" }} />
          {/* Animated step path */}
          <svg className="absolute inset-0 w-full h-full opacity-5" viewBox="0 0 400 600" fill="none">
            <path d="M50 300 Q200 100 350 300 Q200 500 50 300" stroke="white" strokeWidth="2" fill="none" strokeDasharray="8 4" />
            <path d="M80 400 Q200 200 320 400" stroke="white" strokeWidth="1.5" fill="none" strokeDasharray="6 3" />
          </svg>
        </div>

        <div className="relative z-10 flex flex-col items-center text-center px-12">
          <div className="rounded-full p-4 mb-6 shadow-2xl" style={{ background: "rgba(255,255,255,0.15)", backdropFilter: "blur(12px)", border: "1px solid rgba(255,255,255,0.25)" }}>
            <img src={logoSrc} alt="RS Suka Sehat" className="w-28 h-28 rounded-full" style={{ background: "#fff" }} />
          </div>
          <h1 className="text-3xl font-bold text-white mb-1">RS SUKA SEHAT</h1>
          <p className="text-white/60 text-sm mb-8 italic">Melayani dengan Hati, untuk Hidup yang Lebih Sehat</p>

          {/* Registration steps indicator */}
          <div className="w-full max-w-xs">
            <div className="relative mb-6">
              <div className="absolute top-4 left-4 right-4 h-0.5" style={{ background: "rgba(255,255,255,0.2)" }} />
              <div className="absolute top-4 left-4 h-0.5 transition-all duration-500" style={{ background: "rgba(255,255,255,0.8)", width: `calc(${progress}% - 2rem)` }} />
              <div className="relative flex justify-between">
                {stepLabels.map((label, i) => {
                  const stepNum = i + 1;
                  const isActive = step === stepNum;
                  const isDone = step > stepNum;
                  return (
                    <div key={label} className="flex flex-col items-center gap-2">
                      <div
                        className="w-8 h-8 rounded-full flex items-center justify-center text-xs font-bold transition-all duration-300"
                        style={{
                          background: isDone ? "#D9F3EA" : isActive ? "#fff" : "rgba(255,255,255,0.2)",
                          color: isDone ? "#117A68" : isActive ? "#0D6E6E" : "rgba(255,255,255,0.5)",
                          border: isActive ? "2px solid #fff" : isDone ? "2px solid #D9F3EA" : "none",
                          boxShadow: isActive ? "0 0 0 4px rgba(255,255,255,0.2)" : "none",
                        }}
                      >
                        {isDone ? "✓" : stepNum}
                      </div>
                      <span className="text-xs font-medium" style={{ color: isActive ? "#fff" : "rgba(255,255,255,0.5)" }}>{label}</span>
                    </div>
                  );
                })}
              </div>
            </div>
          </div>

          <p className="text-white/40 text-xs mt-6">Daftarkan akun untuk mengakses SIMRS</p>
        </div>
      </div>

      {/* Right form panel */}
      <div className="flex flex-col justify-center items-center flex-1 px-6 py-10" style={{ maxWidth: 520, margin: "0 auto" }}>
        {done ? (
          <div className="text-center">
            <div className="w-20 h-20 rounded-full flex items-center justify-center text-4xl mx-auto mb-5" style={{ background: "#D9F3EA" }}>✓</div>
            <h2 className="text-2xl font-bold mb-2" style={{ color: "#117A68" }}>Pendaftaran Berhasil!</h2>
            <p className="text-sm" style={{ color: "#66869A" }}>Akun Anda sedang direview oleh Admin.</p>
            <p className="text-sm mt-1" style={{ color: "#66869A" }}>Anda akan dialihkan ke halaman login...</p>
          </div>
        ) : (
          <div className="w-full max-w-sm">
            {/* Mobile logo */}
            <div className="lg:hidden flex flex-col items-center mb-6">
              <img src={logoSrc} alt="RS Suka Sehat" className="w-16 h-16 rounded-full mb-2" style={{ background: "#fff" }} />
              <h1 className="text-lg font-bold" style={{ color: "#0D6E6E" }}>RS SUKA SEHAT</h1>
            </div>

            <div className="flex items-center justify-between mb-1">
              <h2 className="text-2xl font-bold" style={{ color: "#164E63" }}>Buat Akun</h2>
              <span className="text-xs font-semibold px-2 py-0.5 rounded-full" style={{ background: "#EAF6F8", color: "#0D6E6E" }}>
                {step} / 3
              </span>
            </div>
            <p className="text-sm mb-2" style={{ color: "#66869A" }}>{stepLabels[step - 1]}</p>

            {/* Progress bar */}
            <div className="h-1 rounded-full mb-6 overflow-hidden" style={{ background: "#D6E8EE" }}>
              <div className="h-full rounded-full transition-all duration-500" style={{ width: `${progress}%`, background: "linear-gradient(90deg, #0D6E6E, #148aab)" }} />
            </div>

            {/* Step 1 */}
            {step === 1 && (
              <div className="space-y-4">
                <Field label="Nama Lengkap" name="fullName" placeholder="Nama sesuai KTP" required />
                <Field label="NIK (16 digit)" name="nik" placeholder="3271XXXXXXXXXXXX" mono required />
                <Field label="Email" name="email" type="email" placeholder="nama@rssukasehat.co.id" required />
                <Field label="No. Handphone" name="phone" placeholder="08xxxxxxxxxx" required />
              </div>
            )}

            {/* Step 2 */}
            {step === 2 && (
              <div className="space-y-4">
                <Field label="Username" name="username" placeholder="Minimal 4 karakter" required />
                <div>
                  <label className="text-xs font-semibold block mb-1.5" style={{ color: "#315E6E" }}>Password *</label>
                  <div className="relative">
                    <input
                      type={showPass ? "text" : "password"}
                      className="w-full border rounded-lg px-3 pr-10 py-2.5 text-sm outline-none"
                      style={{ borderColor: errors.password ? "#C0362C" : "#D6E8EE", color: "#315E6E", background: "#fff" }}
                      placeholder="Minimal 8 karakter"
                      value={form.password}
                      onChange={(e) => { setForm((f) => ({ ...f, password: e.target.value })); setErrors((er) => ({ ...er, password: "" })); }}
                    />
                    <button type="button" onClick={() => setShowPass((s) => !s)} className="absolute right-3 top-1/2 -translate-y-1/2" style={{ color: "#8CA5B0" }}>{showPass ? "🙈" : "👁"}</button>
                  </div>
                  {errors.password && <p className="text-xs mt-1" style={{ color: "#C0362C" }}>⚠ {errors.password}</p>}
                  {form.password && (
                    <div className="flex gap-1 mt-1.5">
                      {[form.password.length >= 8, /[A-Z]/.test(form.password), /\d/.test(form.password)].map((ok, i) => (
                        <div key={i} className="h-1 flex-1 rounded-full" style={{ background: ok ? "#117A68" : "#D6E8EE" }} />
                      ))}
                    </div>
                  )}
                </div>
                <div>
                  <label className="text-xs font-semibold block mb-1.5" style={{ color: "#315E6E" }}>Konfirmasi Password *</label>
                  <input
                    type={showPass ? "text" : "password"}
                    className="w-full border rounded-lg px-3 py-2.5 text-sm outline-none"
                    style={{ borderColor: errors.confirmPassword ? "#C0362C" : "#D6E8EE", color: "#315E6E", background: "#fff" }}
                    placeholder="Ulangi password"
                    value={form.confirmPassword}
                    onChange={(e) => { setForm((f) => ({ ...f, confirmPassword: e.target.value })); setErrors((er) => ({ ...er, confirmPassword: "" })); }}
                  />
                  {errors.confirmPassword && <p className="text-xs mt-1" style={{ color: "#C0362C" }}>⚠ {errors.confirmPassword}</p>}
                </div>
              </div>
            )}

            {/* Step 3 */}
            {step === 3 && (
              <div className="space-y-4">
                <Field label="ID Pegawai" name="employeeId" placeholder="Contoh: PEG-2026-001" mono required />
                <div>
                  <label className="text-xs font-semibold block mb-1.5" style={{ color: "#315E6E" }}>Role / Jabatan *</label>
                  <select
                    className="w-full border rounded-lg px-3 py-2.5 text-sm outline-none"
                    style={{ borderColor: "#D6E8EE", color: "#315E6E", background: "#fff" }}
                    value={form.role}
                    onChange={(e) => setForm((f) => ({ ...f, role: e.target.value }))}
                  >
                    {ROLES.map((r) => <option key={r}>{r}</option>)}
                  </select>
                </div>
                <div>
                  <label className="text-xs font-semibold block mb-1.5" style={{ color: "#315E6E" }}>Unit Kerja *</label>
                  <select
                    className="w-full border rounded-lg px-3 py-2.5 text-sm outline-none"
                    style={{ borderColor: errors.unit ? "#C0362C" : "#D6E8EE", color: "#315E6E", background: "#fff" }}
                    value={form.unit}
                    onChange={(e) => { setForm((f) => ({ ...f, unit: e.target.value })); setErrors((er) => ({ ...er, unit: "" })); }}
                  >
                    <option value="">— Pilih unit kerja —</option>
                    {UNITS.map((u) => <option key={u}>{u}</option>)}
                  </select>
                  {errors.unit && <p className="text-xs mt-1" style={{ color: "#C0362C" }}>⚠ {errors.unit}</p>}
                </div>
              </div>
            )}

            {/* Navigation buttons */}
            <div className="flex gap-3 mt-6">
              {step > 1 && (
                <button
                  onClick={() => setStep((s) => (s - 1) as Step)}
                  className="flex-1 py-2.5 rounded-lg text-sm font-semibold border"
                  style={{ borderColor: "#D6E8EE", color: "#315E6E", background: "#fff" }}
                >
                  ← Kembali
                </button>
              )}
              <button
                onClick={next}
                disabled={loading}
                className="flex-1 py-2.5 rounded-lg text-sm font-semibold text-white transition-all"
                style={{ background: loading ? "#7ab8c8" : "linear-gradient(135deg, #0D6E6E, #0A4D68)", boxShadow: "0 4px 15px rgba(15,116,144,0.3)" }}
              >
                {loading ? (
                  <span className="flex items-center justify-center gap-2">
                    <span className="inline-block w-4 h-4 border-2 border-white/30 border-t-white rounded-full animate-spin" />
                    Mendaftarkan...
                  </span>
                ) : step === 3 ? "Daftarkan Akun" : "Lanjut →"}
              </button>
            </div>

            <div className="mt-5 text-center">
              <p className="text-xs" style={{ color: "#8CA5B0" }}>
                Sudah punya akun?{" "}
                <button onClick={onGoLogin} className="font-semibold underline" style={{ color: "#0D6E6E" }}>
                  Masuk sekarang
                </button>
              </p>
            </div>
          </div>
        )}
      </div>

      <style>{`
        @keyframes pulse2 {
          0%, 100% { transform: scale(1); opacity: 0.1; }
          50% { transform: scale(1.08); opacity: 0.15; }
        }
      `}</style>
    </div>
  );
}
