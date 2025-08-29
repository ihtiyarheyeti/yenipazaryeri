import { useEffect, useState } from "react";
import { api } from "../api";
import { Table, Input, Pagination, Card, Tag, Button, Space } from "antd";
import VariantEditModal from "./VariantEditModal";

type Variant = {
  id: number;
  product_id: number;
  sku: string;
  price: number;
  stock: number;
  attrs?: any;
};

export default function VariantList({ productId }: { productId: number }) {
  const [rows, setRows] = useState<Variant[]>([]);
  const [total, setTotal] = useState(0);
  const [page, setPage] = useState(1);
  const [search, setSearch] = useState("");
  const [editing, setEditing] = useState<any|null>(null);
  const [open, setOpen] = useState(false);
  const pageSize = 10;

  const load = async () => {
    const q = `?product_id=${productId}&page=${page}&pageSize=${pageSize}&q=${encodeURIComponent(search)}`;
    const d = await api(`/variants${q}`);
    if (d?.ok) {
      const items = (d.items || []).map((v: Variant) => ({
        ...v,
        attrs: typeof v.attrs === "string" ? safeParse(v.attrs) : v.attrs,
      }));
      setRows(items);
      setTotal(d.total || 0);
    }
  };

  useEffect(() => { load(); }, [productId, page, search]);

  const columns = [
    { title: "ID", dataIndex: "id", width: 80 },
    { title: "SKU", dataIndex: "sku" },
    { title: "Fiyat", dataIndex: "price", render: (v:number) => `${v} ₺` },
    { title: "Stok", dataIndex: "stock" },
          {
        title: "Özellikler",
        render: (_:any, r:Variant) => {
          const a = r.attrs || {};
          const entries = Object.entries(a);
          if (!entries.length) return <span style={{opacity:.6}}>—</span>;
          return (
            <div style={{ display:"flex", flexWrap:"wrap", gap:6 }}>
              {entries.map(([k,v])=> <Tag key={k}>{formatKey(k)}: {String(v)}</Tag>)}
            </div>
          );
        }
      },
      {
        title: "İşlem",
        render: (_:any, r:Variant) => (
          <Space>
            <Button onClick={()=>{ setEditing(r); setOpen(true); }}>Düzenle</Button>
          </Space>
        )
      }
  ];

  return (
    <Card title={`Varyantlar (Ürün #${productId})`} className="shadow">
      <div className="grid grid-cols-1 md:grid-cols-3 gap-3 mb-3">
        <Input.Search allowClear placeholder="SKU ara..."
          onSearch={(v)=>{setPage(1);setSearch(v)}} />
      </div>
      <Table rowKey="id" columns={columns as any} dataSource={rows} pagination={false}/>
      <div className="mt-3 flex justify-end">
        <Pagination current={page} pageSize={pageSize} total={total} onChange={setPage}/>
      </div>
      <VariantEditModal
        open={open}
        variant={editing}
        onClose={() => { setOpen(false); setEditing(null); load(); }}
      />
    </Card>
  );
}

// yardımcı fonksiyonlar
function safeParse(s:string){ try{return JSON.parse(s);}catch{return {};} }
function formatKey(k:string){ return /^\d+$/.test(k)?`Attr ${k}`: (k.charAt(0).toUpperCase()+k.slice(1)); }
