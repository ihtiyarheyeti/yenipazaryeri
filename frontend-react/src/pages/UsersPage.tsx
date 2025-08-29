import { useEffect, useState } from "react";
import { api } from "../api";
import { Card, Table, Input, Pagination, Tag, Button, Space } from "antd";
import UserRoleModal from "../components/UserRoleModal";

export default function UsersPage(){
  const [rows, setRows] = useState<any[]>([]);
  const [total, setTotal] = useState(0);
  const [page, setPage] = useState(1);
  const [search, setSearch] = useState("");
  const pageSize = 10;

  const [open, setOpen] = useState(false);
  const [selectedUser, setSelectedUser] = useState<any|null>(null);

  const load = async ()=>{
    const q = `?tenant_id=1&page=${page}&pageSize=${pageSize}&q=${encodeURIComponent(search)}`;
    const d = await api(`/users${q}`);
    if(d?.ok){ setRows(d.items||[]); setTotal(d.total||0); }
  };

  useEffect(()=>{ load(); },[page,search]);

  const columns = [
    { title:"ID", dataIndex:"id", width:70 },
    { title:"Ad", dataIndex:"name" },
    { title:"E-posta", dataIndex:"email" },
    { title:"Tenant", dataIndex:"tenant_id", width:90 },
    { title:"Roller", render: (_:any, r:any) => (
        (r.roles||[]).length ? r.roles.map((n:string)=><Tag key={n}>{n}</Tag>) : <span style={{opacity:.6}}>—</span>
      )
    },
    { title:"İşlem", render: (_:any, r:any) => (
        <Space>
          <Button onClick={()=>{ setSelectedUser(r); setOpen(true); }}>Rol Ata</Button>
        </Space>
      )
    }
  ];

  return (
    <Card title="Kullanıcılar">
      <div className="flex flex-wrap gap-2 mb-3">
        <Input.Search placeholder="Ad/E-posta ara..." allowClear onSearch={(v)=>{ setPage(1); setSearch(v); }} style={{maxWidth:300}}/>
      </div>
      <Table rowKey="id" dataSource={rows} columns={columns as any} pagination={false}/>
      <div className="mt-3 flex justify-end">
        <Pagination current={page} pageSize={pageSize} total={total} onChange={setPage}/>
      </div>

      <UserRoleModal
        open={open}
        user={selectedUser}
        onClose={()=>{ setOpen(false); setSelectedUser(null); load(); }}
      />
    </Card>
  );
}
