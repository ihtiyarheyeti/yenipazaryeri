import { Form, Input, Button, Card, message } from "antd";
import { api } from "../api";

export default function OptionForm() {
  const [form] = Form.useForm();

  const submit = async (values:any) => {
    const r = await api("/options", {
      method:"POST",
      body: JSON.stringify({ tenant_id:1, name: values.name })
    });
    if(r?.ok) {
      message.success("Özellik eklendi");
      form.resetFields();
    } else {
      message.error(r?.error || "Kayıt başarısız");
    }
  };

  return (
    <Card title="Yeni Özellik" className="shadow">
      <Form form={form} layout="vertical" onFinish={submit}>
        <Form.Item name="name" label="Ad" rules={[{required:true,message:"Zorunlu"}]}>
          <Input placeholder="Örn: Renk"/>
        </Form.Item>
        <Form.Item>
          <Button type="primary" htmlType="submit">Kaydet</Button>
        </Form.Item>
      </Form>
    </Card>
  );
}
