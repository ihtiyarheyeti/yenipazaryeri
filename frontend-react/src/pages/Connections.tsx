import { useEffect, useState } from "react";
import { Table, Card, Pagination, Form, Input, InputNumber, Select, Button, message, Space, Drawer, DatePicker } from "antd";
import { api } from "../api";

export default function Connections(){
  const [rows, setRows] = useState<any[]>([]); 
  const [mps, setMps] = useState<any[]>([]);
  const [page, setPage] = useState(1); 
  const [filters, setFilters] = useState<any>({});
  const [filterOpen, setFilterOpen] = useState(false);
  const pageSize = 10; 
  const [total, setTotal] = useState(0);
  const [form] = Form.useForm();
  const [fp] = Form.useForm();
  const [selectedMarketplace, setSelectedMarketplace] = useState<number | null>(null);
  const [loading, setLoading] = useState(false);

  const load = async () => {
    try {
      setLoading(true);
      console.log("Marketplaces yükleniyor...");
      
      // Marketplaces endpoint'ini çağır
      const m = await api(`/marketplaces`); 
      console.log("Marketplaces response:", m);
      
      if (m?.ok && m?.items) {
        setMps(m.items);
        console.log("Marketplaces set edildi:", m.items);
      } else {
        console.error("Marketplaces yüklenemedi:", m);
        // Fallback: Hardcoded marketplaces
        const fallbackMps = [
          { id: 1, name: 'Trendyol', base_url: 'https://api.trendyol.com/sapigw' },
          { id: 2, name: 'WooCommerce', base_url: 'http://localhost/wp-json/wc/v3' }
        ];
        setMps(fallbackMps);
        console.log("Fallback marketplaces kullanıldı:", fallbackMps);
        message.warning("API'den marketplace listesi alınamadı, varsayılan seçenekler kullanılıyor");
      }
      
      const params = new URLSearchParams({
        tenant_id: '1',
        page: page.toString(),
        pageSize: pageSize.toString()
      });
      
      // Filtreleri ekle
      Object.entries(filters).forEach(([key, value]) => {
        if(value !== null && value !== undefined && value !== '') {
          if(Array.isArray(value)) {
            // DatePicker.RangePicker için
            if(value.length === 2 && value[0] && value[1]) {
              params.append('dateFrom', value[0].format('YYYY-MM-DD'));
              params.append('dateTo', value[1].format('YYYY-MM-DD'));
            }
          } else {
            params.append(key, value.toString());
          }
        }
      });
      
      const r = await api(`/connections?${params.toString()}`); 
      if(r?.ok){ 
        setRows(r.items||[]); 
        setTotal(r.total||0); 
      }
    } catch (error) {
      console.error("Load error:", error);
      // Fallback: Hardcoded marketplaces
      const fallbackMps = [
        { id: 1, name: 'Trendyol', base_url: 'https://api.trendyol.com/sapigw' },
        { id: 2, name: 'WooCommerce', base_url: 'http://localhost/wp-json/wc/v3' }
      ];
      setMps(fallbackMps);
      message.error("Veriler yüklenemedi, varsayılan marketplace seçenekleri kullanılıyor");
    } finally {
      setLoading(false);
    }
  };
  
  useEffect(() => { 
    console.log("Connections useEffect çalıştı");
    load(); 
  }, [page, filters]);

  const submit = async (v: any) => { 
    try {
      console.log("Submit data:", v);
      console.log("API call starting...");
      const r = await api(`/connections`, {method:"POST", body:JSON.stringify({tenant_id:1,...v})});
      console.log("API response:", r);
      if(r?.ok){ 
        message.success("Kaydedildi"); 
        form.resetFields(); 
        load(); 
      } else { 
        console.log("API error:", r?.error);
        message.error(r?.error||"Hata"); 
      }
    } catch (error) {
      console.error("Submit error:", error);
      message.error("Kaydetme başarısız");
    }
  };

  const ping = async (id: number) => { 
    try {
      const r = await api(`/connections/${id}/test`, {method: "POST"}); 
      r?.ok ? message.success("Bağlantı OK") : message.error(r?.error||"Hata"); 
    } catch (error) {
      message.error("Ping başarısız");
    }
  };

  const deleteConnection = async (id: number) => {
    try {
      const r = await api(`/connections/${id}`, {method: "DELETE"}); 
      if(r?.ok) {
        message.success("Bağlantı silindi");
        load();
      } else {
        message.error(r?.error||"Silme hatası");
      }
    } catch (error) {
      message.error("Silme başarısız");
    }
  };

  const columns = [
    {title:"ID", dataIndex:"id", width:60},
    {title:"Marketplace", dataIndex:"marketplace_name"},
    {title:"API Anahtarı", dataIndex:"api_key", render: (key: string) => key ? `${key.substring(0,8)}...` : '-'},
    {title:"Tedarikçi", dataIndex:"supplier_id"},
    {title:"Test", render:(_:any, r:any) => <Button onClick={() => ping(r.id)}>Test</Button>},
    {title:"İşlemler", render:(_:any, r:any) => (
      <Space>
        <Button onClick={() => ping(r.id)}>Test</Button>
        <Button danger onClick={() => deleteConnection(r.id)}>Sil</Button>
      </Space>
    )}
  ];

  return (
    <div className="grid gap-4">
      <Card title="Yeni Bağlantı">
        <Form layout="vertical" form={form} onFinish={submit}>
          <Form.Item name="marketplace_id" label="Marketplace" rules={[{required:true}]}>
            <Select 
              placeholder="Marketplace seçin..."
              loading={loading}
              options={(mps||[]).map((x:any) => ({label:x.name, value:x.id}))}
              onChange={(value) => {
                console.log("Selected marketplace:", value);
                setSelectedMarketplace(value);
              }}
            />
          </Form.Item>
          
          {selectedMarketplace && (
            <>
              <Form.Item name="api_key" label="API Anahtarı" rules={[{required:true}]}>
                <Input placeholder={selectedMarketplace === 1 ? "Trendyol API Key" : "WooCommerce Consumer Key"}/>
              </Form.Item>
              
              <Form.Item name="api_secret" label="API Secret" rules={[{required:true}]}>
                <Input.Password placeholder={selectedMarketplace === 1 ? "Trendyol API Secret" : "WooCommerce Consumer Secret"}/>
              </Form.Item>
              
              {selectedMarketplace === 2 && (
                <Form.Item name="base_url" label="Temel URL" rules={[{required:true}]}>
                  <Input placeholder="http://siteniz.com/wp-json/wc/v3"/>
                </Form.Item>
              )}
              
              {selectedMarketplace === 1 && (
                <Form.Item name="supplier_id" label="Tedarikçi ID" rules={[{required:true}]}>
                  <InputNumber style={{width:"100%"}} placeholder="Trendyol Supplier ID"/>
                </Form.Item>
              )}
            </>
          )}
          
          <Form.Item>
            <Button type="primary" htmlType="submit" disabled={!selectedMarketplace}>Kaydet</Button>
          </Form.Item>
        </Form>
      </Card>
      
      <Card 
        title="Bağlantılar" 
        extra={
          <Button onClick={() => setFilterOpen(true)}>Filtreler</Button>
        }
      >
        <Table 
          rowKey="id" 
          dataSource={rows} 
          columns={columns as any} 
          pagination={false}
          loading={loading}
        />
        <div style={{display:"flex",justifyContent:"end",marginTop:12}}>
          <Pagination current={page} pageSize={pageSize} total={total} onChange={setPage}/>
        </div>
      </Card>

      <Drawer 
        title="Bağlantı Filtreleri" 
        open={filterOpen} 
        onClose={() => setFilterOpen(false)} 
        width={400}
        extra={
          <Button type="primary" onClick={() => {
            const v = fp.getFieldsValue();
            setFilters(v);
            setPage(1);
            setFilterOpen(false);
          }}>Uygula</Button>
        }
      >
        <Form layout="vertical" form={fp}>
          <Form.Item name="marketplace_id" label="Marketplace">
            <Select allowClear options={[
              {label:"Trendyol", value:1},
              {label:"WooCommerce", value:2}
            ]} />
          </Form.Item>
          <Form.Item name="q" label="Ara (Tedarikçi/API Anahtarı)">
            <Input placeholder="Tedarikçi ID veya API Anahtarı'nda ara..." />
          </Form.Item>
          <Form.Item label="Tarih">
            <DatePicker.RangePicker style={{width:"100%"}} />
          </Form.Item>
        </Form>
      </Drawer>
      
      {/* Debug bilgisi */}
      <Card title="Debug Bilgisi" size="small">
        <div>Marketplaces: {JSON.stringify(mps)}</div>
        <div>Selected: {selectedMarketplace}</div>
        <div>Loading: {loading ? 'true' : 'false'}</div>
      </Card>
    </div>
  );
}
