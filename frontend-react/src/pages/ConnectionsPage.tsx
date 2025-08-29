import { useEffect, useState } from "react";
import { api } from "../api";
import { Card, Table, Pagination, Select, Form, Input, InputNumber, Button, message } from "antd";

type Connection = { id:number; marketplace_id:number; marketplace_name:string; api_key?:string; api_secret?:string; supplier_id?:number; base_url?:string };

export default function ConnectionsPage(){
  const [rows,setRows]=useState<Connection[]>([]);
  const [mps,setMps]=useState<any[]>([]);
  const [page,setPage]=useState(1);
  const [total,setTotal]=useState(0);
  const pageSize=10;
  const [form] = Form.useForm();

  const load = async () => {
    const d1 = await api(`/marketplaces`);
    setMps(d1.items||[]);
    const d2 = await api(`/connections?tenant_id=1&page=${page}&pageSize=${pageSize}`);
    if(d2?.ok){ setRows(d2.items||[]); setTotal(d2.total||0); }
  };
  useEffect(()=>{ load(); },[page]);

  const submit = async (v:any)=>{
    const r = await api(`/connections`, { method:"POST", body: JSON.stringify({ tenant_id:1, ...v }) });
    if(r?.ok){ message.success("Bağlantı kaydedildi"); form.resetFields(); load(); }
    else message.error(r?.error||"Kayıt hatası");
  };

  const columns = [
    { title:"ID", dataIndex:"id", width:70 },
    { title:"Marketplace", dataIndex:"marketplace_name" },
            { title:"API Anahtarı", dataIndex:"api_key" },
            { title:"Tedarikçi ID", dataIndex:"supplier_id" },
    { title:"Test", render:(_:any,r:any)=><Button onClick={async()=>{
        const res=await api(`/connections/ping/${r.id}`);
        if(res?.ok) message.success(`${r.marketplace_name} bağlantı OK (${res.status})`);
        else message.error(res?.error||"Ping hata");
    }}>Ping</Button>}
  ];

  return (
    <div className="grid gap-6">
      <Card title="Yeni Bağlantı">
        <Form layout="vertical" form={form} onFinish={submit}>
          <Form.Item name="marketplace_id" label="Marketplace" rules={[{required:true}]}>
            <Select placeholder="Seçin" options={(mps||[]).map((m:any)=>({label:m.name,value:m.id}))}/>
          </Form.Item>
          <Form.Item name="api_key" label="API Anahtarı" rules={[{required:true}]}>
            <Input />
          </Form.Item>
          <Form.Item name="api_secret" label="API Secret" rules={[{required:true}]}>
            <Input.Password />
          </Form.Item>
          <Form.Item name="supplier_id" label="Tedarikçi ID (Trendyol ise)">
            <InputNumber min={1} style={{width:"100%"}}/>
          </Form.Item>
          <Form.Item>
            <Button type="primary" htmlType="submit">Kaydet</Button>
          </Form.Item>
        </Form>
      </Card>

      <Card title="Bağlantılar">
        <Table rowKey="id" columns={columns as any} dataSource={rows} pagination={false}/>
        <div className="mt-3 flex justify-end">
          <Pagination current={page} pageSize={pageSize} total={total} onChange={setPage}/>
        </div>
      </Card>
    </div>
  );
}
