import { useEffect, useState } from "react";
import { Card, Table } from "antd";
import { api } from "../api";

export default function Logs(){
  const [rows, setRows] = useState<any[]>([]);
  
  useEffect(() => { 
    (async() => { 
      try{ 
        const r = await api(`/logs?tenant_id=1&page=1&pageSize=100`); 
        setRows(r.items||[]);
      } catch(error) {
        // Hata durumunda boş array
      }
    })(); 
  }, []);
  
  const cols = [
    {title:"ID", dataIndex:"id"},
    {title:"Type", dataIndex:"type"},
    {title:"Status", dataIndex:"status"},
    {title:"Message", dataIndex:"message"},
    {title:"Tarih", dataIndex:"created_at"}
  ]; 
  
  return (
    <Card title="İşlem Kayıtları">
      <Table rowKey="id" columns={cols as any} dataSource={rows}/>
    </Card>
  );
}
