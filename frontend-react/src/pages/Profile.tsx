import { useEffect, useState } from "react";
import { api } from "../api";
import { Card, Descriptions, Spin, message } from "antd";

export default function Profile() {
  const [user, setUser] = useState<any>(null);
  const [loading, setLoading] = useState(true);

  useEffect(()=>{
    (async ()=>{
      const r = await api("/auth/me");
      if (r?.ok) setUser(r.user); else message.error(r?.error || "Profil alınamadı");
      setLoading(false);
    })();
  },[]);

  if (loading) return <div style={{display:"grid",placeItems:"center",height:"60vh"}}><Spin/></div>;

  return (
    <Card title="Profil Bilgileri">
      <Descriptions bordered column={1}>
        <Descriptions.Item label="ID">{user?.id}</Descriptions.Item>
        <Descriptions.Item label="Tenant">{user?.tenant_id}</Descriptions.Item>
        <Descriptions.Item label="Ad">{user?.name}</Descriptions.Item>
        <Descriptions.Item label="E-posta">{user?.email}</Descriptions.Item>
        <Descriptions.Item label="Rol">{user?.role}</Descriptions.Item>
      </Descriptions>
    </Card>
  );
}
