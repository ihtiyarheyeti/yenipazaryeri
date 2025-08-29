import { useEffect, useState } from "react";
import { api } from "../api";
import { Table, Input, Pagination, Card } from "antd";

export default function OptionList() {
  const [items,setItems] = useState<any[]>([]);
  const [total,setTotal] = useState(0);
  const [page,setPage] = useState(1);
  const [search,setSearch] = useState("");
  const pageSize = 10;

  const load = async () => {
    const q = `?tenant_id=1&page=${page}&pageSize=${pageSize}&q=${encodeURIComponent(search)}`;
    const d = await api(`/options${q}`);
    if (d?.ok) {
      setItems(d.items || []);
      setTotal(d.total || 0);
    }
  };

  useEffect(()=>{ load(); },[page,search]);

  const columns = [
    {title:"ID", dataIndex:"id", width:80},
    {title:"Ad", dataIndex:"name"},
    {title:"Tenant", dataIndex:"tenant_id"},
  ];

  return (
    <Card title="Özellikler" className="shadow">
      <Input.Search placeholder="Ad ile ara..." allowClear onSearch={(v)=>{setPage(1);setSearch(v)}} className="mb-3"/>
      <Table rowKey="id" columns={columns as any} dataSource={items} pagination={false}/>
      <div className="mt-2 flex justify-end">
        <Pagination current={page} pageSize={pageSize} total={total} onChange={setPage}/>
      </div>
    </Card>
  );
}
