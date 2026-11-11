import { useState } from "react";
import logoSrc from "../assets/logo.png";
import type { UserInfo, UserRole } from "../types/user";

interface LoginProps {
  onLogin: (user: UserInfo) => void;
  onGoRegister: () => void;
}

const ROLE_USERS: Record<UserRole, { fullName: string; unit: string }> = {
  "Dokter": { fullName: "dr. Ahmad Fauzi, Sp.PD", unit: "Rawat Jalan" },
  "Perawat / Bidan": { fullName: "Ns. Rina Wulandari, S.Kep", unit: "IGD / Emergency" },
  "Resepsionis / Admisi": { fullName: "Sari Dewi Kusuma", unit: "Admisi" },
  "Farmasi": { fullName: "apt. Budi Santoso, S.Farm", unit: "Instalasi Farmasi" },
  "Kasir": { fullName: "Dewi Rahayu, S.E.", unit: "Keuangan" },
  "Admin / IT": { fullName: "Rizky Pratama, S.Kom", unit: "IT & Sistem" },
};

export default function Login({ onLogin, onGoRegister }: LoginProps) {
  const [form, setForm] = useState({ username: "admin", password: "simrs123", role: "Dokter" as UserRole });
  const [errors, setErrors] = useState<Record<string, string>>({});
  const [loading, setLoading] = useState(false);
  const [showPass, setShowPass] = useState(false);

  const ROLES = Object.keys(ROLE_USERS) as UserRole[];

  function validate() {
    const e: Record<string, string> = {};
    if (!form.username.trim()) e.username = "Username wajib diisi";
    if (!form.password) e.password = "Password wajib diisi";
    else if (form.password.length < 6) e.password = "Password minimal 6 karakter";
    return e;
  }

  function handleSubmit(ev: React.FormEvent) {
    ev.preventDefault();
    const e = validate();
    if (Object.keys(e).length) { setErrors(e); return; }
    setLoading(true);
    setTimeout(() => {
      setLoading(false);
      const info = ROLE_USERS[form.role];
      const initials = info.fullName.split(" ").slice(0, 2).map((w) => w[0]).join("").toUpperCase();
      onLogin({ username: form.username, fullName: info.fullName, role: form.role, unit: info.unit, initials });
    }, 1000);
  }

  return (
    <div className="min-h-screen flex" style={{ background: "#F3FAFC" }}>
      {/* Left decorative panel */}
      <div
        className="hidden lg:flex flex-col items-center justify-center flex-1 relative overflow-hidden"
        style={{ background: "linear-gradient(145deg, #0A4D68 0%, #0D6E6E 45%, #148aab 100%)" }}
      >
        <div className="absolute inset-0 pointer-events-none">
          <div className="absolute rounded-full opacity-10" style={{ width: 420, height: 420, background: "#fff", top: -80, left: -100, animation: "pulse 6s ease-in-out infinite" }} />
          <div className="absolute rounded-full opacity-10" style={{ width: 300, height: 300, background: "#fff", bottom: -60, right: -60, animation: "pulse 8s ease-in-out infinite 2s" }} />
          <div className="absolute rounded-full opacity-5" style={{ width: 200, height: 200, background: "#fff", top: "40%", right: "10%", animation: "pulse 5s ease-in-out infinite 1s" }} />
          {[
            { icon: "🫀", top: "15%", left: "15%", delay: "0s" },
            { icon: "💊", top: "25%", right: "12%", delay: "1.5s" },
            { icon: "🩺", bottom: "30%", left: "12%", delay: "0.8s" },
            { icon: "🔬", bottom: "18%", right: "18%", delay: "2s" },
            { icon: "🩻", top: "60%", left: "25%", delay: "1.2s" },
          ].map((f, i) => (
            <div key={i} className="absolute text-2xl opacity-20"
              style={{ top: f.top, left: (f as { left?: string }).left, right: (f as { right?: string }).right, bottom: (f as { bottom?: string }).bottom, animation: `float 4s ease-in-out infinite ${f.delay}` }}>
              {f.icon}
            </div>
          ))}
        </div>
        <div className="relative z-10 flex flex-col items-center text-center px-12">
          <div className="rounded-full p-4 mb-6 shadow-2xl" style={{ background: "rgba(255,255,255,0.15)", backdropFilter: "blur(12px)", border: "1px solid rgba(255,255,255,0.25)" }}>
            <img src={logoSrc} alt="RS Suka Sehat" className="w-36 h-36 rounded-full" style={{ background: "#fff" }} />
          </div>
          <h1 className="text-4xl font-bold text-white mb-1" style={{ letterSpacing: "-0.5px" }}>RS SUKA SEHAT</h1>
          <p className="text-white/70 text-sm mb-8 italic">Melayani dengan Hati, untuk Hidup yang Lebih Sehat</p>
          <div className="grid grid-cols-3 gap-4 w-full max-w-xs">
            {[{ num: "120+", label: "Tempat Tidur" }, { num: "45+", label: "Dokter Spesialis" }, { num: "24/7", label: "Layanan IGD" }].map((s) => (
              <div key={s.label} className="text-center rounded-xl py-3 px-2" style={{ background: "rgba(255,255,255,0.12)", border: "1px solid rgba(255,255,255,0.2)" }}>
                <p className="text-xl font-bold text-white">{s.num}</p>
                <p className="text-xs text-white/60 leading-tight mt-0.5">{s.label}</p>
              </div>
            ))}
          </div>
          <p className="text-white/50 text-xs mt-10">Sistem Informasi Manajemen Rumah Sakit</p>
        </div>
      </div>

      {/* Right login panel */}
      <div className="flex flex-col justify-center items-center flex-1 px-6 py-12">
        <div className="lg:hidden flex flex-col items-center mb-8">
          <img src={logoSrc} alt="RS Suka Sehat" className="w-20 h-20 rounded-full mb-3" style={{ background: "#fff" }} />
          <h1 className="text-xl font-bold" style={{ color: "#0D6E6E" }}>RS SUKA SEHAT</h1>
        </div>
        <div className="w-full max-w-sm">
          <h2 className="text-2xl font-bold mb-1" style={{ color: "#164E63" }}>Selamat Datang</h2>
          <p className="text-sm mb-8" style={{ color: "#66869A" }}>Masuk ke akun Anda untuk melanjutkan</p>
          <form onSubmit={handleSubmit} className="space-y-4" noValidate>
            <div>
              <label className="text-xs font-semibold block mb-1.5" style={{ color: "#315E6E" }}>Username *</label>
              <div className="relative">
                <span className="absolute left-3 top-1/2 -translate-y-1/2">👤</span>
                <input type="text" className="w-full border rounded-lg pl-9 pr-4 py-2.5 text-sm outline-none"
                  style={{ borderColor: errors.username ? "#C0362C" : "#D6E8EE", color: "#315E6E", background: "#fff" }}
                  placeholder="Masukkan username..." value={form.username}
                  onChange={(e) => { setForm((f) => ({ ...f, username: e.target.value })); setErrors((er) => ({ ...er, username: "" })); }} />
              </div>
              {errors.username && <p className="text-xs mt-1" style={{ color: "#C0362C" }}>⚠ {errors.username}</p>}
            </div>
            <div>
              <label className="text-xs font-semibold block mb-1.5" style={{ color: "#315E6E" }}>Password *</label>
              <div className="relative">
                <span className="absolute left-3 top-1/2 -translate-y-1/2">🔒</span>
                <input type={showPass ? "text" : "password"} className="w-full border rounded-lg pl-9 pr-10 py-2.5 text-sm outline-none"
                  style={{ borderColor: errors.password ? "#C0362C" : "#D6E8EE", color: "#315E6E", background: "#fff" }}
                  placeholder="Masukkan password..." value={form.password}
                  onChange={(e) => { setForm((f) => ({ ...f, password: e.target.value })); setErrors((er) => ({ ...er, password: "" })); }} />
                <button type="button" onClick={() => setShowPass((s) => !s)} className="absolute right-3 top-1/2 -translate-y-1/2" style={{ color: "#8CA5B0" }}>{showPass ? "🙈" : "👁"}</button>
              </div>
              {errors.password && <p className="text-xs mt-1" style={{ color: "#C0362C" }}>⚠ {errors.password}</p>}
            </div>
            <div>
              <label className="text-xs font-semibold block mb-1.5" style={{ color: "#315E6E" }}>Login sebagai</label>
              <div className="relative">
                <span className="absolute left-3 top-1/2 -translate-y-1/2">🏥</span>
                <select className="w-full border rounded-lg pl-9 pr-4 py-2.5 text-sm outline-none appearance-none"
                  style={{ borderColor: "#D6E8EE", color: "#315E6E", background: "#fff" }}
                  value={form.role} onChange={(e) => setForm((f) => ({ ...f, role: e.target.value as UserRole }))}>
                  {ROLES.map((r) => <option key={r}>{r}</option>)}
                </select>
              </div>
            </div>
            <button type="submit" disabled={loading} className="w-full py-3 rounded-lg font-semibold text-white text-sm transition-all mt-2"
              style={{ background: loading ? "#7ab8c8" : "linear-gradient(135deg, #0D6E6E, #0A4D68)", boxShadow: "0 4px 15px rgba(15,116,144,0.35)" }}>
              {loading ? (
                <span className="flex items-center justify-center gap-2">
                  <span className="inline-block w-4 h-4 border-2 border-white/30 border-t-white rounded-full animate-spin" />
                  Memverifikasi...
                </span>
              ) : "Masuk ke SIMRS"}
            </button>
          </form>
          <div className="mt-5 text-center">
            <p className="text-xs" style={{ color: "#8CA5B0" }}>
              Belum punya akun?{" "}
              <button onClick={onGoRegister} className="font-semibold underline" style={{ color: "#0D6E6E" }}>Daftar sekarang</button>
            </p>
          </div>
          <div className="mt-6 pt-5 border-t flex items-center justify-center gap-2" style={{ borderColor: "#D6E8EE" }}>
            <span className="text-xs" style={{ color: "#8CA5B0" }}>🔐 Akses dibatasi sesuai hak role pengguna (RBAC)</span>
          </div>
        </div>
      </div>
      <style>{`
        @keyframes float { 0%,100%{transform:translateY(0)} 50%{transform:translateY(-12px)} }
        @keyframes pulse { 0%,100%{transform:scale(1);opacity:.1} 50%{transform:scale(1.05);opacity:.15} }
      `}</style>
    </div>
  );
}
