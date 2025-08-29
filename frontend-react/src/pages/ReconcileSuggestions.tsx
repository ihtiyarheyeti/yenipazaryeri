import { useEffect, useState } from "react";
import { Card, Table, Button, message } from "antd";
import { api } from "../api";

export default function ReconcileSuggestions(){
  const [rows,setRows]=useState<any[]>([]);
  const load=async()=>{ const r=await api('/dev/sql',{method:'POST', body: JSON.stringify({sql:"SELECT * FROM reconcile_suggestions WHERE resolved_at IS NULL ORDER BY id DESC LIMIT 500"})}); setRows(r.items||[]); };
  useEffect(()=>{ load(); },[]);
  return <Card title="Uyum Önerileri" extra={<Button onClick={async()=>{ const r=await api('/reconcile/suggest',{method:'POST'}); r?.ok? (message.success(`Öneri üretildi: ${r.inserted}`), load()) : message.error('Hata'); }}>Öneri Üret</Button>}>
    <Table rowKey="id" dataSource={rows} pagination={{pageSize:20}}       columns={[
        {title:'Ürün',dataIndex:'product_id'},{title:'Varyant',dataIndex:'variant_id'},
        {title:'Konu',dataIndex:'issue'},{title:'Kaynak',dataIndex:'source'},
        {title:'Yerel',dataIndex:'local_value'},{title:'Dış',dataIndex:'remote_value'},
        {title:'Öneri',dataIndex:'suggestion'},
      {title:'',render:(_:any,r:any)=> <Button onClick={async()=>{ const x=await api(`/reconcile/suggestions/${r.id}/resolve`,{method:'POST', body: JSON.stringify({note:'Uygulandı'})}); x?.ok? (message.success('Çözüldü'), load()) : message.error('Hata'); }}>Çözüldü</Button>}
    ] as any}/>
  </Card>;
}
