import { useEffect, useState } from "react";
import { Card, Table, Button, Input, Select, message } from "antd";
import { api } from "../api";

export default function CatalogMap(){
  const [catMaps,setCatMaps]=useState<any[]>([]);
  const [attrMaps,setAttrMaps]=useState<any[]>([]);
  const [mp,setMp]=useState(1); // 1 TY, 2 WOO
  const [local,setLocal]=useState(""); 
  const [ext,setExt]=useState("");
  const [lk,setLk]=useState(""); 
  const [ek,setEk]=useState(""); 
  const [vm,setVm]=useState("");

  const load=async()=>{ 
    const c=await api(`/catalog/category-map?marketplace_id=${mp}`); 
    setCatMaps(c.items||[]);
    const a=await api(`/catalog/attr-map?marketplace_id=${mp}`); 
    setAttrMaps(a.items||[]);
  };
  
  useEffect(()=>{ load(); },[mp]);

  return (
    <Card title="Kategori & Attribute Eşleme" extra={
      <div style={{display:'flex',gap:8}}>
        <Select value={mp} onChange={setMp} options={[
          {label:'Trendyol',value:1},
          {label:'Woo',value:2}
        ]}/>
        <Button onClick={async()=>{ 
          const r=await api(`/catalog/pull?marketplace_id=${mp}`,{method:'POST'}); 
          r?.ok? (message.success('Kategoriler çekildi'), load()):message.error('Hata'); 
        }}>Kategorileri Çek</Button>
      </div>
    }>

             <h3>Kategori Eşleme</h3>
      <div style={{display:'flex',gap:8,marginBottom:8}}>
        <Input placeholder="Yerel yol (Kadın>Takı>Bileklik)" value={local} onChange={e=>setLocal(e.target.value)}/>
        <Input placeholder="Dış ID" value={ext} onChange={e=>setExt(e.target.value)}/>
        <Button type="primary" onClick={async()=>{ 
          const r=await api('/catalog/category-map',{
            method:'POST', 
            body: JSON.stringify({
              marketplace_id:mp, 
              local_path:local, 
              external_id:ext
            })
          }); 
          r?.ok? (message.success('Kaydedildi'), setLocal(''), setExt(''), load()) : message.error('Hata'); 
        }}>Eşle</Button>
      </div>
      <Table 
        rowKey="id" 
        dataSource={catMaps} 
        columns={[
          {title:'Yerel',dataIndex:'local_path'},
          {title:'Pazar',dataIndex:'marketplace_id'},
          {title:'Dış',dataIndex:'external_id'}
        ]} 
        pagination={false}
      />

             <h3 style={{marginTop:24}}>Özellik Eşleme</h3>
      <div style={{display:'flex',gap:8,marginBottom:8}}>
        <Input placeholder="Yerel anahtar (color)" value={lk} onChange={e=>setLk(e.target.value)}/>
        <Input placeholder="Dış anahtar (Renk / attribute_pa_color)" value={ek} onChange={e=>setEk(e.target.value)}/>
        <Input placeholder='Value map JSON {"Kırmızı":"Red"} (opsiyonel)' value={vm} onChange={e=>setVm(e.target.value)}/>
        <Button type="primary" onClick={async()=>{ 
          let vmap=null; 
          try{ 
            vmap=vm?JSON.parse(vm):null; 
          }catch(e){ 
            return message.error('Geçersiz JSON'); 
          }
          const r=await api('/catalog/attr-map',{
            method:'POST', 
            body: JSON.stringify({
              marketplace_id:mp, 
              local_key:lk, 
              external_key:ek, 
              value_map:vmap
            })
          });
          r?.ok? (message.success('Kaydedildi'), setLk(''), setEk(''), setVm(''), load()) : message.error('Hata');
        }}>Eşle</Button>
      </div>
      <Table 
        rowKey="id" 
        dataSource={attrMaps} 
        columns={[
          {title:'Yerel',dataIndex:'local_key'},
          {title:'Dış',dataIndex:'external_key'}
        ]} 
        pagination={false}
      />
    </Card>
  );
}
