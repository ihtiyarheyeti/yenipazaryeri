import { useEffect, useState } from "react";
import { Table, Card, Button, Modal, Form, Input, InputNumber, message, Tag, Space, Drawer, Select } from "antd";
import { api } from "../api";

export default function Variants(){
  const [rows, setRows] = useState<any[]>([]);
  const [products, setProducts] = useState<any[]>([]);
  const [filters, setFilters] = useState<any>({});
  const [filterOpen, setFilterOpen] = useState(false);
  const [open, setOpen] = useState(false);
  const [form] = Form.useForm();
  const [fp] = Form.useForm();
  
  const loadProducts = async () => {
    try {
      const r = await api('/products?tenant_id=1&page=1&pageSize=1000');
      setProducts(r.items || []);
    } catch (error) {
      console.error("Ürünler yüklenemedi:", error);
    }
  };
  
  const reload = async () => { 
    try {
      const params = new URLSearchParams({
        tenant_id: '1',
        page: '1',
        pageSize: '50'
      });
      
      // Filtreleri ekle
      Object.entries(filters).forEach(([key, value]) => {
        if(value !== null && value !== undefined && value !== '') {
          params.append(key, value.toString());
        }
      });
      
      const r = await api(`/variants?${params.toString()}`); 
      setRows(r.items||[]); 
    } catch (error) {
      console.error("Varyant yükleme hatası:", error);
    }
  };
  
  useEffect(() => { 
    loadProducts();
    reload(); 
  }, [filters]);

  const submit = async () => { 
    try {
      const v = await form.validateFields();
      let attrs = {}; 
      
      try { 
        attrs = v.attrs ? JSON.parse(v.attrs) : {}; 
      } catch { 
        message.error("Özellikler JSON formatında olmalı"); 
        return; 
      }
      
      const r = await api(`/variants`, {method:"POST", body:JSON.stringify({
        product_id: Number(v.product_id), 
        sku: v.sku, 
        price: Number(v.price), 
        stock: Number(v.stock), 
        attrs
      })});
      
      if(r?.ok){ 
        message.success("Varyant eklendi"); 
        setOpen(false); 
        form.resetFields(); 
        reload(); 
      } else { 
        message.error(r?.error||"Hata"); 
      }
    } catch (error) {
      message.error("Varyant eklenemedi");
    }
  };

  const columns = [
    {title:"ID", dataIndex:"id", width:70},
    {title:"Ürün", dataIndex:"product_name", render: (name: any, row: any) => {
      if (name) return name;
      return <span style={{opacity: 0.6}}>Ürün #{row.product_id}</span>;
    }},
    {title:"SKU", dataIndex:"sku"},
    {title:"Fiyat", dataIndex:"price", render: (price: any) => {
      if (price === null || price === undefined) return "₺0.00";
      const numPrice = Number(price);
      return isNaN(numPrice) ? "₺0.00" : `₺${numPrice.toFixed(2)}`;
    }},
    {title:"Stok", dataIndex:"stock"},
    {title:"Özellikler", dataIndex:"attrs", render: (attrs: any) => {
      if (!attrs) return "—";
      try {
        const parsed = typeof attrs === 'string' ? JSON.parse(attrs) : attrs;
        return Object.entries(parsed).map(([key, value]) => (
          <Tag key={key} color="blue" style={{margin: '2px'}}>
            {key}: {String(value)}
          </Tag>
        ));
      } catch {
        return <span style={{opacity: 0.6}}>JSON Hatası</span>;
      }
    }},
  ];
  
  return (
    <Card 
      title="Varyant Yönetimi" 
      extra={
        <Space>
          <Button onClick={() => setFilterOpen(true)}>Filtreler</Button>
          <Button type="primary" onClick={() => setOpen(true)}>Yeni Varyant</Button>
        </Space>
      }
    >
      <Table 
        rowKey="id" 
        columns={columns} 
        dataSource={rows} 
        pagination={false}
      />

      <Modal 
        open={open} 
        onOk={submit} 
        onCancel={() => setOpen(false)} 
        title="Yeni Varyant"
        okText="Ekle"
        cancelText="İptal"
      >
        <Form layout="vertical" form={form}>
          <Form.Item 
            name="product_id" 
            label="Ürün ID" 
            rules={[{required:true, message:"Ürün ID gerekli"}]}
          >
            <InputNumber style={{width:"100%"}} />
          </Form.Item>
          
          <Form.Item name="sku" label="SKU">
            <Input />
          </Form.Item>
          
          <Form.Item 
            name="price" 
            label="Fiyat" 
            rules={[{required:true, message:"Fiyat gerekli"}]}
          >
            <InputNumber style={{width:"100%"}} min={0} step={0.01} />
          </Form.Item>
          
          <Form.Item 
            name="stock" 
            label="Stok" 
            rules={[{required:true, message:"Stok gerekli"}]}
          >
            <InputNumber style={{width:"100%"}} min={0} />
          </Form.Item>
          
          <Form.Item name="attrs" label="Özellikler (JSON)">
            <Input.TextArea rows={3} placeholder='{"renk":"kırmızı","beden":"M"}' />
          </Form.Item>
        </Form>
      </Modal>

      <Drawer 
        title="Varyant Filtreleri" 
        open={filterOpen} 
        onClose={() => setFilterOpen(false)} 
        width={400}
        extra={
          <Button type="primary" onClick={() => {
            const v = fp.getFieldsValue();
            setFilters(v);
            setFilterOpen(false);
          }}>Uygula</Button>
        }
      >
        <Form layout="vertical" form={fp}>
          <Form.Item name="q" label="SKU Ara">
            <Input placeholder="SKU'da ara..." />
          </Form.Item>
          <Form.Item name="product_id" label="Ürün ID">
            <InputNumber style={{width:"100%"}} placeholder="Belirli ürün" />
          </Form.Item>
          <Form.Item name="priceMin" label="Min Fiyat">
            <InputNumber style={{width:"100%"}} min={0} step={0.01} />
          </Form.Item>
          <Form.Item name="priceMax" label="Max Fiyat">
            <InputNumber style={{width:"100%"}} min={0} step={0.01} />
          </Form.Item>
          <Form.Item name="stockMin" label="Min Stok">
            <InputNumber style={{width:"100%"}} min={0} />
          </Form.Item>
          <Form.Item name="stockMax" label="Max Stok">
            <InputNumber style={{width:"100%"}} min={0} />
          </Form.Item>
        </Form>
      </Drawer>
    </Card>
  );
}
