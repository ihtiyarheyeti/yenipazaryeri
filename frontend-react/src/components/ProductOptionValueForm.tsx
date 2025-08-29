import { useEffect, useState } from "react";
import { api } from "../api";
import { Form, Select, Button, Card, message, InputNumber } from "antd";

type OptionValue = { id:number; value:string; option_id:number };

export default function ProductOptionValueForm() {
  const [form] = Form.useForm();
  const [ov,setOv] = useState<OptionValue[]>([]);

  useEffect(()=>{
    api("/option-values").then(d=>setOv(d.items||[]));
  },[]);

  const submit = async (v:any) => {
    const r = await api("/product-option-values", {
      method:"POST",
      body: JSON.stringify({ product_id:v.product_id, option_value_id:v.option_value_id })
    });
    if(r?.ok) {
      message.success("Bağlantı eklendi");
      form.resetFields();
    } else {
      message.error(r?.error || "İşlem başarısız");
    }
  };

  return (
    <Card title="Ürüne Özellik Bağla" className="shadow">
      <Form form={form} layout="vertical" onFinish={submit}>
        <Form.Item name="product_id" label="Product ID" rules={[{required:true}]}>
          <InputNumber min={1} style={{width:"100%"}} placeholder="Ürün ID"/>
        </Form.Item>
        <Form.Item name="option_value_id" label="Özellik Değeri" rules={[{required:true}]}>
          <Select showSearch placeholder="Seçiniz" optionFilterProp="children">
            {ov.map(o=><Select.Option key={o.id} value={o.id}>#{o.id} — {o.value}</Select.Option>)}
          </Select>
        </Form.Item>
        <Form.Item>
          <Button type="primary" htmlType="submit">Bağla</Button>
        </Form.Item>
      </Form>
    </Card>
  );
}
