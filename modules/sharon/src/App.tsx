import { useState } from "react";
import Layout from "./components/Layout";
import Emergency from "./modules/Emergency";
import Outpatient from "./modules/Outpatient";
import Inpatient from "./modules/Inpatient";
import Billing from "./modules/Billing";
import Login from "./pages/Login";
import Register from "./pages/Register";
import Kasir from "./roles/Kasir";
import Resepsionis from "./roles/Resepsionis";
import Perawat from "./roles/Perawat";
import Farmasi from "./roles/Farmasi";
import Admin from "./roles/Admin";
import type { UserInfo } from "./types/user";

type Module = "emergency" | "outpatient" | "inpatient" | "billing";
type AuthPage = "login" | "register" | "app";

export default function App() {
  const params = new URLSearchParams(window.location.search);
  const ssoRole = params.get("sso") === "1" ? params.get("role") : null;
  const ssoUser: UserInfo | null = ssoRole === "kasir" ? {
    username: "kasir", fullName: "Dewi Rahayu, S.E.", role: "Kasir", unit: "Keuangan", initials: "DR"
  } : null;
  const [authPage, setAuthPage] = useState<AuthPage>(ssoUser ? "app" : "login");
  const [user, setUser] = useState<UserInfo | null>(ssoUser);
  const ssoModule = params.get("module") as Module | null;
  const [activeModule, setActiveModule] = useState<Module>(ssoModule ?? "billing");

  // Mode portal: modul dibuka langsung di dalam shell SIMRS (sidebar/header dari portal)
  if (params.get("sso") === "1" && ssoModule && ["emergency", "outpatient", "inpatient", "billing"].includes(ssoModule)) {
    return (
      <div className="h-full flex flex-col overflow-hidden portal-embedded">
        {ssoModule === "emergency" && <Emergency />}
        {ssoModule === "outpatient" && <Outpatient />}
        {ssoModule === "inpatient" && <Inpatient />}
        {ssoModule === "billing" && <Billing />}
      </div>
    );
  }

  function handleLogin(u: UserInfo) {
    setUser(u);
    setAuthPage("app");
  }

  function handleLogout() {
    setUser(null);
    setAuthPage("login");
  }

  if (authPage === "login") {
    return <Login onLogin={handleLogin} onGoRegister={() => setAuthPage("register")} />;
  }

  if (authPage === "register") {
    return <Register onGoLogin={() => setAuthPage("login")} onRegistered={() => setAuthPage("login")} />;
  }

  if (!user) return null;

  // Role-specific pages
  if (user.role === "Kasir") {
    return <Kasir user={user} onLogout={handleLogout} />;
  }

  if (user.role === "Resepsionis / Admisi") {
    return <Resepsionis user={user} onLogout={handleLogout} />;
  }

  if (user.role === "Perawat / Bidan") {
    return <Perawat user={user} onLogout={handleLogout} />;
  }

  if (user.role === "Farmasi") {
    return <Farmasi user={user} onLogout={handleLogout} />;
  }

  if (user.role === "Admin / IT") {
    return <Admin user={user} onLogout={handleLogout} />;
  }

  // Default admin/dokter view — full module access
  return (
    <Layout activeModule={activeModule} onModuleChange={setActiveModule} onLogout={handleLogout} user={user}>
      {activeModule === "emergency" && <Emergency />}
      {activeModule === "outpatient" && <Outpatient />}
      {activeModule === "inpatient" && <Inpatient />}
      {activeModule === "billing" && <Billing />}
    </Layout>
  );
}
