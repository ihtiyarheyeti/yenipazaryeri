import { Modal, Form, Input, InputNumber, message } from "antd";
import { api } from "../api";
import { useEffect } from "react";

type Props = {
  open: boolean;
  onClose: () => void;
  variant: any | null; // {id, sku, price, stock, attrs}
};
export default function VariantEditModal({ open, onClose, variant }: Props) {
  const [form] = Form.useForm();

  useEffect(() => {
    if (variant) {
      form.setFieldsValue({
        sku: variant.sku,
        price: variant.price,
        stock: variant.stock,
        attrs: typeof variant.attrs === "string" ? variant.attrs : JSON.stringify(variant.attrs || {})
      });
    } else {
      form.resetFields();
    }
  }, [variant]);

  const submit = async () => {
    const values = await form.validateFields();
    let attrsObj: any = {};
    try {
      attrsObj = values.attrs ? JSON.parse(values.attrs) : {};
    } catch {
      message.error("attrs geçerli bir JSON olmalı. Örn: {\"Renk\":\"Kırmızı\",\"Beden\":\"M\"}");
      return;
    }
    const r = await api(`/variants/${variant.id}`, {
      method: "PUT",
      body: JSON.stringify({
        sku: values.sku,
        price: Number(values.price),
        stock: Number(values.stock),
        attrs: attrsObj
      })
    });
    if (r?.ok) {
      message.success("Varyant güncellendi");
      onClose();
    } else {
      message.error(r?.error || "Güncelleme başarısız");
    }
  };

  return (
    <Modal open={open} onOk={submit} onCancel={onClose} title={`Varyant Düzenle #${variant?.id}`} okText="Kaydet">
      <Form form={form} layout="vertical">
        <Form.Item name="sku" label="SKU" rules={[{ required: true, message: "Zorunlu" }]}>
          <Input />
        </Form.Item>
        <Form.Item name="price" label="Fiyat" rules={[{ required: true }]}>
          <InputNumber min={0} step={0.01} style={{ width: "100%" }} />
        </Form.Item>
        <Form.Item name="stock" label="Stok" rules={[{ required: true }]}>
          <InputNumber min={0} style={{ width: "100%" }} />
        </Form.Item>
        <Form.Item
          name="attrs"
          label="Özellikler (JSON)"
          tooltip='Örn: {"Renk":"Kırmızı","Beden":"M"}'
        >
          <Input.TextArea rows={4} />
        </Form.Item>
      </Form>
    </Modal>
  );
}
