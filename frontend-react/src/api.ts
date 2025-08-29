export const API_BASE = "http://localhost:8000";

export function currentTenantId(){
  // app.localhost → 'app'; prod → ilk parçayı al
  const host = window.location.hostname; // "app.localhost"
  const sub = host.split('.')[0] || 'app';
  // local dev'te istersen override: window.__TENANT_SUB__ = 'demo';
  const effective = (window as any).__TENANT_SUB__ || sub;
  // backend subdomain eşlemesi DB'de: burada 1 döndürmek yeterli; server tarafı zaten resolve ediyor.
  return 1; 
}

let refreshing = false;
let waiters: ((t:string|null)=>void)[] = [];

async function refreshToken(): Promise<string|null>{
  if(refreshing){ return new Promise(res=>waiters.push(res)); }
  refreshing = true;
  try{
    const rt = localStorage.getItem("refresh_token");
    if(!rt){ refreshing=false; waiters.forEach(w=>w(null)); waiters=[]; return null; }
    const r = await fetch(`${API_BASE}/auth/refresh`, {
      method:'POST',
      headers:{'Content-Type':'application/json'},
      body: JSON.stringify({refresh_token: rt})
    });
    const data = await r.json();
    if(data?.ok){
      localStorage.setItem("token", data.token);
      localStorage.setItem("refresh_token", data.refresh_token);
      return data.token;
    }
    return null;
  } finally {
    refreshing=false;
    waiters.forEach(w=>w(localStorage.getItem("token")));
    waiters=[];
  }
}

export async function api(path: string, opts: RequestInit = {}) {
  const headers: any = { "Content-Type": "application/json", ...(opts.headers||{}) };
  const token = localStorage.getItem("token");
  if (token) headers["Authorization"] = `Bearer ${token}`;
  let res = await fetch(`${API_BASE}${path}`, { ...opts, headers });

  if (res.status === 401) {
    const nt = await refreshToken();
    if(nt){
      headers["Authorization"] = `Bearer ${nt}`;
      res = await fetch(`${API_BASE}${path}`, { ...opts, headers });
    } else {
      localStorage.removeItem("token"); localStorage.removeItem("refresh_token");
      if(!path.includes('/auth/')) window.location.href='/login';
    }
  }
  const ct = res.headers.get("content-type")||"";
  const data = ct.includes("application/json") ? await res.json() : await res.text();
  if (!res.ok && typeof data === "object") throw data;
  return data;
}

export function setToken(t?:string){ 
  if(t) localStorage.setItem("token",t); 
  else localStorage.removeItem("token"); 
}

export async function getBranding(tenantId=1){ 
  try{ 
    return (await api(`/tenant/branding?tenant_id=${tenantId}`)).item; 
  }catch{
    return null;
  } 
}
