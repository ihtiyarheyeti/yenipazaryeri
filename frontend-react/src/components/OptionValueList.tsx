import { useEffect, useState } from "react";
import { api } from "../api";
import { Table, Input, Pagination, Card } from "antd";

export default function OptionValueList({ optionId }: { optionId?:number }) {
  const [items,setItems] = useState<any[]>([]);
  const [total,setTotal] = useState(0);
  const [page,setPage] = useState(1);
  const [search,setSearch] = useState("");
  const pageSize = 10;

  const load = async () => {
    const params = new URLSearchParams({
      page: String(page),
      pageSize: String(pageSize),
      q: search
    });
    if (optionId) params.set("option_id", String(optionId));
    const d = await api(`/option-values?${params.toString()}`);
    if (d?.ok) {
      setItems(d.items || []);
      setTotal(d.total || 0);
    }
  };

  useEffect(()=>{ load(); },[optionId, page, search]);

  const columns=[
    {title:"ID",dataIndex:"id",width:80},
    {title:"Değer",dataIndex:"value"},
    {title:"Option ID",dataIndex:"option_id"}
  ];

  return (
    <Card title="Özellik Değerleri" className="shadow">
      <Input.Search placeholder="Değer ara..." allowClear onSearch={(v)=>{setPage(1);setSearch(v)}} className="mb-3"/>
      <Table rowKey="id" columns={columns as any} dataSource={items} pagination={false}/>
      <div className="mt-2 flex justify-end">
        <Pagination current={page} pageSize={pageSize} total={total} onChange={setPage}/>
      </div>
    </Card>
  );
}
