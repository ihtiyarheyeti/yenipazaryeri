import { useEffect, useState } from "react";
import { api } from "../api";
import { Card, Table, Pagination } from "antd";

export default function AuditLogPage() {
  const [rows, setRows] = useState<any[]>([]);
  const [page, setPage] = useState(1);
  const [total, setTotal] = useState(0);
  const pageSize = 20;

  const load = async () => {
    const d = await api(`/audit-logs?page=${page}&pageSize=${pageSize}`);
    if (d?.ok) { 
      setRows(d.items || []); 
      setTotal(9999); 
    }
  };
  
  useEffect(() => { load(); }, [page]);

  const columns = [
    { title: "ID", dataIndex: "id", width: 60 },
    { title: "User", dataIndex: "user_id" },
    { title: "Action", dataIndex: "action" },
    { title: "Method", dataIndex: "method" },
    { title: "Endpoint", dataIndex: "endpoint" },
    { title: "IP", dataIndex: "ip" },
    { title: "Tarih", dataIndex: "created_at" }
  ];
  
  return (
    <Card title="Audit Log">
      <Table 
        rowKey="id" 
        dataSource={rows} 
        columns={columns as any} 
        pagination={false} 
        size="small"
      />
      <div className="mt-3 flex justify-end">
        <Pagination 
          current={page} 
          pageSize={pageSize} 
          total={total} 
          onChange={setPage}
        />
      </div>
    </Card>
  );
}

