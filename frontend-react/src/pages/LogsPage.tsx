import { useEffect, useState } from "react";
import { api } from "../api";
import { Table, Pagination, Card, Tag, Select } from "antd";

export default function LogsPage() {
  const [rows,setRows]=useState<any[]>([]);
  const [page,setPage]=useState(1);
  const [total,setTotal]=useState(0);
  const [type,setType]=useState<string|undefined>(undefined);
  const pageSize=10;

  const load=async()=>{
    const params=new URLSearchParams({
      tenant_id:"1",
      page:String(page),
      pageSize:String(pageSize)
    });
    if(type) params.set("type",type);
    const d=await api(`/logs?${params.toString()}`);
    if(d?.ok){ setRows(d.items||[]); setTotal(d.total||0); }
  };
  useEffect(()=>{ load(); },[page,type]);

  const columns=[
    {title:"ID",dataIndex:"id",width:60},
    {title:"Product",dataIndex:"product_id"},
    {title:"Tip",dataIndex:"type"},
    {title:"Durum",dataIndex:"status",render:(v:string)=><Tag color={v==='success'?'green':'red'}>{v}</Tag>},
    {title:"Mesaj",dataIndex:"message"},
    {title:"Tarih",dataIndex:"created_at"}
  ];

  return (
    <Card title="Loglar">
      <div className="mb-3">
        <Select
          allowClear
          placeholder="Tip filtrele (ör. product, variant, trendyol)"
          style={{width:240}}
          onChange={(v)=>{setPage(1);setType(v)}}
          options={[
            {label:"Product",value:"product"},
            {label:"Variant",value:"variant"},
            {label:"Trendyol",value:"trendyol"},
            {label:"Woo",value:"woo"},
          ]}
        />
      </div>
      <Table rowKey="id" columns={columns as any} dataSource={rows} pagination={false}/>
      <div className="mt-3 flex justify-end">
        <Pagination current={page} pageSize={pageSize} total={total} onChange={setPage}/>
      </div>
    </Card>
  );
}
