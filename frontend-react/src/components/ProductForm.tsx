import { Form, Input, Button } from "antd";
import { api } from "../api";

export default function ProductForm() {
  const [form] = Form.useForm();
  const submit = async (values:any) => {
    await api("/products", {
      method: "POST",
      body: JSON.stringify({ tenant_id:1, ...values }),
    });
    form.resetFields();
    alert("Ürün eklendi");
  };
  return (
    <div className="bg-white p-4 shadow rounded">
      <h2 className="text-lg font-semibold mb-2">Yeni Ürün</h2>
      <Form form={form} onFinish={submit} layout="vertical">
        <Form.Item name="name" label="Ürün Adı" rules={[{required:true,message:"Zorunlu"}]}>
          <Input/>
        </Form.Item>
        <Form.Item name="brand" label="Marka">
          <Input/>
        </Form.Item>
        <Form.Item>
          <Button type="primary" htmlType="submit">Kaydet</Button>
        </Form.Item>
      </Form>
    </div>
  );
}
