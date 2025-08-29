import { useEffect, useMemo, useState } from "react";
import { api } from "../api";
import { Table, Input, Select, Pagination, Card, Tag } from "antd";

type Row = {
  id:number;
  product_id:number;
  product_name?:string;
  marketplace_id:number;
  marketplace_name:string;
  external_product_id:string;
  last_sync:string;
};

type Marketplace = { id:number; name:string };

export default function MappingList({ productId }: { productId?: number }) {
  const [rows, setRows] = useState<Row[]>([]);
  const [mps, setMps] = useState<Marketplace[]>([]);
  const [search, setSearch] = useState("");
  const [mpFilter, setMpFilter] = useState<number | undefined>(undefined);
  const [page, setPage] = useState(1);
  const pageSize = 10;

  useEffect(() => {
    (async () => {
      const mp = await api("/marketplaces");
      setMps(mp.items || []);
    })();
  }, []);

  useEffect(() => {
    (async () => {
      // İsteğe göre productId veya marketplace filtreli çekim
      const q = productId ? `?product_id=${productId}` : "";
      const d = await api(`/product-mappings${q}`);
      setRows(d.items || []);
      setPage(1);
    })();
  }, [productId]);

  const filtered = useMemo(() => {
    let data = [...rows];
    if (mpFilter) data = data.filter(r => r.marketplace_id === mpFilter);
    if (search) {
      const s = search.toLowerCase();
      data = data.filter(r =>
        (r.external_product_id || "").toLowerCase().includes(s) ||
        (r.product_name || String(r.product_id)).toLowerCase().includes(s)
      );
    }
    return data;
  }, [rows, mpFilter, search]);

  const pageData = useMemo(() => {
    const start = (page-1)*pageSize;
    return filtered.slice(start, start+pageSize);
  }, [filtered, page]);

  const columns = [
    { title:"ID", dataIndex:"id", width:80 },
    { title:"Ürün", render: (_:any, r:Row) => <span>#{r.product_id} — {r.product_name ?? "-"}</span> },
    { title:"Pazar Yeri", dataIndex:"marketplace_name", render:(v:string)=> <Tag>{v}</Tag> },
            { title:"Dış Ürün ID", dataIndex:"external_product_id" },
    { title:"Son Senkronizasyon", dataIndex:"last_sync" },
  ];

  return (
    <Card title="Pazaryeri Eşleştirmeleri" bordered className="shadow">
      <div className="grid grid-cols-1 md:grid-cols-3 gap-3 mb-3">
        <Input.Search allowClear placeholder="Ürün adı/ID veya Dış Ürün ID ara..."
          onSearch={(v)=>{ setSearch(v); setPage(1); }} />
        <Select
          allowClear
          placeholder="Pazar yeri filtrele"
          onChange={(v)=>{ setMpFilter(v as number | undefined); setPage(1); }}
          options={mps.map(m => ({label:m.name, value:m.id}))}
        />
        <div />
      </div>

      <Table
        rowKey="id"
        columns={columns as any}
        dataSource={pageData}
        pagination={false}
      />
      <div className="mt-3 flex justify-end">
        <Pagination current={page} pageSize={pageSize} total={filtered.length} onChange={setPage}/>
      </div>
    </Card>
  );
}
