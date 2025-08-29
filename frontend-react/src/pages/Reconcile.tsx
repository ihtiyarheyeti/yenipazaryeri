import { Card, Table, Select, Button } from "antd";
import { api } from "../api";
import { useEffect, useState } from "react";

export default function Reconcile(){
  const [rows,setRows]=useState<any[]>([]); 
  const [src,setSrc]=useState<'woo'|'trendyol'|'both'>('both');
  
  const load=async()=>{ 
    // basit: son 200 snapshot'ı getir
    const r=await api('/dev/sql',{
      method:'POST', 
      body: JSON.stringify({
        sql:"SELECT r.id,p.name as product, r.variant_id, r.source, r.price, r.stock, r.taken_at FROM reconcile_snapshots r LEFT JOIN products p ON p.id=r.product_id ORDER BY r.id DESC LIMIT 200"
      })
    });
    setRows(r.items||[]);
  };
  
  useEffect(()=>{ load(); },[]);
  
  const filtered = rows.filter((x:any)=> src==='both' || x.source===src);
  
  return (
    <Card title="Reconcile (Stok/Fiyat Karşılaştırma)" extra={
      <Select value={src} onChange={setSrc} options={[
        {label:'Hepsi',value:'both'},
        {label:'Woo',value:'woo'},
        {label:'Trendyol',value:'trendyol'}
      ]}/>
    }>
      <Table 
        rowKey="id" 
        dataSource={filtered} 
        pagination={false} 
        columns={[
          {title:'Ürün',dataIndex:'product'},
          {title:'Varyant',dataIndex:'variant_id'},
          {title:'Kaynak',dataIndex:'source'},
          {title:'Fiyat',dataIndex:'price'},
          {title:'Stok',dataIndex:'stock'},
          {title:'Zaman',dataIndex:'taken_at'},
        ] as any}
      />
    </Card>
  );
}
