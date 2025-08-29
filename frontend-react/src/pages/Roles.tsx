import { useEffect, useState } from "react";
import { Card, Table, Button, Modal, Input, Select, message, Popconfirm } from "antd";
import { api } from "../api";

export default function Roles(){
  const [rows,setRows]=useState<any[]>([]);
  const [perms,setPerms]=useState<any[]>([]);
  const [open,setOpen]=useState(false);
  const [permOpen,setPermOpen]=useState(false);
  const [name,setName]=useState("");
  const [current,setCurrent]=useState<any|null>(null);
  const [sel,setSel]=useState<number[]>([]);

  const load=async()=>{ const r=await api(`/roles`); setRows(r.items||[]); };
  const loadPerms=async()=>{ const r=await api(`/permissions`); setPerms(r.items||[]); };
  useEffect(()=>{ load(); loadPerms(); },[]);

  const columns=[
    {title:"ID",dataIndex:"id",width:70},
    {title:"Rol",dataIndex:"name"},
    {title:"İşlem",render:(_:any,r:any)=><>
      <Button onClick={async()=>{ const d=await api(`/roles/${r.id}/permissions`); setSel(d.selected||[]); setCurrent(r); setPermOpen(true); }}>İzinler</Button>
      <Popconfirm title="Silinsin mi?" onConfirm={async()=>{ const ok=await api(`/roles/${r.id}`,{method:"DELETE"}); ok?.ok? (message.success("Silindi"),load()):message.error(ok?.error||"Hata"); }}>
        <Button danger>Sil</Button>
      </Popconfirm>
    </>}
  ];

  const create=async()=>{ const ok=await api(`/roles`,{method:"POST",body:JSON.stringify({name})}); ok?.ok? (message.success("Rol eklendi"), setOpen(false), setName(""), load()) : message.error(ok?.error||"Hata"); };
  const savePerms=async()=>{ const ok=await api(`/roles/${current.id}/permissions`,{method:"POST",body:JSON.stringify({permission_ids:sel})}); ok?.ok?(message.success("İzinler kaydedildi"), setPermOpen(false)):message.error(ok?.error||"Hata"); };

  return <Card title="Roller & İzinler" extra={<Button type="primary" onClick={()=>setOpen(true)}>Yeni Rol</Button>}>
    <Table rowKey="id" dataSource={rows} columns={columns as any} pagination={false}/>
    <Modal title="Yeni Rol" open={open} onOk={create} onCancel={()=>setOpen(false)}>
      <Input value={name} onChange={(e)=>setName(e.target.value)} placeholder="Rol adı (örn: Editor)"/>
    </Modal>
    <Modal title={`İzinler: ${current?.name||''}`} open={permOpen} onOk={savePerms} onCancel={()=>setPermOpen(false)} width={640}>
      <Select mode="multiple" style={{width:"100%"}}
        value={sel} onChange={setSel}
        options={perms.map((p:any)=>({label:p.name,value:p.id}))}/>
    </Modal>
  </Card>;
}
