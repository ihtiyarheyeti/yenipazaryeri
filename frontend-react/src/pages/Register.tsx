import { useState } from "react";
import { api } from "../api";
import { useNavigate, Link } from "react-router-dom";
import { Form, Input, Button, Card, message, InputNumber } from "antd";
import { useAuth } from "../auth/AuthContext";

export default function Register() {
  const [form] = Form.useForm();
  const nav = useNavigate();
  const { login } = useAuth();
  const [loading, setLoading] = useState(false);

  const submit = async (values:any) => {
    setLoading(true);
    const res = await api("/auth/register", {
      method: "POST",
      body: JSON.stringify({
        tenant_id: Number(values.tenant_id),
        name: values.name,
        email: values.email,
        password: values.password,
      }),
    });
    setLoading(false);
    if (res?.ok) {
      message.success("Kayıt başarılı, giriş yapıldı");
      login(res.token, res.user);
      nav("/");
    } else {
      message.error(res?.error || "Kayıt başarısız");
    }
  };

  return (
    <div style={{maxWidth: 420, margin: "80px auto"}}>
      <Card title="Kayıt Ol">
        <Form form={form} layout="vertical" onFinish={submit}>
          <Form.Item name="tenant_id" label="Tenant ID" rules={[{required:true, message:"Zorunlu"}]}>
            <InputNumber min={1} style={{width:"100%"}} placeholder="Örn: 1"/>
          </Form.Item>
          <Form.Item name="name" label="Ad Soyad" rules={[{required:true, message:"Zorunlu"}]}>
            <Input />
          </Form.Item>
          <Form.Item name="email" label="E-posta" rules={[{required:true, type:"email", message:"Geçerli e-posta girin"}]}>
            <Input />
          </Form.Item>
          <Form.Item name="password" label="Şifre" rules={[{required:true, min:6, message:"En az 6 karakter"}]}>
            <Input.Password />
          </Form.Item>
          <Form.Item>
            <Button type="primary" htmlType="submit" loading={loading}>Kayıt Ol</Button>
            <Link to="/login" style={{marginLeft:12}}>Zaten hesabın var mı?</Link>
            <Link to="/forgot" style={{marginLeft:12}}>Şifremi unuttum?</Link>
          </Form.Item>
        </Form>
      </Card>
    </div>
  );
}
