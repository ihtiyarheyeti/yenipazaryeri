import { Card, Form, Input, Button, message } from "antd";
import { api, setToken } from "../api";
import { useNavigate, Link } from "react-router-dom";

export default function Login(){
  const nav = useNavigate();
  const [form] = Form.useForm();
  
  const submit = async () => {
    try {
      const v = await form.validateFields();
      const r = await api("/auth/login", {method:"POST", body:JSON.stringify(v)});
      
      if(r?.ok){ 
        setToken(r.token); 
        localStorage.setItem("refresh_token", r.refresh_token);
        message.success("Giriş başarılı"); 
        nav("/"); 
      }
      else if(r?.twofa_required){ 
        message.info("2FA kodu gerekli"); 
      }
      else { 
        message.error(r?.error||"Giriş başarısız"); 
      }
    } catch (error) {
      message.error("Giriş yapılamadı");
    }
  };
  
  return(
    <div style={{maxWidth:360,margin:"80px auto"}}>
      <Card title="Giriş">
        <Form layout="vertical" form={form} onFinish={submit}>
          <Form.Item 
            name="email" 
            label="E-posta" 
            rules={[{required:true, type:"email"}]}
          >
            <Input />
          </Form.Item>
          
          <Form.Item 
            name="password" 
            label="Şifre" 
            rules={[{required:true}]}
          >
            <Input.Password />
          </Form.Item>
          
          <Form.Item>
            <Button type="primary" htmlType="submit" block>
              Giriş Yap
            </Button>
          </Form.Item>
          
          <div style={{textAlign:"right"}}>
            <Link to="/forgot-password">Şifremi unuttum</Link>
          </div>
        </Form>
      </Card>
    </div>
  );
}
