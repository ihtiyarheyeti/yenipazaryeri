import { Card, Button, Table, message, Drawer, Descriptions } from "antd";
import { api } from "../api";
import { useEffect, useState } from "react";
import OrderItems from "./OrderItems";

export default function Orders(){
  const [rows,setRows]=useState<any[]>([]);
  const [open,setOpen]=useState(false);
  const [cur,setCur]=useState<any|null>(null);

  const load=async()=>{ const r=await api('/orders'); setRows(r.items||[]); };
  useEffect(()=>{ load(); },[]);

  const cols=[ 
    {title:'ID',dataIndex:'id',width:80},
    {title:'Pazar',dataIndex:'mp',width:90},
    {title:'Sipariş No',dataIndex:'external_id'},
    {title:'Durum',dataIndex:'status'},
    {title:'Toplam',dataIndex:'total'},
    {title:'Tarih',dataIndex:'created_at_mp'},
    {title:'',render:(_:any,r:any)=><Button onClick={()=>{setCur(r); setOpen(true);}}>Detay</Button>}
  ];

  return <Card title="Siparişler" extra={
    <Button onClick={async()=>{ 
      const r=await api('/orders/pull/trendyol',{method:'POST'});
      r?.ok? (message.success(`Çekildi: ${r.imported||0}`), load()) : message.error(r?.error||'Hata');
    }}>Trendyol'dan Çek</Button>
  }>
    <Table rowKey="id" dataSource={rows} columns={cols as any} pagination={{pageSize:20}}/>

    <Drawer title={`Sipariş #${cur?.id||''}`} open={open} onClose={()=>setOpen(false)} width={720} extra={
      cur?.mp==='trendyol' && <Button type="primary" onClick={async()=>{
        const r=await api(`/orders/push/woo/${cur.id}`,{method:'POST'});
        r?.ok? message.success('Woo\'da sipariş oluşturuldu'):message.error(r?.error||('HTTP '+r?.code||'Hata'));
      }}>Woo'da Oluştur</Button>
    }>
      {cur && <Descriptions size="small" column={1} bordered>
        <Descriptions.Item label="Pazar">{cur.mp}</Descriptions.Item>
        <Descriptions.Item label="Sipariş No">{cur.external_id}</Descriptions.Item>
        <Descriptions.Item label="Durum">{cur.status}</Descriptions.Item>
        <Descriptions.Item label="Toplam">{cur.total}</Descriptions.Item>
        <Descriptions.Item label="Müşteri">{cur.customer_name} ({cur.customer_email||'-'})</Descriptions.Item>
        <Descriptions.Item label="Telefon">{cur.phone||'-'}</Descriptions.Item>
      </Descriptions>}
      <div style={{marginTop:12}}>
        {/* basit item listesi */}
        <OrderItems orderId={cur?.id}/>
      </div>
    </Drawer>
  </Card>;
}
