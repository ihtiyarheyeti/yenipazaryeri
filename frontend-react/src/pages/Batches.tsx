import { useEffect, useState } from "react";
import { Card, Table } from "antd";
import { api } from "../api";

export default function Batches(){
  const [rows,setRows]=useState<any[]>([]); 
  const [detail,setDetail]=useState<any[]|null>(null); 
  const [sel,setSel]=useState<string|null>(null);
  
  useEffect(()=>{ 
    api('/batches').then(r=>setRows(r.items||[])); 
  },[]);
  
  return (
    <Card title="Batch Jobs">
      <Table 
        rowKey="batch_id" 
        dataSource={rows} 
        columns={[
          {title:"Batch ID",dataIndex:"batch_id"},
          {title:"Jobs",dataIndex:"jobs"},
          {title:"Done",dataIndex:"done"},
          {title:"Errors",dataIndex:"errors"},
          {title:"Started",dataIndex:"started"},
          {title:"Last Update",dataIndex:"last_update"},
          {title:"",render:(_:any,r:any)=>
            <a onClick={async()=>{ 
              setSel(r.batch_id); 
              const d=await api(`/batches/${r.batch_id}`); 
              setDetail(d.items); 
            }}>Detay</a>
          }
        ]}
      />
      
      {detail && (
        <div style={{marginTop:16}}>
          <h3>Batch Detayı: {sel}</h3>
          <Table 
            rowKey="id" 
            dataSource={detail} 
            columns={[
              {title:"ID",dataIndex:"id"},
              {title:"Type",dataIndex:"type"},
              {title:"Status",dataIndex:"status"},
              {title:"Attempts",dataIndex:"attempts"},
              {title:"Last Error",dataIndex:"last_error"}
            ]} 
            pagination={false}
            size="small"
          />
        </div>
      )}
    </Card>
  );
}
