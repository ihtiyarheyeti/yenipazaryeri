import { useEffect, useState } from "react";
import { Table, Input, Button, message, Pagination, Card, Upload, Space, Popconfirm } from "antd";
import { UploadOutlined, DownloadOutlined } from "@ant-design/icons";
import { api, API_BASE } from "../api";

export default function CategoryMapping(){
  const [rows, setRows] = useState<any[]>([]);
  const [total, setTotal] = useState(0);
  const [page, setPage] = useState(1);
  const [q, setQ] = useState("");
  const [marketplaceId, setMarketplaceId] = useState(0);
  const pageSize = 10;

  const load = async () => {
    try {
      const params = new URLSearchParams({
        tenant_id: '1',
        page: page.toString(),
        pageSize: pageSize.toString()
      });
      if (q) params.append('q', q);
      if (marketplaceId > 0) params.append('marketplace_id', marketplaceId.toString());
      
      const res = await api(`/category-mappings?${params}`);
      if(res?.ok){ 
        setRows(res.items||[]); 
        setTotal(res.total||0); 
      }
    } catch (error) {
      message.error("Kategori eşleştirmeleri yüklenemedi");
    }
  };
  
  useEffect(() => { load(); }, [page, q, marketplaceId]);

  const handleUpload = async (file: any) => {
    try {
      const formData = new FormData();
      formData.append('file', file);
      
      const res = await fetch(`${API_BASE}/csv/category-mappings/import?tenant_id=1`, {
        method: 'POST',
        body: formData
      });
      
      const result = await res.json();
      if (result?.ok) {
        message.success(`${result.inserted} eklendi, ${result.updated} güncellendi`);
        load();
      } else {
        message.error(result?.error || "Yükleme başarısız");
      }
    } catch (error) {
      message.error("Dosya yüklenemedi");
    }
    return false; // Prevent default upload
  };

  const columns = [
    {title:"ID", dataIndex:"id", width:70},
    {title:"Pazar Yeri", dataIndex:"marketplace_name"},
    {title:"Kaynak Yol", dataIndex:"source_path"},
    {title:"Dış Kategori ID", dataIndex:"external_category_id"},
    {title:"Not", dataIndex:"note"},
    {title:"İşlem", render: (_:any, r:any) => (
      <Space>
        <Button onClick={async() => {
          const source_path = prompt("Kaynak Yol", r.source_path) || r.source_path;
          const external_category_id = prompt("Dış Kategori ID", r.external_category_id) || r.external_category_id;
          const note = prompt("Not", r.note || "") || r.note;
          
          try {
            const ok = await api(`/category-mappings/${r.id}`, {
              method:"PUT",
              body:JSON.stringify({source_path, external_category_id, note})
            });
            
            if (ok?.ok) {
              message.success("Güncellendi");
              load();
            } else {
              message.error(ok?.error || "Hata");
            }
          } catch (error) {
            message.error("Güncelleme başarısız");
          }
        }}>Düzenle</Button>
        
        <Popconfirm 
          title="Silinsin mi?" 
          onConfirm={async() => { 
            try {
              const ok = await api(`/category-mappings/${r.id}`, {method:"DELETE"}); 
              if (ok?.ok) {
                message.success("Silindi");
                load();
              } else {
                message.error(ok?.error || "Hata");
              }
            } catch (error) {
              message.error("Silme başarısız");
            }
          }}
        >
          <Button danger>Sil</Button>
        </Popconfirm>
      </Space>
    )}
  ];
  
  return (
    <Card title="Kategori Eşleştirme">
      <div className="toolbar" style={{display:"flex",gap:8,marginBottom:12}}>
        <Input.Search 
          placeholder="Ara..." 
          onSearch={(v) => {setPage(1); setQ(v);}} 
          allowClear 
          style={{maxWidth:280}}
        />
        <Input 
          placeholder="Pazar Yeri ID" 
          type="number"
          onChange={(e) => setMarketplaceId(Number(e.target.value) || 0)}
          style={{width:150}}
        />
        <Button onClick={() => window.open(`${API_BASE}/csv/category-mappings/export?tenant_id=1`, "_blank")}>
          <DownloadOutlined /> CSV Dışa Aktar
        </Button>
        <Upload
          accept=".csv"
          beforeUpload={handleUpload}
          showUploadList={false}
        >
          <Button icon={<UploadOutlined />}>CSV İçe Aktar</Button>
        </Upload>
      </div>
      
      <Table 
        rowKey="id" 
        columns={columns} 
        dataSource={rows} 
        pagination={false}
      />
      
      <div style={{display:"flex",justifyContent:"end",marginTop:12}}>
        <Pagination 
          current={page} 
          pageSize={pageSize} 
          total={total} 
          onChange={setPage}
        />
      </div>
    </Card>
  );
}
