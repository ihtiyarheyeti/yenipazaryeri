import { Card, Form, Input, Button, message } from "antd";
import { api } from "../api";

export default function UserInvitePage() {
  const [form] = Form.useForm();
  
  const submit = async () => {
    const v = await form.validateFields();
    const r = await api("/users/invite", {
      method: "POST", 
      body: JSON.stringify({ tenant_id: 1, email: v.email })
    });
    
    if (r?.ok) { 
      message.success("Davet gönderildi"); 
      form.resetFields(); 
    } else {
      message.error(r?.error || "Hata");
    }
  };
  
  return (
    <Card title="Kullanıcı Davet">
      <Form form={form} layout="vertical" onFinish={submit}>
        <Form.Item 
          name="email" 
          label="E-posta" 
          rules={[{ required: true, type: "email" }]}
        >
          <Input />
        </Form.Item>
        <Form.Item>
          <Button type="primary" htmlType="submit">
            Davet Gönder
          </Button>
        </Form.Item>
      </Form>
    </Card>
  );
}

