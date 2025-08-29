import { useEffect, useState } from "react";
import { Card, Col, Row, Statistic, Table, Tag } from "antd";
import { api } from "../api";

export default function Dashboard(){
  const [d,setD]=useState<any|null>(null);
  async function load(){ const r=await api(`/dashboard/metrics?tenant_id=1`); setD(r.data); }
  useEffect(()=>{ load(); const i=setInterval(load,10000); return ()=>clearInterval(i); },[]);
  if(!d) return <div>Yükleniyor…</div>;
  const logs=d.recentLogs||[];
  return (
    <div className="grid gap-4">
      <Row gutter={[16,16]}>
        <Col xs={24} md={12} lg={6}><Card><Statistic title="Ürün" value={d.totProducts}/></Card></Col>
        <Col xs={24} md={12} lg={6}><Card><Statistic title="Varyant" value={d.totVariants}/></Card></Col>
        <Col xs={24} md={12} lg={6}><Card><Statistic title="Trendyol Mapped" value={d.mappedTY}/></Card></Col>
        <Col xs={24} md={12} lg={6}><Card><Statistic title="Woo Mapped" value={d.mappedWOO}/></Card></Col>
      </Row>

      <Row gutter={[16,16]}>
        <Col xs={24} md={8}><Card><Statistic title="Pending Jobs" value={d.jobsPending}/></Card></Col>
        <Col xs={24} md={8}><Card><Statistic title="Error Jobs" value={d.jobsError} valueStyle={{color:'#fa541c'}}/></Card></Col>
        <Col xs={24} md={8}><Card><Statistic title="Dead Jobs" value={d.jobsDead} valueStyle={{color:'#cf1322'}}/></Card></Col>
      </Row>

      <Card title="Son İşlemler">
        <Table rowKey="id" size="small" pagination={false} dataSource={logs} columns={[
          {title:"ID",dataIndex:"id",width:70},
          {title:"Tip",dataIndex:"type"},
          {title:"Durum",dataIndex:"status",render:(s:string)=><Tag color={s==='success'?'green':s==='error'?'red':'default'}>{s}</Tag>},
          {title:"Mesaj",dataIndex:"message"},
          {title:"Zaman",dataIndex:"created_at"},
        ] as any}/>
      </Card>
    </div>
  );
}
