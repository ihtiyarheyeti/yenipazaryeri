import { useEffect, useState } from "react";
import { Table, Button, Input, Space, Modal, Form, Upload, message, Tag, Select, Drawer } from "antd";
import { PlusOutlined, SearchOutlined, FilterOutlined, UploadOutlined, DownloadOutlined } from "@ant-design/icons";
import { api, API_BASE } from "../api";
import { ProductRowActions } from "../components/ProductRowActions";

const { Search } = Input;

export default function Products(){
  const [rows,setRows]=useState<any[]>([]);
  const [loading,setLoading]=useState(false);
  const [total,setTotal]=useState(0);
  const [page,setPage]=useState(1);
  const [pageSize,setPageSize]=useState(10);
  const [search,setSearch]=useState("");
  const [modalOpen,setModalOpen]=useState(false);
  const [selected,setSelected]=useState<any[]>([]);
  const [filterOpen,setFilterOpen]=useState(false);
  const [filters,setFilters]=useState<any>({});
  const [valOpen,setValOpen]=useState(false);
  const [validation,setValidation]=useState<any>(null);

  async function load(){
    setLoading(true);
    try {
      const params = new URLSearchParams({
        tenant_id: "1",
        page: page.toString(),
        pageSize: pageSize.toString(),
        ...filters
      });
      if(search) params.append("q", search);
      
      const r = await api(`/products?${params}`);
      if(r?.ok){
        setRows(r.items || []);
        setTotal(r.total || 0);
      }
    } catch(e) {
      message.error("Ürünler yüklenemedi");
    } finally {
      setLoading(false);
    }
  }

  useEffect(()=>{ load(); },[page,pageSize,search,filters]);







  const columns = [
    {title:"ID", dataIndex:"id", width:70},
    {title:"Ad", dataIndex:"name", render:(name: string, r: any) => (
      <div>
        <div style={{fontWeight:500}}>{name}</div>
        <div style={{fontSize:12,opacity:0.7}}>{r.brand}</div>
      </div>
    )},
    {title:"Kategori", dataIndex:"category_path", render:(path: string) => {
      if(!path) return "-";
      try {
        const cats = JSON.parse(path);
        return cats.join(" > ");
      } catch {
        return path;
      }
    }},
    {title:"Varyant", dataIndex:"variant_count", width:80, render:(count: number) => count || 0},
    {title:"Durum", dataIndex:"status", render:(s:string)=>{
      const color = s==='active'?'green': s==='archived'?'default':'gold';
      const label = s==='active'?'Aktif': s==='archived'?'Arşiv':'Taslak';
      return <Tag color={color}>{label}</Tag>;
    }},
    {title:"Review", dataIndex:"review_status", render:(s:string,r:any)=>{
      const color = s==='approved'?'green': s==='rejected'?'red':'default';
      const label = s==='approved'?'Onaylandı': s==='rejected'?'Reddedildi':'Bekliyor';
      return <Tag color={color}>{label}</Tag>;
    }},
    {title:"Woo", dataIndex:'sync_woo_status', render:(s:string,r:any)=> <span style={{textTransform:'capitalize'}}>{s||'none'}</span>},
    {title:'TY', dataIndex:'sync_trendyol_status', render:(s:string)=> <span style={{textTransform:'capitalize'}}>{s||'none'}</span>},
    {title:"Kategori Durumu", dataIndex:"category_match", width:120, render:(s:string) => 
      <span style={{color: s==='mapped'?'green':'red'}}>{s==='mapped'?'Eşleşti':'Eşleşmedi'}</span>
    },
    {title:"Attr Durumu", dataIndex:"attrs_match_status", width:120, render:(s:string) => {
      if(!s) return <span style={{color:'gray'}}>Yok</span>;
      const statuses = s.split(',');
      const mapped = statuses.filter(st => st === 'mapped').length;
      const total = statuses.length;
      return <span style={{color: mapped === total ? 'green' : 'red'}}>
        {mapped}/{total} Eşleşti
      </span>;
    }},
    {title:"Medya", dataIndex:"media_status", width:90, render:(s:string)=> <span style={{color:s==='ready'?'green': (s==='partial'?'#faad14':'#999')}}>{s||'none'}</span>},
    {title:"Durum", width:120, render:(_: any, r: any) => (
      <div style={{display:"flex",gap:4}}>
        <span className="badge" style={{background:r.trendyol_external_id?'#1677ff55':'#00000022',padding:'2px 6px',borderRadius:6}}>TY</span>
        <span className="badge" style={{background:r.woo_external_id?'#52c41a55':'#00000022',padding:'2px 6px',borderRadius:6}}>WOO</span>
      </div>
    )},
    {title:"İşlem", width:360, render:(_: any, r: any) => (
      <ProductRowActions row={r}/>
    )}
  ];

  return (
    <div>
      <div style={{display:"flex",justifyContent:"space-between",alignItems:"center",marginBottom:16}}>
        <h2>Tüm Ürünler</h2>
        <Space>
          <Select placeholder="Toplu İşlem" style={{width:160}} onChange={async(v)=>{
            if(!selected.length) return message.warning("Seçim yok");
            const r=await api('/products/bulk-status',{method:"POST", body: JSON.stringify({ids:selected, status:v})});
            r?.ok? (message.success('OK'), load()):message.error(r?.error||'Hata');
          }} options={[
            {label:'Taslak', value:'draft'},
            {label:'Aktif', value:'active'},
            {label:'Arşiv', value:'archived'}
          ]}/>
          <Button onClick={()=>setModalOpen(true)} type="primary" icon={<PlusOutlined/>}>Yeni Ürün</Button>
        </Space>
      </div>

      <div style={{display:"flex",gap:8,marginBottom:16}}>
        <Search placeholder="Ürün ara..." value={search} onChange={e=>setSearch(e.target.value)} style={{width:300}} />
        <Button icon={<FilterOutlined/>} onClick={()=>setFilterOpen(true)}>Filtreler</Button>
        <Upload name="file" action={`${API_BASE}/csv/products/validate`} onChange={(i)=>{ 
          if(i.file.status==='done'){ setValidation(i.file.response); setValOpen(true); }
        }}>
          <Button>CSV Doğrula</Button>
        </Upload>
        <Upload name="file" action={`${API_BASE}/csv/products/import`} onChange={(i)=>{ 
          if(i.file.status==='done'){ message.success('CSV import OK'); load(); }
        }}>
          <Button icon={<UploadOutlined/>}>CSV İçe Aktar</Button>
        </Upload>
        <Upload name="file" action={`${API_BASE}/csv/products/import-sync`} onChange={(i)=>{ 
          if(i.file.status==='done'){ message.success('Import + Sync kuyruğa alındı'); load(); }
        }}>
          <Button type="primary">CSV Import + Sync</Button>
        </Upload>
        <Button icon={<DownloadOutlined/>} onClick={()=>window.open(`${API_BASE}/csv/products/export?tenant_id=1`)}>CSV Dışa Aktar</Button>
      </div>

      <Table 
        columns={columns} 
        dataSource={rows} 
        loading={loading}
        rowKey="id"
        pagination={{
          current: page,
          pageSize: pageSize,
          total: total,
          onChange: (p,ps) => { setPage(p); setPageSize(ps); }
        }}
        rowSelection={{
          selectedRowKeys: selected,
          onChange: (keys) => setSelected(keys)
        }}
      />

      {/* Yeni Ürün Modal */}
      <Modal title="Yeni Ürün" open={modalOpen} onCancel={()=>setModalOpen(false)} onOk={async()=>{
        // Form submit logic
        setModalOpen(false);
        load();
      }}>
        <Form layout="vertical">
          <Form.Item label="Ad" name="name" rules={[{required:true}]}>
            <Input />
          </Form.Item>
          <Form.Item label="Marka" name="brand">
            <Input />
          </Form.Item>
          <Form.Item label="Açıklama" name="description">
            <Input.TextArea />
          </Form.Item>
        </Form>
      </Modal>





      {/* Gelişmiş Filtreler Drawer */}
      <Drawer title="Gelişmiş Filtreler" open={filterOpen} onClose={()=>setFilterOpen(false)} width={400}>
        <Form layout="vertical" onFinish={(v)=>{setFilters(v);setFilterOpen(false);}}>
          <Form.Item name="brand" label="Marka">
            <Input placeholder="Marka ara..." />
          </Form.Item>
          <Form.Item name="status" label="Durum">
            <Select allowClear options={[
              {label:'Taslak', value:'draft'},
              {label:'Aktif', value:'active'},
              {label:'Arşiv', value:'archived'}
            ]}/>
          </Form.Item>
          <Form.Item name="priceMin" label="Min Fiyat">
            <Input type="number" placeholder="0" />
          </Form.Item>
          <Form.Item name="priceMax" label="Max Fiyat">
            <Input type="number" placeholder="1000" />
          </Form.Item>
          <Form.Item name="dateFrom" label="Başlangıç Tarihi">
            <Input type="date" />
          </Form.Item>
          <Form.Item name="dateTo" label="Bitiş Tarihi">
            <Input type="date" />
          </Form.Item>
          <Form.Item>
            <Button type="primary" htmlType="submit" block>Filtrele</Button>
          </Form.Item>
        </Form>
      </Drawer>

      {/* CSV Validasyon Raporu Modal */}
      <Modal title="Validasyon Raporu" open={valOpen} onCancel={()=>setValOpen(false)} footer={null} width={720}>
        <div>Geçerli satır: {validation?.valid_rows||0}</div>
        <Table size="small" rowKey={(r:any)=>r.row} dataSource={validation?.errors||[]} pagination={{pageSize:8}}
          columns={[
            {title:"Satır", dataIndex:"row", width:70},
            {title:"Hatalar", render:(_:any,r:any)=> r.errors.map((e:any,i:number)=> <div key={i}><b>{e.field}</b>: {e.code} - {e.msg}</div>)}
          ] as any}/>
      </Modal>
    </div>
  );
}
