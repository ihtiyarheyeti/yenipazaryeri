import { useEffect, useState } from "react";
import { Table, Button, Input, Space, Modal, Form, Upload, message, Tag, Select, Drawer, Tooltip, Dropdown } from "antd";
import { PlusOutlined, SearchOutlined, FilterOutlined, UploadOutlined, DownloadOutlined, EllipsisOutlined, SyncOutlined } from "@ant-design/icons";
import { api, API_BASE } from "../api";

const { Search } = Input;

export default function WooProducts(){
  const [rows,setRows]=useState<any[]>([]);
  const [loading,setLoading]=useState(false);
  const [total,setTotal]=useState(0);
  const [page,setPage]=useState(1);
  const [pageSize]=useState(10);
  const [search,setSearch]=useState("");
  const [modalOpen,setModalOpen]=useState(false);
  const [logsOpen,setLogsOpen]=useState(false);
  const [logs,setLogs]=useState<any[]>([]);
  const [selected,setSelected]=useState<any[]>([]);
  const [imgOpen,setImgOpen]=useState(false);
  const [imgRows,setImgRows]=useState<any[]>([]);
  const [imgPid,setImgPid]=useState(0);
  const [filterOpen,setFilterOpen]=useState(false);
  const [filters,setFilters]=useState<any>({});

  async function load(){
    setLoading(true);
    try {
      const params = new URLSearchParams({
        tenant_id: "1",
        page: page.toString(),
        pageSize: pageSize.toString(),
        mp: "2", // WooCommerce marketplace_id
        ...filters
      });
      if(search) params.append("q", search);
      
      const r = await api(`/products?${params}`);
      if(r?.ok){
        setRows(r.items || []);
        setTotal(r.total || 0);
      }
    } catch(e) {
      message.error("WooCommerce ürünleri yüklenemedi");
    } finally {
      setLoading(false);
    }
  }

  useEffect(()=>{ load(); },[page,pageSize,search,filters]);

  async function openLogs(pid: number){
    setImgPid(pid);
    const r = await api(`/products/${pid}/logs`);
    setLogs(r.items || []);
    setLogsOpen(true);
  }

  async function openImages(pid: number){
    setImgPid(pid);
    const r = await api(`/product-images?product_id=${pid}`);
    setImgRows(r.items || []);
    setImgOpen(true);
  }

  function handleMenu(key: string, row: any) {
    switch(key) {
      case "images":
        openImages(row.id);
        break;
      case "logs":
        openLogs(row.id);
        break;
      case "variants":
        api(`/products/${row.id}/create-woo-variations`,{method:'POST'})
          .then((r)=>{message.info(`Created: ${r.created||0}`); load(); });
        break;
      case "archive":
        api(`/products/${row.id}/archive`,{method:"POST"})
          .then(()=>{message.success('Arşivlendi'); load(); });
        break;
      case "draft":
        api(`/products/${row.id}/restore`,{method:"POST"})
          .then(()=>{message.success('Taslağa alındı'); load(); });
        break;
      case "approve":
        api(`/products/${row.id}/review`,{method:"POST",body:JSON.stringify({status:'approved'})})
          .then(()=>{message.success('Onaylandı'); load(); });
        break;
      case "reject":
        api(`/products/${row.id}/review`,{method:"POST",body:JSON.stringify({status:'rejected'})})
          .then(()=>{message.success('Reddedildi'); load(); });
        break;
    }
  }

  const menuItems = [
    { key: "images", label: "Görseller" },
    { key: "logs", label: "Loglar" },
    { key: "variants", label: "Woo Varyant Oluştur" },
    { key: "archive", label: "Arşivle" },
    { key: "draft", label: "Taslağa Al" },
    { key: "approve", label: "Onayla" },
    { key: "reject", label: "Reddet" },
  ];

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
    {title:"İşlem", width:200, render:(_: any, r: any) => (
      <Space size="small">
        <Button 
          type="primary" 
          size="small" 
          onClick={()=>api(`/products/${r.id}/push/trendyol`,{method:'POST'})
            .then(()=>{message.success('Trendyol\'a gönderildi'); load();})}
        >
          Eşle ve Gönder TY
        </Button>
        <Dropdown menu={{ items: menuItems, onClick:(info)=> handleMenu(info.key,r) }}>
          <Button icon={<EllipsisOutlined/>} size="small"/>
        </Dropdown>
      </Space>
    )}
  ];

  return (
    <div>
      <div style={{display:"flex",justifyContent:"space-between",alignItems:"center",marginBottom:16}}>
        <h2>WooCommerce Ürünleri</h2>
        <Space>
          <Button icon={<SyncOutlined/>} onClick={()=>load()}>Yenile</Button>
          <Button onClick={()=>setModalOpen(true)} type="primary" icon={<PlusOutlined/>}>Yeni Ürün</Button>
        </Space>
      </div>

      <div style={{display:"flex",gap:8,marginBottom:16}}>
        <Search placeholder="Ürün ara..." value={search} onChange={e=>setSearch(e.target.value)} style={{width:300}} />
        <Button icon={<FilterOutlined/>} onClick={()=>setFilterOpen(true)}>Filtreler</Button>
        <Button icon={<DownloadOutlined/>} onClick={()=>window.open(`${API_BASE}/csv/products/export?tenant_id=1&mp=2`)}>CSV Dışa Aktar</Button>
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
          onChange: (p) => { setPage(p); }
        }}
        rowSelection={{
          selectedRowKeys: selected,
          onChange: (keys) => setSelected(keys)
        }}
      />

      {/* Yeni Ürün Modal */}
      <Modal title="Yeni Ürün" open={modalOpen} onCancel={()=>setModalOpen(false)} onOk={async()=>{
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

      {/* Ürün Logları Drawer */}
      <Drawer title={`Ürün Logları #${imgPid}`} open={logsOpen} onClose={()=>setLogsOpen(false)} width={520}>
        <Table
          dataSource={logs}
          pagination={false}
          columns={[
            {title:"ID", dataIndex:"id", width:70},
            {title:"Tip", dataIndex:"type"},
            {title:"Durum", dataIndex:"status"},
            {title:"Mesaj", dataIndex:"message"},
            {title:"Zaman", dataIndex:"created_at"},
          ] as any}
        />
      </Drawer>

      {/* Ürün Görselleri Drawer */}
      <Drawer 
        title={`Ürün Görselleri #${imgPid}`} 
        open={imgOpen} 
        onClose={() => setImgOpen(false)} 
        width={520}
        extra={
          <div style={{display:"flex",gap:8}}>
            <Button onClick={async()=>{ const r=await api(`/integrations/trendyol/images/${imgPid}`,{method:"POST",body:JSON.stringify({tenant_id:1})}); r?.ok?message.success('Trendyol image sync OK'):message.error(r?.error||'Hata'); }}>TY Image Sync</Button>
            <Button onClick={async()=>{ const r=await api(`/integrations/woo/images/${imgPid}`,{method:"POST",body:JSON.stringify({tenant_id:1})}); r?.ok?message.success('Woo image sync OK'):message.error(r?.error||'Hata'); }}>Woo Image Sync</Button>
          </div>
        }
      >
        <Upload
          name="file"
          action={`${API_BASE}/upload/product-image?product_id=${imgPid}`}
          listType="picture-card"
          onChange={async (i) => { 
            if(i.file.status === "done") { 
              const r = await api(`/product-images?product_id=${imgPid}`); 
              setImgRows(r.items || []); 
            } 
          }}
          accept=".jpg,.jpeg,.png,.webp"
          multiple
        >
          + Yükle
        </Upload>
        
        <div style={{display:"grid", gridTemplateColumns:"repeat(3,1fr)", gap:8, marginTop:16}}>
          {imgRows.map((x:any) => (
            <div key={x.id} style={{position:"relative"}}>
              <picture>
                {x.thumb_webp && <source srcSet={`${API_BASE}${x.thumb_webp}`} type="image/webp" />}
                <img 
                  loading="lazy"
                  src={`${API_BASE}${x.thumb_url || x.url}`} 
                  alt="" 
                  style={{width:"100%", borderRadius:8}}
                  onLoad={(e) => e.currentTarget.classList.add('loaded')}
                />
              </picture>
              <div style={{position:"absolute",left:6,bottom:6,display:"flex",gap:6}}>
                <span className="badge" style={{background:x.synced_to_ty?'#1677ff55':'#00000022',padding:'2px 6px',borderRadius:6}}>TY</span>
                <span className="badge" style={{background:x.synced_to_woo?'#52c41a55':'#00000022',padding:'2px 6px',borderRadius:6}}>WOO</span>
              </div>
              <Button 
                size="small" 
                danger 
                style={{position:"absolute", top:6, right:6}} 
                onClick={async() => { 
                  await api(`/product-images/${x.id}`, {method:"DELETE"}); 
                  const r = await api(`/product-images?product_id=${imgPid}`); 
                  setImgRows(r.items || []); 
                }}
              >
                Sil
              </Button>
            </div>
          ))}
        </div>
      </Drawer>

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
    </div>
  );
}
