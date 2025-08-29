import { Card, Form, Input, Button, message } from "antd";
import { useSearchParams } from "react-router-dom";
import { api } from "../api";

export default function InviteAccept(){
  const [sp] = useSearchParams(); 
  const token = sp.get('token') || '';
  const [form] = Form.useForm();
  
  const submit = async () => { 
    const v = await form.validateFields(); 
    const r = await api(`/invites/accept`, {
      method:'POST',
      body:JSON.stringify({token, name:v.name, password:v.password})
    }); 
    r?.ok? (message.success('Hesap oluşturuldu'), window.location.href='/login') : message.error(r?.error||'Hata'); 
  };
  
  return <Card title="Davet Kabul" style={{maxWidth:420, margin:'40px auto'}}>
    <Form layout="vertical" form={form}>
      <Form.Item name="name" label="Ad Soyad" rules={[{required:true}]}>
        <Input />
      </Form.Item>
      <Form.Item name="password" label="Şifre" rules={[{required:true,min:6}]}>
        <Input.Password />
      </Form.Item>
      <Button type="primary" onClick={submit} block>Hesabı Oluştur</Button>
    </Form>
  </Card>;
}
