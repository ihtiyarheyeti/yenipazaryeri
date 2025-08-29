import { Card, Form, Input, Button, message } from "antd";
import { api } from "../api";

export default function Invite(){
  const [form] = Form.useForm();
  
  const submit = async () => { 
    try {
      const v = await form.validateFields(); 
      const r = await api(`/users/invite`, {method:"POST", body:JSON.stringify({tenant_id:1,email:v.email})}); 
      r?.ok ? message.success("Davet gönderildi") : message.error(r?.error||"Hata"); 
    } catch (error) {
      message.error("Davet gönderilemedi");
    }
  }; 
  
  return (
    <Card title="Kullanıcı Davet" style={{maxWidth:420}}>
      <Form layout="vertical" form={form} onFinish={submit}>
        <Form.Item 
          name="email" 
          label="E-posta" 
          rules={[{required:true, type:"email"}]}
        >
          <Input />
        </Form.Item>
        <Form.Item>
          <Button type="primary" htmlType="submit">Gönder</Button>
        </Form.Item>
      </Form>
    </Card>
  );
}
