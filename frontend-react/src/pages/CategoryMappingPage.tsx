import { useEffect, useState } from "react";
import { api } from "../api";
import { Card, Table, Pagination, Select, Form, Input, Button, message, Upload } from "antd";

type Row = { id:number; marketplace_id:number; marketplace_name:string; source_path:string; external_category_id:string; note?:string };

export default function CategoryMappingPage(){
  const [rows,setRows]=useState<Row[]>([]);
  const [mps,setMps]=useState<any[]>([]);
  const [page,setPage]=useState(1);
  const [total,setTotal]=useState(0);
  const pageSize=10;
  const [search,setSearch]=useState("");
  const [form]=Form.useForm();

  const load = async ()=>{
    const m = await api(`/marketplaces`);
    setMps(m.items||[]);
    const params = new URLSearchParams({ tenant_id:"1", page:String(page), pageSize:String(pageSize) });
    if(search) params.set("q",search);
    const d = await api(`/category-mappings?${params.toString()}`);
    if(d?.ok){ setRows(d.items||[]); setTotal(d.total||0); }
  };
  useEffect(()=>{ load(); },[page,search]);

  const submit = async (v:any)=>{
    const r = await api(`/category-mappings`, {
      method:"POST",
      body: JSON.stringify({ tenant_id:1, ...v })
    });
    if(r?.ok){ message.success("Eşleştirme kaydedildi"); form.resetFields(); load(); }
    else message.error(r?.error||"Kayıt hatası");
  };

  const columns = [
    { title:"ID", dataIndex:"id", width:70 },
    { title:"Pazar Yeri", dataIndex:"marketplace_name" },
    { title:"Kaynak Yol", dataIndex:"source_path" },
            { title:"Dış Kategori ID", dataIndex:"external_category_id" },
    { title:"Not", dataIndex:"note" }
  ];

  return (
    <div className="grid gap-6">
      <Card title="Yeni Kategori Eşleştirme">
        <Form layout="vertical" form={form} onFinish={submit}>
          <Form.Item name="marketplace_id" label="Pazar Yeri" rules={[{required:true}]}>
            <Select placeholder="Seçin" options={(mps||[]).map((m:any)=>({label:m.name,value:m.id}))}/>
          </Form.Item>
          <Form.Item name="source_path" label="Kaynak Yol" rules={[{required:true,message:"Örn: Kadın>Takı>Bileklik"}]}>
            <Input placeholder="Örn: Kadın>Takı>Bileklik"/>
          </Form.Item>
          <Form.Item name="external_category_id" label="Dış Kategori ID" rules={[{required:true}]}>
            <Input placeholder="Örn: 123456"/>
          </Form.Item>
          <Form.Item name="note" label="Not">
            <Input />
          </Form.Item>
          <Form.Item><Button type="primary" htmlType="submit">Kaydet</Button></Form.Item>
        </Form>
      </Card>

      <Card title="Eşleştirmeler">
        <div className="flex gap-2 mb-3">
          <Button onClick={()=>window.open(`/csv/category-mappings/export?tenant_id=1&marketplace_id=1`)}>CSV Dışa Aktar</Button>
          <Upload name="file" action="/csv/category-mappings/import?tenant_id=1&marketplace_id=1" onChange={(info)=>{ if(info.file.status==="done"){ message.success("İçe aktarma tamamlandı"); load(); } }}>
                          <Button>CSV İçe Aktar</Button>
          </Upload>
        </div>
        <div className="mb-3">
          <Input.Search placeholder="Kaynak yol ara..." allowClear onSearch={(v)=>{setPage(1);setSearch(v)}}/>
        </div>
        <Table rowKey="id" columns={columns as any} dataSource={rows} pagination={false}/>
        <div className="mt-3 flex justify-end">
          <Pagination current={page} pageSize={pageSize} total={total} onChange={setPage}/>
        </div>
      </Card>
    </div>
  );
}
