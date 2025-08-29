import React, { useState } from 'react';
import { Card, Form, Input, Button, message, Space, Typography } from 'antd';
import { MailOutlined, SendOutlined } from '@ant-design/icons';

const { Title, Text } = Typography;

interface SmtpTestForm {
  to: string;
  subject: string;
  message: string;
}

const SmtpTestPage: React.FC = () => {
  const [form] = Form.useForm();
  const [loading, setLoading] = useState(false);

  const onFinish = async (values: SmtpTestForm) => {
    setLoading(true);
    try {
      const response = await fetch('/api/smtp/test', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify(values),
      });

      if (response.ok) {
        message.success('SMTP test e-postası başarıyla gönderildi!');
        form.resetFields();
      } else {
        const error = await response.json();
        message.error(`SMTP test hatası: ${error.message || 'Bilinmeyen hata'}`);
      }
    } catch (error) {
      message.error('SMTP test sırasında bir hata oluştu!');
      console.error('SMTP test error:', error);
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="max-w-4xl mx-auto">
      <Title level={2} className="mb-6">
        <MailOutlined className="mr-2" />
        SMTP Test Sayfası
      </Title>

      <Card title="SMTP Ayarlarını Test Et" className="mb-6">
        <Text type="secondary">
          Bu sayfa ile SMTP ayarlarınızı test edebilir ve e-posta gönderimini doğrulayabilirsiniz.
        </Text>
      </Card>

      <Card title="Test E-postası Gönder">
        <Form
          form={form}
          layout="vertical"
          onFinish={onFinish}
          initialValues={{
            subject: 'SMTP Test E-postası',
            message: 'Bu bir test e-postasıdır. SMTP ayarlarınız başarıyla çalışıyor!'
          }}
        >
          <Form.Item
            label="Alıcı E-posta"
            name="to"
            rules={[
              { required: true, message: 'Lütfen alıcı e-posta adresini girin!' },
              { type: 'email', message: 'Geçerli bir e-posta adresi girin!' }
            ]}
          >
            <Input 
              placeholder="ornek@email.com" 
              prefix={<MailOutlined />}
            />
          </Form.Item>

          <Form.Item
            label="Konu"
            name="subject"
            rules={[{ required: true, message: 'Lütfen konu girin!' }]}
          >
            <Input placeholder="E-posta konusu" />
          </Form.Item>

          <Form.Item
            label="Mesaj"
            name="message"
            rules={[{ required: true, message: 'Lütfen mesaj girin!' }]}
          >
            <Input.TextArea 
              rows={4} 
              placeholder="E-posta mesajı"
            />
          </Form.Item>

          <Form.Item>
            <Space>
              <Button 
                type="primary" 
                htmlType="submit" 
                loading={loading}
                icon={<SendOutlined />}
              >
                Test E-postası Gönder
              </Button>
              <Button onClick={() => form.resetFields()}>
                Formu Temizle
              </Button>
            </Space>
          </Form.Item>
        </Form>
      </Card>

      <Card title="SMTP Test Sonuçları" className="mt-6">
        <Text type="secondary">
          Test e-postası gönderildikten sonra sonuçlar burada görüntülenecektir.
        </Text>
      </Card>
    </div>
  );
};

export default SmtpTestPage;
