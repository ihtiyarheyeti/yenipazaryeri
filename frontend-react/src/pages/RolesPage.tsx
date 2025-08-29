import { useEffect, useState } from "react";
import { api } from "../api";
import { Card, Table, Tag } from "antd";

export default function RolesPage(){
  const [roles,setRoles]=useState<any[]>([]);
  const [perms,setPerms]=useState<any[]>([]);
  useEffect(()=>{
    (async()=>{
      const r=await api(`/roles`); if(r?.ok) setRoles(r.items);
      const p=await api(`/permissions`); if(p?.ok) setPerms(p.items);
    })();
  },[]);
  return (
    <Card title="Roller & Yetkiler">
      <h3>Roller</h3>
      <Table rowKey="id" dataSource={roles} columns={[{title:"ID",dataIndex:"id"},{title:"Name",dataIndex:"name"}]} pagination={false}/>
      <h3 className="mt-4">Yetkiler</h3>
      <Table rowKey="id" dataSource={perms} columns={[{title:"ID",dataIndex:"id"},{title:"Name",dataIndex:"name"}]} pagination={false}/>
    </Card>
  );
}
