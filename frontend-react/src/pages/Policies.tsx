import { useEffect, useState } from "react";
import { Card, Table, Button, Modal, Form, Input, Select, message } from "antd";
import { api } from "../api";

export default function Policies(){
  const [rows,setRows]=useState<any[]>([]);
  const [open,setOpen]=useState(false);
  const [form]=Form.useForm();

  const load=async()=>{ const r=await api('/policies'); setRows(r.items||[]); };
  useEffect(()=>{ load(); },[]);

  const columns=[
    {title:'Ayar Adı',dataIndex:'name'},
    {title:'Ayar Değeri',render:(_:any,r:any)=> JSON.stringify(r.rules)}
  ];

  const presets=[
    {label:'Stok Ana Kaynak', value:'stock_master', schema:{master:'local'}},
    {label:'Fiyat Ana Kaynak', value:'price_master', schema:{master:'local'}},
    {label:'Otomatik Düzeltme Eşiği', value:'auto_fix_threshold', schema:{price:0.02, stock:0}},
    {label:'Ödeme Yöntemi Eşleme', value:'payment_map', schema:{ woo_method:'cod', woo_title:'Kapıda Ödeme', ty_method:'1' }},
    {label:'Kargo Yöntemi Eşleme', value:'shipping_map', schema:{ woo_method_id:1, woo_method_title:'Flat Rate', ty_carrier:'Yurtici', ty_service_code:'STANDARD' }},
    {label:'Vergi Politikası', value:'tax_policy', schema:{ default_rate:20 }}
  ];

  const onOk=async()=>{
    const v=await form.validateFields();
    let json:any; try{ json=JSON.parse(v.value_json); }catch(e){ return message.error('Geçersiz JSON'); }
    const r=await api('/policies',{method:'POST', body: JSON.stringify({name:v.key_name, rules:json})});
    r?.ok? (message.success('Kaydedildi'), setOpen(false), load()) : message.error(r?.error||'Hata');
  };

  return (
    <Card title="Sistem Ayarları" extra={
      <Button onClick={()=>{ 
        form.setFieldsValue({key_name:'stock_master', value_json: JSON.stringify(presets[0].schema)}); 
        setOpen(true); 
      }}>Yeni/Değiştir</Button>
    }>
      <Table rowKey="id" dataSource={rows} columns={columns as any} pagination={false}/>
      <Modal title="Sistem Ayarı" open={open} onOk={onOk} onCancel={()=>setOpen(false)}>
        <Form layout="vertical" form={form}>
          <Form.Item name="key_name" label="Ayar Adı" rules={[{required:true}]}>
            <Select options={presets}/>
          </Form.Item>
          <Form.Item name="value_json" label="Ayar Değeri (JSON)" rules={[{required:true}]}>
            <Input.TextArea rows={6}/>
          </Form.Item>
        </Form>
      </Modal>
    </Card>
  );
}
