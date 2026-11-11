export type UserRole =
  | "Dokter"
  | "Perawat / Bidan"
  | "Resepsionis / Admisi"
  | "Farmasi"
  | "Kasir"
  | "Admin / IT";

export interface UserInfo {
  username: string;
  fullName: string;
  role: UserRole;
  unit: string;
  initials: string;
}
