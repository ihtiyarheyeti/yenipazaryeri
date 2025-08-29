import { Modal, Form, Input, message } from "antd";
import { api } from "../api";
import { useEffect } from "react";

type Props={ open:boolean; onClose:()=>void; product:any|null };

export default function ProductEditModal({open,onClose,product}:Props){
  const [form]=Form.useForm();
  useEffect(()=>{
    if(product){
      form.setFieldsValue({
        name:product.name,
        brand:product.brand,
        category_path:(Array.isArray(product.category_path)?product.category_path.join(">"):"")
      });
    } else form.resetFields();
  },[product]);
  
  const submit=async()=>{
    const v=await form.validateFields();
    const path=v.category_path? v.category_path.split(">"):[];
    const r=await api(`/products/${product.id}`,{
      method:"PUT",
      body:JSON.stringify({ name:v.name, brand:v.brand, category_path:path })
    });
    if(r?.ok){ message.success("Ürün güncellendi"); onClose(); }
    else message.error(r?.error||"Güncelleme hatası");
  };

  return(
    <Modal open={open} onOk={submit} onCancel={onClose} title={`Ürün Düzenle #${product?.id}`} okText="Kaydet">
      <Form form={form} layout="vertical">
        <Form.Item name="name" label="Ad" rules={[{required:true}]}>
          <Input/>
        </Form.Item>
        <Form.Item name="brand" label="Marka">
          <Input/>
        </Form.Item>
        <Form.Item name="category_path" label="Kategori Yolu" tooltip="A>B>C">
          <Input/>
        </Form.Item>
      </Form>
    </Modal>
  );
}
