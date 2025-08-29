import { Card, Button, Form, Input, message } from "antd";
import { api } from "../api";
import { useEffect, useState } from "react";

export default function Security(){
  const [enabled, setEnabled] = useState(false);
  const [secret, setSecret] = useState<string|null>(null);
  const [otpauth, setOtpauth] = useState<string|null>(null);
  const [form] = Form.useForm();
  const userId = 1; // demo; me endpoint ile gerçek id çekebilirsin

  const load = async () => { 
    try {
      const r = await api(`/auth/2fa/status?user_id=${userId}`); 
      if(r?.ok) setEnabled(!!r.twofa_enabled); 
    } catch (error) {
      // Hata durumunda false
    }
  }; 
  
  useEffect(() => { load(); }, []);

  const start = async () => { 
    try {
      const r = await api(`/auth/2fa/setup`, {method:"POST", body:JSON.stringify({user_id:userId})}); 
      if(r?.ok){ 
        setSecret(r.secret); 
        setOtpauth(r.otpauth_uri);
      }
    } catch (error) {
      message.error("Kurulum başlatılamadı");
    }
  }; 
  
  const enable = async () => { 
    try {
      const v = await form.validateFields(); 
      const r = await api(`/auth/2fa/enable`, {method:"POST", body:JSON.stringify({user_id:userId,code:v.code})}); 
      r?.ok ? (message.success("Aktif"), setSecret(null), setOtpauth(null), load()) : message.error("Kod hatalı"); 
    } catch (error) {
      message.error("Etkinleştirme başarısız");
    }
  }; 
  
  const disable = async () => { 
    try {
      const v = await form.validateFields(); 
      const r = await api(`/auth/2fa/disable`, {method:"POST", body:JSON.stringify({user_id:userId,code:v.code})}); 
      r?.ok ? (message.success("Kapalı"), load()) : message.error("Kod hatalı"); 
    } catch (error) {
      message.error("Devre dışı bırakma başarısız");
    }
  };

  return (
    <Card title="Güvenlik (2FA)" style={{maxWidth:600}}>
      <p>Durum: <b>{enabled ? "Açık" : "Kapalı"}</b></p>
      
      {!enabled ? (
        <>
          {!secret ? (
            <Button onClick={start} type="primary">Kurulumu Başlat</Button>
          ) : (
            <>
              <p>Secret: <code>{secret}</code></p>
              <Form form={form} layout="vertical">
                <Form.Item 
                  name="code" 
                  label="Uygulamadaki 6 haneli kod" 
                  rules={[{required:true}]}
                >
                  <Input />
                </Form.Item>
                <Button type="primary" onClick={enable}>Etkinleştir</Button>
              </Form>
            </>
          )}
        </>
      ) : (
        <Form form={form} layout="vertical">
          <Form.Item 
            name="code" 
            label="Kod" 
            rules={[{required:true}]}
          >
            <Input />
          </Form.Item>
          <Button danger onClick={disable}>2FA'yı Kapat</Button>
        </Form>
      )}
    </Card>
  );
}
