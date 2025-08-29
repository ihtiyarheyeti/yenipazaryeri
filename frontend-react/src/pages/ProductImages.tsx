import { Card, Button, Table, message } from "antd";
import { useEffect, useState } from "react";
import { useParams } from "react-router-dom";
import { api } from "../api";

export default function ProductImages(){
  const { id } = useParams();
  const [rows,setRows]=useState<any[]>([]);
  const load=async()=>{ const r=await api(`/products/${id}/images`); setRows(r.items||[]); };
  useEffect(()=>{ load(); },[id]);

  return <Card title={`Ürün #${id} Görseller`} extra={
    <>
      <Button onClick={async()=>{ const r=await api(`/products/${id}/media/fetch-woo`,{method:'POST'}); r?.ok? (message.success(`Woo'dan ${r.count} görsel çekildi`), load()) : message.error(r?.error||'Hata'); }}>Woo'dan Çek</Button>
              <Button style={{marginLeft:8}} onClick={async()=>{ const r=await api(`/products/${id}/media/push-ty`,{method:'POST'}); r?.ok? message.success('Trendyol\'a yüklendi') : message.error(r?.error||'Hata'); }}>Trendyol'a Yükle</Button>
    </>
  }>
    <Table rowKey={(r:any)=>r.id||r.url} dataSource={rows} columns={[
      {title:'Sıra',dataIndex:'position',width:80},
      {title:'Önizleme',render:(_:any,r:any)=><img src={r.url} alt="" style={{height:60}}/>},
      {title:'URL',dataIndex:'url'},
      {title:'Durum',dataIndex:'status'},
    ]} pagination={false}/>
  </Card>;
}
