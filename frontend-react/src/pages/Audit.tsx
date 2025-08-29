import { useEffect, useState } from "react";
import { Card, Table } from "antd";
import { api } from "../api";

export default function Audit(){
  const [rows,setRows]=useState<any[]>([]);
  useEffect(()=>{ api('/audit-logs').then(r=>setRows(r.items||[])); },[]);
  return <Card title="Audit Log">
    <Table rowKey="id" pagination={false} dataSource={rows} columns={[
      {title:'ID',dataIndex:'id',width:90},
      {title:'Kullanıcı',render:(_:any,r:any)=> r.name? `${r.name} <${r.email}>` : '-'},
      {title:'Aksiyon',dataIndex:'action'},
      {title:'Kaynak',dataIndex:'resource'},
      {title:'Zaman',dataIndex:'created_at'},
    ] as any}/>
  </Card>;
}
