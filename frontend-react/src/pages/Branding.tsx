import { Card, Form, Input, Radio, Button, Upload, message } from "antd";
import { API_BASE, api } from "../api";
import { useEffect, useState } from "react";

export default function Branding(){
  const [form]=Form.useForm(); const [logo,setLogo]=useState<string|undefined>(undefined);

  const load=async()=>{ const r=await api(`/tenant/branding?tenant_id=1`); form.setFieldsValue(r.item||{}); setLogo(r.item?.logo_url); };
  useEffect(()=>{ load(); },[]);

  const save=async()=>{ const v=await form.validateFields(); const r=await api(`/tenant/branding`,{method:"POST",body:JSON.stringify({tenant_id:1,...v, logo_url:logo})}); r?.ok?message.success("Kaydedildi"):message.error("Hata"); };

  return <Card title="Tema / Marka" style={{maxWidth:720}}>
    <Form layout="vertical" form={form}>
      <Form.Item name="name" label="Tenant Adı"><Input/></Form.Item>
      <Form.Item label="Logo">
        <Upload name="file" showUploadList={false} action={`${API_BASE}/upload/tenant-logo?tenant_id=1`} onChange={(i)=>{ if(i.file.status==='done'){ const url=i.file.response.url; setLogo(url); message.success('Logo yüklendi'); } }}>
          <Button>Logo Yükle</Button>
        </Upload>
        {logo && <div style={{marginTop:12}}><img src={`${API_BASE}${logo}`} alt="" style={{height:40}}/></div>}
      </Form.Item>
      <Form.Item name="theme_primary" label="Ana Renk (hex)"><Input placeholder="#1677ff"/></Form.Item>
      <Form.Item name="theme_accent" label="Vurgu Renk (hex)"><Input placeholder="#13c2c2"/></Form.Item>
      <Form.Item name="theme_mode" label="Tema">
        <Radio.Group>
          <Radio.Button value="light">Light</Radio.Button>
          <Radio.Button value="dark">Dark</Radio.Button>
        </Radio.Group>
      </Form.Item>
      <Button type="primary" onClick={save}>Kaydet</Button>
    </Form>
  </Card>;
}
