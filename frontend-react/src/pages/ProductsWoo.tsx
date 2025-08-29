import { Card, Table, Tag, message, Button, Space } from "antd";
import { useEffect, useState } from "react";
import { api } from "../api";
import { ProductRowActions } from "../components/ProductRowActions";
import { ProductFilters } from "../components/ProductFilters";

export default function ProductsWoo(){
  const [rows,setRows]=useState<any[]>([]);
  const [filters,setFilters]=useState<{search:string;onlyUnmapped:boolean}>({search:'',onlyUnmapped:false});
  const [selectedRowKeys,setSelectedRowKeys]=useState<React.Key[]>([]);
  
  const load=async(f?:{search:string;onlyUnmapped:boolean})=>{
    const f2 = f || filters;
    const params = new URLSearchParams({
      source: 'woo',
      search: f2.search,
      only_unmapped: f2.onlyUnmapped ? '1' : '0'
    });
    const r=await api(`/products?${params.toString()}`); 
    setRows(r.items||[]); 
  };
  
  useEffect(()=>{ load(); },[]);
  const cols:any[]=[
    {title:'ID',dataIndex:'id',width:80},
    {title:'Ad',dataIndex:'name'},
    {title:'Marka',dataIndex:'brand',width:140},
    {title:'Kategori',dataIndex:'category_path',width:260, render:(t:string)=> t||'-'},
    {title:'Cat',dataIndex:'category_match',width:80, render:(s:string)=> <Tag color={s==='mapped'?'green':'red'}>{s||'unmapped'}</Tag>},
    {title:'Var',dataIndex:'variant_count',width:80},
                    {title:'SKU',dataIndex:'first_sku',width:160},
                {title:'Medya',dataIndex:'media_status',width:90, render:(s:string)=> <span style={{color:s==='ready'?'green': (s==='partial'?'#faad14':'#999')}}>{s||'none'}</span>},
                {title:'İşlem',width:360, render:(_:any,r:any)=> <ProductRowActions row={r}/>}
  ];
  return <Card title="Woo Ürünleri" extra={
    <Space direction="vertical" size="small" style={{width: '100%'}}>
      <Space>
        <Button onClick={async()=>{ const r=await api('/import/woo',{method:'POST'}); r?.ok? (message.success(`Woo sayfa çekildi: ${r.imported}`), load()) : message.error(r?.error||'Hata'); }}>Woo'dan Çek (Sayfa)</Button>
        <Button onClick={async()=>{ const r=await api('/import/woo/enqueue',{method:'POST'}); r?.ok? message.success('Woo tam çekim kuyruğa alındı') : message.error('Hata'); }}>Woo Tam Çekim</Button>
        <Button onClick={async()=>{ const r=await api('/mapping/woo/resolve',{method:'POST', body: JSON.stringify({limit:300})}); r?.ok? message.success(`Woo kimlik eşlemesi: ${r.mapped} eşleşti, ${r.unresolved} kaldı`) : message.error(r?.error||'Hata'); }}>Kimlikleri Eşle (Woo)</Button>
      </Space>
      <ProductFilters onChange={(f)=>{ setFilters(f); load(f); }} />
    </Space>
  }>
    <Table 
      rowKey="id" 
      columns={cols} 
      dataSource={rows} 
      pagination={{pageSize:20}}
      rowSelection={{
        selectedRowKeys,
        onChange: setSelectedRowKeys
      }}
    />
    {selectedRowKeys.length > 0 && (
      <div style={{marginTop: 16, textAlign: 'right'}}>
        <Button 
          type="primary" 
          disabled={!selectedRowKeys.length} 
          onClick={async()=>{
            for(const id of selectedRowKeys){ 
              await api(`/products/${id}/push/trendyol`,{method:'POST'}); 
            }
            message.success('Seçilen ürünler Trendyol\'a gönderildi'); 
            load(filters);
            setSelectedRowKeys([]);
          }}
        >
          Toplu Gönder ({selectedRowKeys.length} ürün)
        </Button>
      </div>
    )}
  </Card>;
}
