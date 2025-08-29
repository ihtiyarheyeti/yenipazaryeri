import { useEffect, useState } from "react";
import { Table } from "antd";
import { api } from "../api";

export default function OrderItems({orderId}:{orderId:number}){
  const [rows,setRows]=useState<any[]>([]);
  
  useEffect(()=>{ 
    if(orderId) {
      api('/dev/sql',{
        method:'POST', 
        body: JSON.stringify({
          sql:`SELECT id,sku,name,qty,price,total FROM order_items WHERE order_id=${orderId} ORDER BY id`
        })
      }).then(r=>setRows(r.items||[])); 
    }
  },[orderId]);
  
  return <Table 
    size="small" 
    rowKey="id" 
    dataSource={rows} 
    pagination={false} 
    columns={[
      {title:'Ürün Kodu',dataIndex:'sku'},
      {title:'Ad',dataIndex:'name'},
      {title:'Adet',dataIndex:'qty'},
      {title:'Birim',dataIndex:'price'},
      {title:'Toplam',dataIndex:'total'}
    ] as any}
  />;
}
