import { useEffect, useState } from "react";
import { api } from "../api";
import { Form, Input, Button, Select, InputNumber, message, Card } from "antd";

type Marketplace = { id:number; name:string };

export default function MappingForm() {
  const [form] = Form.useForm();
  const [mps, setMps] = useState<Marketplace[]>([]);
  const [loading, setLoading] = useState(false);

  useEffect(() => {
    (async () => {
      const d = await api("/marketplaces");
      setMps(d.items || []);
    })();
  }, []);

  const onFinish = async (values:any) => {
    setLoading(true);
    const r = await api("/product-mappings", {
      method: "POST",
      body: JSON.stringify({
        product_id: Number(values.product_id),
        marketplace_id: Number(values.marketplace_id),
        external_product_id: String(values.external_product_id).trim()
      })
    });
    setLoading(false);
    if (r?.ok) {
      message.success(r.updated ? "Eşleştirme güncellendi" : "Eşleştirme oluşturuldu");
      form.resetFields();
    } else {
      message.error(r?.error || "Kayıt başarısız");
    }
  };

  return (
    <Card title="Pazaryeri Eşleştirme" bordered className="shadow">
      <Form form={form} layout="vertical" onFinish={onFinish}>
        <Form.Item name="product_id" label="Ürün ID" rules={[{ required:true, message:"Zorunlu" }]}>
          <InputNumber style={{width:"100%"}} min={1} placeholder="Örn: 10" />
        </Form.Item>

        <Form.Item name="marketplace_id" label="Pazar Yeri" rules={[{ required:true, message:"Zorunlu" }]}>
          <Select placeholder="Seçiniz">
            {mps.map(m => <Select.Option key={m.id} value={m.id}>{m.name}</Select.Option>)}
          </Select>
        </Form.Item>

        <Form.Item name="external_product_id" label="Dış Ürün ID" rules={[{ required:true, message:"Zorunlu" }]}>
          <Input placeholder="Örn: TY-123456" />
        </Form.Item>

        <Form.Item>
          <Button type="primary" htmlType="submit" loading={loading}>Kaydet</Button>
        </Form.Item>
      </Form>
    </Card>
  );
}
