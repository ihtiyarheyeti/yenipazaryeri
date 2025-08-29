import { Card, Table, Button, Tag, message } from "antd";
import { useEffect, useState } from "react";
import { api } from "../api";
import { useNavigate } from "react-router-dom";

export default function OrdersTrendyol(){
  const [rows,setRows]=useState<any[]>([]);
  const nav=useNavigate();
  const load=async()=>{ const r=await api('/orders?source=trendyol'); setRows(r.items||[]); };
  useEffect(()=>{ load(); },[]);
  const cols:any[]=[
    {title:'ID',dataIndex:'id',width:80},
    {title:'Order#',dataIndex:'origin_external_id',width:140},
    {title:'Müşteri',dataIndex:'customer_name'},
    {title:'Tutar',dataIndex:'total_amount',width:120},
    {title:'Durum',dataIndex:'status',render:(s:string)=><Tag color={s==='completed'?'green':s==='cancelled'?'red':'blue'}>{s}</Tag>,width:120},
    {title:'',width:260,render:(_:any,r:any)=>
      <>
        <Button onClick={()=>nav(`/orders/${r.id}`)}>Detay</Button>
        <Button style={{marginLeft:8}} type="primary"
          onClick={async()=>{ const x=await api(`/orders/${r.id}/push/woo`,{method:'POST'}); x?.ok?message.success('Woo\'ya aktarıldı'):message.error(x?.error||'Hata'); }}>
          Eşle & Woo'ya Aktar
        </Button>
      </>
    }
  ];
  return <Card title="Trendyol Siparişleri" extra={<>
    <Button onClick={async()=>{ const r=await api('/orders/import/trendyol',{method:'POST'}); r?.ok? (message.success(`Çekildi: ${r.imported}`), load()) : message.error(r?.error||'Hata'); }}>Trendyol'dan Çek (Sayfa)</Button>
  </>}>
    <Table rowKey="id" columns={cols} dataSource={rows} pagination={{pageSize:20}}/>
  </Card>;
}
