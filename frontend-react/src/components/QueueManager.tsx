import { useState } from "react";
import { api } from "../api";
import { Card, Button, InputNumber, message, Space, Tag } from "antd";

export default function QueueManager() {
  const [limit, setLimit] = useState(10);
  const [processing, setProcessing] = useState(false);
  const [lastResult, setLastResult] = useState<any>(null);

  const processQueue = async () => {
    setProcessing(true);
    try {
      const res = await api(`/queue/process?limit=${limit}`, { method: "POST" });
      if (res?.ok) {
        setLastResult(res);
        message.success(`Queue processed: ${res.summary.picked} jobs picked`);
      } else {
        message.error(res?.error || "Queue processing failed");
      }
    } catch (error) {
      message.error("Queue processing error");
    } finally {
      setProcessing(false);
    }
  };

  return (
    <Card title="Queue Manager" className="shadow">
      <Space direction="vertical" style={{ width: '100%' }}>
        <div className="flex items-center gap-2">
          <span>Process limit:</span>
          <InputNumber 
            min={1} 
            max={50} 
            value={limit} 
            onChange={(v) => setLimit(v || 10)}
            style={{ width: 80 }}
          />
          <Button 
            type="primary" 
            onClick={processQueue} 
            loading={processing}
            disabled={processing}
          >
            Process Queue
          </Button>
        </div>

        {lastResult && (
          <div className="mt-4 p-3 bg-gray-50 rounded">
            <h4>Last Result:</h4>
            <div className="flex gap-4 text-sm">
              <span>Picked: <Tag color="blue">{lastResult.summary.picked}</Tag></span>
              <span>Done: <Tag color="green">{lastResult.summary.done}</Tag></span>
              <span>Error: <Tag color="red">{lastResult.summary.error}</Tag></span>
            </div>
            {lastResult.results && lastResult.results.length > 0 && (
              <div className="mt-2">
                <h5>Job Results:</h5>
                <div className="text-xs">
                  {lastResult.results.map((r: any, i: number) => (
                    <div key={i} className="flex gap-2">
                      <span>Job #{r.id}:</span>
                      <Tag color={r.ok ? 'green' : 'red'}>
                        {r.ok ? 'Success' : 'Failed'}
                      </Tag>
                      {r.error && <span className="text-red-500">({r.error})</span>}
                    </div>
                  ))}
                </div>
              </div>
            )}
          </div>
        )}
      </Space>
    </Card>
  );
}
