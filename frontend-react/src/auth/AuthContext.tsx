import { createContext, useContext, useEffect, useState } from "react";
import { api } from "../api";

type User = { id:number; tenant_id:number; name:string; email:string; role:string } | null;

type AuthCtx = {
  token: string | null;
  user: User;
  permissions: string[];
  can: (perm: string) => boolean;
  login: (token:string, user:User) => void;
  logout: () => void;
  refreshMe: () => Promise<void>;
};

const Ctx = createContext<AuthCtx>({ token:null, user:null, permissions:[], can:()=>false, login:()=>{}, logout:()=>{}, refreshMe: async()=>{} });

export function AuthProvider({ children }: { children: React.ReactNode }) {
  const [token, setToken] = useState<string | null>(localStorage.getItem("token"));
  const [user, setUser] = useState<User>(null);
  const [permissions, setPermissions] = useState<string[]>([]);

  useEffect(() => {
    if (token) localStorage.setItem("token", token); else localStorage.removeItem("token");
  }, [token]);

  // Token varsa /auth/me ile kullanıcıyı getir
  useEffect(() => {
    if (!token) { setUser(null); return; }
    (async () => { await refreshMe(); })();
  }, [token]);

  // Kullanıcı değiştiğinde permissions'ları çek
  useEffect(() => {
    if (user) {
      (async () => {
        const r = await api(`/user-permissions?user_id=${user.id}`);
        if (r?.ok) setPermissions(r.permissions || []);
      })();
    }
  }, [user]);

  const can = (perm: string) => permissions.includes(perm);
  
  const login = (t:string, u:User) => { setToken(t); setUser(u); };
  const logout = () => { setToken(null); setUser(null); setPermissions([]); };

  const refreshMe = async () => {
    try {
      const r = await api("/auth/me");
      if (r?.ok) setUser(r.user);
    } catch {}
  };

  return <Ctx.Provider value={{ token, user, permissions, can, login, logout, refreshMe }}>{children}</Ctx.Provider>;
}

export function useAuth() { return useContext(Ctx); }
