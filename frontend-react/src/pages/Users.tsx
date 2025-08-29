import { useEffect, useState } from "react";
import { Card, Table, Input, Button, Modal, Select, message } from "antd";
import { api } from "../api";

export default function Users(){
  const [rows,setRows]=useState<any[]>([]);
  const [roles,setRoles]=useState<any[]>([]);
  const [open,setOpen]=useState(false);
  const [target,setTarget]=useState<any|null>(null);
  const [sel,setSel]=useState<number[]>([]);
  const [q,setQ]=useState("");

  const load=async()=>{ const r=await api(`/users?q=${encodeURIComponent(q)}`); setRows(r.items||[]); };
  const loadRoles=async()=>{ const r=await api(`/roles`); setRoles(r.items||[]); };
  useEffect(()=>{ load(); loadRoles(); },[]);

  const columns=[
    {title:"ID",dataIndex:"id",width:70},
    {title:"Ad",dataIndex:"name"},
    {title:"Email",dataIndex:"email"},
    {title:"Roller",dataIndex:"roles"},
    {title:"İşlem",render:(_:any,r:any)=><Button onClick={()=>{setTarget(r); setSel([]); setOpen(true);}}>Rolleri Ata</Button>}
  ];

  const save=async()=>{
    const r=await api(`/users/${target.id}/roles`,{method:"POST",body:JSON.stringify({role_ids:sel})});
    r?.ok? (message.success("Roller güncellendi"), setOpen(false), load()) : message.error(r?.error||"Hata");
  };

  return <Card title="Kullanıcılar" extra={
    <div style={{display:"flex",gap:8}}>
      <Input.Search placeholder="Ad/Email ara" onSearch={(v)=>{setQ(v); load();}} allowClear />
      <Button onClick={async()=>{
        const email = prompt('Davet e-postası'); if(!email) return;
        const roles = await api('/roles'); const rid = roles.items?.find((x:any)=>x.name==='Editor')?.id || roles.items?.[0]?.id;
        const r = await api('/invites',{method:"POST", body: JSON.stringify({email, role_id: rid})});
        r?.ok? message.success('Davet gönderildi (storage/mails.log)'):message.error(r?.error||'Hata');
      }}>Davet Gönder</Button>
    </div>
  }>
    <Table rowKey="id" columns={columns as any} dataSource={rows} pagination={false}/>
    <Modal title={`Roller: ${target?.name||''}`} open={open} onOk={save} onCancel={()=>setOpen(false)}>
      <Select mode="multiple" style={{width:"100%"}} value={sel} onChange={setSel}
        options={roles.map((r:any)=>({label:r.name,value:r.id}))}/>
    </Modal>
  </Card>;
}
