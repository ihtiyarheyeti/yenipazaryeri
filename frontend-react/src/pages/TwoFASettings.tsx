import { Card, Button, Form, Input, message } from "antd";
import { QRCode } from "antd";
import { useEffect, useState } from "react";
import { api } from "../api";
import { useAuth } from "../auth/AuthContext";

export default function TwoFASettings() {
  const { user } = useAuth();
  const [status, setStatus] = useState(false);
  const [secret, setSecret] = useState<string | null>(null);
  const [otpauth, setOtpauth] = useState<string | null>(null);
  const [form] = Form.useForm();
  const uid = user?.id;

  const load = async () => {
    if (!uid) return;
    const r = await api(`/auth/2fa/status?user_id=${uid}`);
    if (r?.ok) setStatus(!!r.twofa_enabled);
  };
  
  useEffect(() => { load(); }, [uid]);

  const startSetup = async () => {
    const r = await api(`/auth/2fa/setup`, {
      method: "POST", 
      body: JSON.stringify({ user_id: uid })
    });
    
    if (r?.ok) { 
      setSecret(r.secret); 
      setOtpauth(r.otpauth_uri); 
    } else {
      message.error(r?.error || "Hata");
    }
  };

  const enable = async () => {
    const v = await form.validateFields();
    const r = await api(`/auth/2fa/enable`, {
      method: "POST", 
      body: JSON.stringify({ user_id: uid, code: v.code })
    });
    
    if (r?.ok) { 
      message.success("2FA etkinleştirildi"); 
      setSecret(null); 
      setOtpauth(null); 
      load(); 
    } else {
      message.error(r?.error || "Hatalı kod");
    }
  };

  const disable = async () => {
    const v = await form.validateFields();
    const r = await api(`/auth/2fa/disable`, {
      method: "POST", 
      body: JSON.stringify({ user_id: uid, code: v.code })
    });
    
    if (r?.ok) { 
      message.success("2FA kapatıldı"); 
      load(); 
    } else {
      message.error(r?.error || "Hatalı kod");
    }
  };

  return (
    <div style={{maxWidth: 520, margin: "40px auto"}}>
      <Card title="İki Aşamalı Doğrulama (2FA)">
        <p>Durum: <b>{status ? "Açık" : "Kapalı"}</b></p>
        
        {!status ? (
          <>
            {!secret ? (
              <Button type="primary" onClick={startSetup}>
                Kurulumu Başlat
              </Button>
            ) : (
              <>
                <p>Authenticator uygulamasında aşağıdaki QR'ı tara veya secret'ı gir:</p>
                {otpauth && <QRCode value={otpauth} />}
                <p>Secret: <code>{secret}</code></p>
                <Form form={form} layout="vertical" className="mt-3">
                  <Form.Item 
                    name="code" 
                    label="Uygulamadaki 6 haneli kod" 
                    rules={[{ required: true }]}
                  >
                    <Input />
                  </Form.Item>
                  <Button type="primary" onClick={enable}>
                    Etkinleştir
                  </Button>
                </Form>
              </>
            )}
          </>
        ) : (
          <>
            <Form form={form} layout="vertical">
              <Form.Item 
                name="code" 
                label="Uygulamadaki 6 haneli kod" 
                rules={[{ required: true }]}
              >
                <Input />
              </Form.Item>
              <Button danger onClick={disable}>
                2FA'yı Kapat
              </Button>
            </Form>
          </>
        )}
      </Card>
    </div>
  );
}

