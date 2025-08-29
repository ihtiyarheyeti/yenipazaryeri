import { Form, Input, Button, Select, Card, message } from "antd";
import { useEffect, useState } from "react";
import { api } from "../api";

type Option = { id:number; name:string };

export default function OptionValueForm() {
  const [form] = Form.useForm();
  const [options,setOptions] = useState<Option[]>([]);

  useEffect(()=>{
    api("/options?tenant_id=1").then(d=>setOptions(d.items||[]));
  },[]);

  const submit = async (values:any) => {
    const r = await api("/option-values", {
      method:"POST",
      body: JSON.stringify({ option_id:values.option_id, value:values.value })
    });
    if(r?.ok) {
      message.success("Değer eklendi");
      form.resetFields();
    } else {
      message.error(r?.error || "Kayıt başarısız");
    }
  };

  return (
    <Card title="Yeni Özellik Değeri" className="shadow">
      <Form form={form} layout="vertical" onFinish={submit}>
        <Form.Item name="option_id" label="Özellik" rules={[{required:true,message:"Zorunlu"}]}>
          <Select placeholder="Seçiniz">
            {options.map(o=><Select.Option key={o.id} value={o.id}>{o.name}</Select.Option>)}
          </Select>
        </Form.Item>
        <Form.Item name="value" label="Değer" rules={[{required:true,message:"Zorunlu"}]}>
          <Input placeholder="Örn: Kırmızı"/>
        </Form.Item>
        <Form.Item>
          <Button type="primary" htmlType="submit">Kaydet</Button>
        </Form.Item>
      </Form>
    </Card>
  );
}
