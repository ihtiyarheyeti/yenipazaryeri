import { Card, Form, Input, Button, message } from "antd";
import { api } from "../api";

export default function SmtpTest(){
  const [form] = Form.useForm();
  
  const submit = async () => { 
    try {
      const v = await form.validateFields(); 
      const r = await api(`/mail/test?to=${encodeURIComponent(v.to)}`); 
      r?.ok ? message.success("Gönderildi (veya kuyruğa alındı)") : message.error("Hata"); 
    } catch (error) {
      message.error("Test başarısız");
    }
  }; 
  
  return (
    <Card title="SMTP Test" style={{maxWidth:480}}>
      <Form layout="vertical" form={form} onFinish={submit}>
        <Form.Item 
          name="to" 
          label="Alıcı" 
          rules={[{required:true, type:"email"}]}
        >
          <Input />
        </Form.Item>
        <Form.Item>
          <Button type="primary" htmlType="submit">Test Et</Button>
        </Form.Item>
      </Form>
    </Card>
  );
}
