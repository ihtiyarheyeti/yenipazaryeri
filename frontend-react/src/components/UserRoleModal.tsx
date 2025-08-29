import { Modal, Form, Select, message } from "antd";
import { useEffect, useState } from "react";
import { api } from "../api";

type Props = {
  open: boolean;
  onClose: () => void;
  user: any | null; // { id, name, roles: string[] }
};
export default function UserRoleModal({ open, onClose, user }: Props) {
  const [form] = Form.useForm();
  const [roleOptions, setRoleOptions] = useState<any[]>([]);
  const [loading, setLoading] = useState(false);

  useEffect(() => {
    (async()=>{
      const r = await api("/roles");
      if (r?.ok) {
        setRoleOptions((r.items||[]).map((x:any)=>({label:x.name, value:x.id})));
      }
    })();
  }, []);

  useEffect(() => {
    if (user) {
      // Kullanıcının mevcut rol isimleri user.roles (string[]) olarak geliyor.
      // Select value role_id istediği için mevcutları boş bırakıyoruz; kullanıcı seçsin.
      // İstersen backend'e user_roles by user endpoint ekleyip role_id'leri çekebilirsin.
      form.setFieldsValue({ roles: [] });
    } else {
      form.resetFields();
    }
  }, [user]);

  const submit = async () => {
    const values = await form.validateFields();
    const selectedRoleIds: number[] = values.roles || [];
    if (!user) return;

    setLoading(true);

    try {
      // Yeni setRoles endpoint'i kullanarak tek seferde güncelle
      const r = await api("/user-roles/set", {
        method: "PUT",
        body: JSON.stringify({ user_id: user.id, roles: selectedRoleIds })
      });

      if (r?.ok) {
        message.success("Roller güncellendi");
        onClose();
      } else {
        message.error(r?.error || "Rol güncelleme hatası");
      }
    } catch (e:any) {
      message.error("Rol güncelleme hatası");
    } finally {
      setLoading(false);
    }
  };

  return (
    <Modal
      open={open}
      onOk={submit}
      okButtonProps={{loading}}
      onCancel={onClose}
      title={user ? `Rol Ata — ${user.name}` : "Rol Ata"}
      okText="Kaydet"
    >
      <Form form={form} layout="vertical">
        <Form.Item name="roles" label="Roller" rules={[{ required: true, message: "En az bir rol seçin" }]}>
          <Select
            mode="multiple"
            placeholder="Rol seçin"
            options={roleOptions}
            optionFilterProp="label"
          />
        </Form.Item>
      </Form>
      <p className="text-sm opacity-70 mt-2">
        Not: Roller tek seferde güncelleniyor.
      </p>
    </Modal>
  );
}
