import { Card, Form, Input, Button, message } from "antd";
import { api } from "../api";

export default function ForgotPassword(){
  const [form] = Form.useForm();
  
  const submit = async () => { 
    const v = await form.validateFields(); 
    const r = await api('/password/forgot', {
      method:'POST', 
      body: JSON.stringify({email:v.email})
    }); 
    r?.ok? message.success('Eğer kayıtlı ise e-posta gönderildi') : message.error('Hata'); 
  };
  
  return <Card title="Şifre Sıfırlama İsteği" style={{maxWidth:420, margin:'40px auto'}}>
    <Form layout="vertical" form={form}>
      <Form.Item name="email" label="E-posta" rules={[{type:'email',required:true}]}>
        <Input />
      </Form.Item>
      <Button type="primary" block onClick={submit}>E-posta Gönder</Button>
    </Form>
  </Card>;
}

