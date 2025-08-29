import { useEffect, useState } from "react";
import { api } from "../api";
import { Table, Input, Pagination, Card, Button, message, Space, Tag } from "antd";
import ProductEditModal from "./ProductEditModal";

export default function ProductList() {
  const [items, setItems] = useState<any[]>([]);
  const [total, setTotal] = useState(0);
  const [page, setPage] = useState(1);
  const [search, setSearch] = useState("");
  const [selectedRowKeys, setSelectedRowKeys] = useState<React.Key[]>([]);
  const [editing, setEditing] = useState<any|null>(null);
  const [open, setOpen] = useState(false);
  const pageSize = 10;

  const load = async () => {
    const q = `?tenant_id=1&page=${page}&pageSize=${pageSize}&q=${encodeURIComponent(search)}`;
    const d = await api(`/products${q}`);
    if (d?.ok) {
      setItems(d.items || []);
      setTotal(d.total || 0);
    }
  };

  useEffect(() => { load(); }, [page, search]);

  // Tekil gönderimler (credentials otomatik DB'den)
  const sendToTrendyolSingle = async (id:number) => {
    const res = await api(`/integrations/trendyol/send-product/${id}`, { method:"POST", body: JSON.stringify({}) });
    if(res?.ok) { message.success(`Ürün #${id} Trendyol'a gönderildi`); load(); }
    else message.error(res?.error || "Gönderim hatası");
  };
  const sendToWooSingle = async (id:number) => {
    const res = await api(`/integrations/woo/send-product/${id}`, { method:"POST", body: JSON.stringify({}) });
    if(res?.ok) { message.success(`Ürün #${id} Woo'ya gönderildi`); load(); }
    else message.error(res?.error || "Gönderim hatası");
  };

  // TOPLU (enqueue)
  const enqueueTrendyol = async () => {
    if (selectedRowKeys.length===0) return message.info("Önce ürün seçin");
    const res = await api(`/integrations/trendyol/enqueue-products`, {
      method:"POST",
      body: JSON.stringify({ product_ids: selectedRowKeys })
    });
    if(res?.ok){ message.success(`Trendyol kuyruğa alındı (${res.enqueued})`); setSelectedRowKeys([]); }
    else message.error(res?.error || "Kuyruğa alma hatası");
  };
  const enqueueWoo = async () => {
    if (selectedRowKeys.length===0) return message.info("Önce ürün seçin");
    const res = await api(`/integrations/woo/enqueue-products`, {
      method:"POST",
      body: JSON.stringify({ product_ids: selectedRowKeys })
    });
    if(res?.ok){ message.success(`Woo kuyruğa alındı (${res.enqueued})`); setSelectedRowKeys([]); }
    else message.error(res?.error || "Kuyruğa alma hatası");
  };

  const columns = [
    { title:"ID", dataIndex:"id", width:80 },
    { title:"Ad", dataIndex:"name" },
    { title:"Marka", dataIndex:"brand" },
    { title:"Varyant", dataIndex:"variant_count" },
    { title:"Trendyol ID", dataIndex:"trendyol_external_id", render:(v:any)=> v ? <Tag color="blue">{v}</Tag> : <span style={{opacity:.6}}>—</span> },
    { title:"Woo ID", dataIndex:"woo_external_id", render:(v:any)=> v ? <Tag>{v}</Tag> : <span style={{opacity:.6}}>—</span> },
         { title:"İşlem", render:(_:any,r:any)=>(
       <Space>
         <Button onClick={()=>{ setEditing(r); setOpen(true); }}>Düzenle</Button>
         <Button type="primary" onClick={()=>sendToTrendyolSingle(r.id)}>Trendyol</Button>
         <Button onClick={()=>sendToWooSingle(r.id)}>Woo</Button>
       </Space>
     )}
  ];

  const rowSelection = {
    selectedRowKeys,
    onChange: (keys: React.Key[]) => setSelectedRowKeys(keys),
  };

  return (
    <Card title="Ürünler" className="shadow">
              <div className="flex flex-wrap gap-2 mb-3">
          <Input.Search placeholder="Ara..." allowClear onSearch={(v)=>{setPage(1);setSearch(v)}} style={{maxWidth:300}}/>
          <Button onClick={enqueueWoo} disabled={selectedRowKeys.length===0}>Seçili → Woo (kuyruk)</Button>
          <Button type="primary" onClick={enqueueTrendyol} disabled={selectedRowKeys.length===0}>Seçili → Trendyol (kuyruk)</Button>
          <Button onClick={()=>window.open(`/csv/products/export?tenant_id=1`,"_blank")}>CSV Export (Ürün+Varyant)</Button>
        </div>
      <Table
        rowKey="id"
        columns={columns as any}
        dataSource={items}
        pagination={false}
        rowSelection={rowSelection}
      />
             <div className="mt-3 flex justify-end">
         <Pagination current={page} pageSize={pageSize} total={total} onChange={setPage}/>
       </div>
       <ProductEditModal 
         open={open} 
         product={editing} 
         onClose={()=>{ setOpen(false); setEditing(null); load(); }} 
       />
     </Card>
   );
 }
