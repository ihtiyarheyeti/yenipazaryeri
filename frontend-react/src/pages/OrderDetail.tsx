import { Card, Table, Descriptions, Button, message } from "antd";
import { useEffect, useState } from "react";
import { useParams } from "react-router-dom";
import { api } from "../api";

export default function OrderDetail(){
  const { id } = useParams();
  const [data,setData]=useState<any>(null);
  const load=async()=>{ const r=await api(`/orders/${id}`); setData(r); };
  useEffect(()=>{ load(); },[id]);
  if(!data) return null;
  return <Card title={`Sipariş #${id}`} extra={
    <>
      <Button onClick={async()=>{ const x=await api(`/orders/${id}/push/woo`,{method:'POST'}); x?.ok?message.success('Woo\'ya aktarıldı'):message.error(x?.error||'Hata'); }}>Woo'ya Aktar</Button>
      <Button style={{marginLeft:8}} onClick={async()=>{ const x=await api(`/orders/${id}/push/trendyol`,{method:'POST'}); x?.ok?message.success('Trendyol\'a aktarıldı'):message.error(x?.error||'Hata'); }}>Trendyol'a Aktar</Button>
      <Button style={{marginLeft:8}} onClick={async()=>{ const x=await api(`/orders/${id}/status/woo`,{method:'POST', body: JSON.stringify({status:'completed'})}); x?.ok?message.success('Woo durumu güncellendi'):message.error(x?.error||'Hata'); }}>Woo: Tamamlandı</Button>
    </>
  }>
    <Descriptions bordered size="small" column={2}>
      <Descriptions.Item label="Kaynak">{data.order.origin_mp}</Descriptions.Item>
      <Descriptions.Item label="Kaynak ID">{data.order.origin_external_id}</Descriptions.Item>
      <Descriptions.Item label="Müşteri" span={2}>{data.order.customer_name}</Descriptions.Item>
      <Descriptions.Item label="Durum">{data.order.status}</Descriptions.Item>
      <Descriptions.Item label="Tutar">{data.order.total_amount} {data.order.currency}</Descriptions.Item>
    </Descriptions>
    <Table style={{marginTop:16}} rowKey="id" dataSource={data.items||[]} columns={[
      {title:'SKU',dataIndex:'sku'},
      {title:'Ad',dataIndex:'name'},
      {title:'Adet',dataIndex:'quantity'},
      {title:'Birim Fiyat',dataIndex:'price'}
    ]} pagination={false}/>
  </Card>
}
