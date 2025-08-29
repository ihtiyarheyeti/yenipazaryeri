import { useEffect, useState } from "react";
import { Card, Table, Button, Modal, Form, Input, message, Tabs } from "antd";
import { api } from "../api";

export default function ShipInvoice(){
  const [ship,setShip]=useState<any[]>([]);
  const [inv,setInv]=useState<any[]>([]);
  const [openShip,setOpenShip]=useState(false);
  const [openInv,setOpenInv]=useState(false);
  const [fs]=Form.useForm(); const [fi]=Form.useForm();

  const load=async()=>{
    const s=await api('/dev/sql',{method:'POST', body: JSON.stringify({sql:"SELECT * FROM shipments ORDER BY id DESC LIMIT 200"})}); setShip(s.items||[]);
    const i=await api('/dev/sql',{method:'POST', body: JSON.stringify({sql:"SELECT * FROM invoices ORDER BY id DESC LIMIT 200"})}); setInv(i.items||[]);
  };
  useEffect(()=>{ load(); },[]);

  return <Card title="Kargo & Fatura" extra={
    <>
      <Button onClick={()=>setOpenShip(true)}>Kargo Oluştur</Button>
      <Button onClick={()=>setOpenInv(true)} style={{marginLeft:8}}>Fatura Oluştur</Button>
    </>
  }>
    <Tabs items={[
      {key:'ship', label:'Kargo', children:
        <Table rowKey="id" dataSource={ship} pagination={{pageSize:20}} columns={[
          {title:'ID',dataIndex:'id'},{title:'Sipariş No',dataIndex:'order_external_id'},{title:'Kargo',dataIndex:'carrier'},
          {title:'Takip No',dataIndex:'tracking_no'},
          {title:'Durum',dataIndex:'status'},
          {
            title:'', 
            render:(_:any,row:any)=> row.status!=='label_ready'
              ? <Button onClick={async()=>{ 
                  // create_label job tetiklemek için: var olan shipment zaten create_label kuyruğuna atılıyor.
                  // Burada sadece refresh:
                  setTimeout(load, 1200);
                }}>Etiket Hazırla</Button>
              : <a href={row.label_url} target="_blank" rel="noreferrer">Etiketi Aç</a>
          }
        ] as any}/>
      },
      {key:'inv', label:'Fatura', children:
        <Table rowKey="id" dataSource={inv} pagination={{pageSize:20}} columns={[
          {title:'ID',dataIndex:'id'},{title:'Sipariş No',dataIndex:'order_external_id'},{title:'Fatura No',dataIndex:'number'},
          {title:'Durum',dataIndex:'status'},{title:'PDF',dataIndex:'pdf_url', render:(u:string)=> u? <a href={u} target="_blank">PDF</a> : '-'}
        ] as any}/>
      }
    ]}/>

         <Modal title="Kargo Oluştur" open={openShip} onCancel={()=>setOpenShip(false)} onOk={async()=>{ const v=await fs.validateFields(); const r=await api('/shipments',{method:'POST', body: JSON.stringify(v)}); r?.ok? (message.success('Kargo oluşturuldu'), setOpenShip(false), load()):message.error('Hata'); }}>
       <Form layout="vertical" form={fs}>
         <Form.Item name="order_external_id" label="Sipariş No" rules={[{required:true}]}> <Input/> </Form.Item>
        <Form.Item name="carrier" label="Kargo"> <Input placeholder="Yurtiçi / Aras / ..." /> </Form.Item>
      </Form>
    </Modal>

         <Modal title="Fatura Oluştur" open={openInv} onCancel={()=>setOpenInv(false)} onOk={async()=>{ const v=await fi.validateFields(); const r=await api('/invoices',{method:'POST', body: JSON.stringify(v)}); r?.ok? (message.success('Fatura kuyruğa eklendi'), setOpenInv(false), load()):message.error('Hata'); }}>
       <Form layout="vertical" form={fi}>
         <Form.Item name="order_external_id" label="Sipariş No" rules={[{required:true}]}> <Input/> </Form.Item>
      </Form>
    </Modal>
  </Card>;
}
