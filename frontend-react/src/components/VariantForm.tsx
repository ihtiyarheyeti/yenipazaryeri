import { useEffect, useState } from "react";
import { api } from "../api";
import { Form, Input, InputNumber, Button, Card, Select, message } from "antd";

type OptionValue = { id:number; value:string; option_id:number };

export default function VariantForm({ productId }: { productId:number }) {
  const [form] = Form.useForm();
  const [ov, setOv] = useState<OptionValue[]>([]);

  useEffect(()=>{
    api("/option-values").then(d=>setOv(d.items||[]));
  },[]);

  const onFinish = async (values:any) => {
    const r = await api("/variants", {
      method:"POST",
      body: JSON.stringify({
        product_id: productId,
        sku: values.sku,
        price: values.price,
        stock: values.stock,
        attrs: values.attrs // seçilen option values
      })
    });
    if(r?.ok) {
      message.success("Varyant eklendi");
      form.resetFields();
    } else {
      message.error(r?.error || "Kayıt başarısız");
    }
  };

  // OptionValue listesini grupla (option_id'ye göre)
  const grouped = ov.reduce((acc:any, cur)=>{
    if(!acc[cur.option_id]) acc[cur.option_id]=[];
    acc[cur.option_id].push(cur);
    return acc;
  },{});

  return (
    <Card title="Yeni Varyant" className="shadow">
      <Form form={form} layout="vertical" onFinish={onFinish}>
        <Form.Item name="sku" label="SKU" rules={[{required:true,message:"Zorunlu"}]}>
          <Input placeholder="Örn: SKU-RED-M"/>
        </Form.Item>
        <Form.Item name="price" label="Fiyat" rules={[{required:true}]}>
          <InputNumber style={{width:"100%"}} min={0} step={0.01}/>
        </Form.Item>
        <Form.Item name="stock" label="Stok" rules={[{required:true}]}>
          <InputNumber style={{width:"100%"}} min={0}/>
        </Form.Item>

        {/* OptionValue seçimleri */}
        <Form.Item name="attrs" label="Özellikler">
          <div className="grid gap-3">
            {Object.entries(grouped).map(([optId, values]:any)=>(
              <Form.Item key={optId} name={["attrs",optId]} label={`Option ${optId}`}>
                <Select placeholder="Seçiniz">
                  {values.map((v:OptionValue)=>
                    <Select.Option key={v.id} value={v.value}>
                      {v.value} (#{v.id})
                    </Select.Option>
                  )}
                </Select>
              </Form.Item>
            ))}
          </div>
  </Form.Item>

        <Form.Item>
          <Button type="primary" htmlType="submit">Kaydet</Button>
        </Form.Item>
      </Form>
    </Card>
  );
}
