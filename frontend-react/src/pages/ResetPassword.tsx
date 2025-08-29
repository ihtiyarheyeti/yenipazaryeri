import { Card, Form, Input, Button, message } from "antd";
import { useSearchParams } from "react-router-dom";
import { api } from "../api";

export default function ResetPassword(){
  const [sp] = useSearchParams(); 
  const token = sp.get('token') || '';
  const [form] = Form.useForm();
  
  const submit = async () => { 
    const v = await form.validateFields(); 
    const r = await api('/password/reset', {
      method:'POST', 
      body: JSON.stringify({token, password:v.password})
    }); 
    r?.ok? (message.success('Şifre güncellendi'), window.location.href='/login') : message.error(r?.error||'Hata'); 
  };
  
  return <Card title="Yeni Şifre" style={{maxWidth:420, margin:'40px auto'}}>
    <Form layout="vertical" form={form}>
      <Form.Item name="password" label="Yeni Şifre" rules={[{required:true,min:6}]}>
        <Input.Password />
      </Form.Item>
      <Button type="primary" onClick={submit} block>Kaydet</Button>
    </Form>
  </Card>;
}

