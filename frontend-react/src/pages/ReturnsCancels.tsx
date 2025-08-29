import { useEffect, useState } from "react";
import { Card, Table, Button, message, Tabs, Popconfirm } from "antd";
import { api } from "../api";

export default function ReturnsCancels(){
  const [ret,setRet]=useState<any[]>([]);
  const [can,setCan]=useState<any[]>([]);
  
  const load=async()=>{
    const r=await api('/dev/sql',{method:'POST', body: JSON.stringify({sql:"SELECT * FROM mp_returns ORDER BY requested_at DESC LIMIT 500"})}); 
    setRet(r.items||[]);
    const c=await api('/dev/sql',{method:'POST', body: JSON.stringify({sql:"SELECT * FROM mp_cancellations ORDER BY requested_at DESC LIMIT 500"})}); 
    setCan(c.items||[]);
  };
  
  useEffect(()=>{ load(); },[]);

  return (
    <Card title="İadeler & İptaller" extra={
      <>
        <Button onClick={async()=>{ 
          const r=await api('/returns/pull',{method:'POST'}); 
          r?.ok? (message.success(`İade çekildi: ${r.imported}`),load()):message.error(r?.error||'Hata'); 
        }}>Trendyol İadeleri Çek</Button>
        <Button onClick={async()=>{ 
          const r=await api('/cancellations/pull',{method:'POST'}); 
          r?.ok? (message.success(`İptal çekildi: ${r.imported}`),load()):message.error(r?.error||'Hata'); 
        }} style={{marginLeft:8}}>Trendyol İptalleri Çek</Button>
      </>
    }>
      <Tabs items={[
        {
          key:'ret', 
          label:'İadeler', 
          children: (
            <Table 
              rowKey="id" 
              dataSource={ret} 
              pagination={{pageSize:20}} 
              columns={[
                {title:'İade ID',dataIndex:'external_id'},
                {title:'Sipariş No',dataIndex:'order_external_id'},
                {title:'Sebep',dataIndex:'reason'},
                {title:'Durum',dataIndex:'status'},
                {title:'Talep Tarihi',dataIndex:'requested_at'},
                {
                  title:'İşlem', 
                  render:(_:any,r:any)=> (
                    <>
                      <Popconfirm 
                        title="İadeyi kabul et?" 
                        onConfirm={async()=>{ 
                          const x=await api(`/returns/${r.external_id}/act`,{method:'POST', body: JSON.stringify({action:'accept'})}); 
                          x?.ok? (message.success('Kabul edildi'),load()):message.error('Hata'); 
                        }}
                      >
                        <Button type="primary">Kabul</Button>
                      </Popconfirm>
                      <Popconfirm 
                        title="İadeyi reddet?" 
                        onConfirm={async()=>{ 
                          const x=await api(`/returns/${r.external_id}/act`,{method:'POST', body: JSON.stringify({action:'reject'})}); 
                          x?.ok? (message.success('Reddedildi'),load()):message.error('Hata'); 
                        }}
                      >
                        <Button danger style={{marginLeft:8}}>Reddet</Button>
                      </Popconfirm>
                      <Button 
                        style={{marginLeft:8}} 
                        onClick={async()=>{ 
                          const x=await api(`/returns/${r.external_id}/push-woo`,{method:'POST'}); 
                          x?.ok? message.success('Woo refund oluşturuldu'):message.error(x?.error||('HTTP '+x?.code)); 
                        }}
                      >Woo Refund</Button>
                    </>
                  )
                }
              ]}
            />
          )
        },
        {
          key:'can', 
          label:'İptaller', 
          children: (
            <Table 
              rowKey="id" 
              dataSource={can} 
              pagination={{pageSize:20}} 
              columns={[
                {title:'İptal ID',dataIndex:'external_id'},
                {title:'Sipariş No',dataIndex:'order_external_id'},
                {title:'Sebep',dataIndex:'reason'},
                {title:'Durum',dataIndex:'status'},
                {title:'Talep Tarihi',dataIndex:'requested_at'},
                {
                  title:'İşlem', 
                  render:(_:any,r:any)=> (
                    <>
                      <Button onClick={async()=>{ 
                        const x=await api(`/cancellations/${r.external_id}/approve`,{method:'POST'}); 
                        x?.ok? (message.success('Onaylandı'),load()):message.error('Hata'); 
                      }}>Onayla</Button>
                      <Button 
                        style={{marginLeft:8}} 
                        onClick={async()=>{ 
                          const x=await api(`/cancellations/${r.external_id}/push-woo`,{method:'POST'}); 
                          x?.ok? message.success('Woo iptal edildi'):message.error(x?.error||('HTTP '+x?.code)); 
                        }}
                      >Woo İptal</Button>
                    </>
                  )
                }
              ]}
            />
          )
        }
      ]}/>
    </Card>
  );
}
