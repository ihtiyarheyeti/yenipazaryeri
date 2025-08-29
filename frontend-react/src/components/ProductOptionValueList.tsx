import { useEffect, useState } from "react";
import { api } from "../api";
import { Table, Card, Button, Popconfirm, message } from "antd";

type Row = { product_id:number; option_value_id:number; value:string; option_name:string };

export default function ProductOptionValueList({ productId }:{productId:number}) {
  const [rows,setRows] = useState<Row[]>([]);

  const load = async () => {
    const d = await api(`/product-option-values?product_id=${productId}`);
    setRows(d.items||[]);
  };

  useEffect(()=>{ load(); },[productId]);

  const detach = async (ovId:number) => {
    const r = await api("/product-option-values", {
      method:"DELETE",
      body: JSON.stringify({ product_id:productId, option_value_id:ovId })
    });
    if(r?.ok) {
      message.success("Bağlantı kaldırıldı");
      load();
    } else {
      message.error(r?.error || "İşlem başarısız");
    }
  };

  const columns = [
    {title:"Option", dataIndex:"option_name"},
    {title:"Değer", dataIndex:"value"},
    {title:"İşlem", render:(_:any,r:Row)=>
      <Popconfirm title="Kaldırılsın mı?" onConfirm={()=>detach(r.option_value_id)}>
        <Button danger>Kaldır</Button>
      </Popconfirm>
    }
  ];

  return (
    <Card title={`Ürün #${productId} Özellikleri`} className="shadow">
      <Table rowKey={r=>`${r.product_id}-${r.option_value_id}`} dataSource={rows} columns={columns as any} pagination={false}/>
    </Card>
  );
}
