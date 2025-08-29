import React, { useState } from 'react';
import { Card, Button, Upload, message, Table, Tag, Space, Progress, Alert } from 'antd';
import { UploadOutlined, DownloadOutlined, SyncOutlined, FileTextOutlined } from '@ant-design/icons';
import { useTranslation } from 'react-i18next';

const { Dragger } = Upload;

interface ImportStatus {
  id: string;
  type: 'trendyol' | 'woocommerce' | 'csv';
  status: 'pending' | 'processing' | 'completed' | 'error';
  progress: number;
  message: string;
  created_at: string;
}

export default function ProductImport() {
  const { t } = useTranslation();
  const [importStatus, setImportStatus] = useState<ImportStatus[]>([]);
  const [isImporting, setIsImporting] = useState(false);

  // Trendyol'dan ürün çek
  const pullFromTrendyol = async () => {
    try {
      setIsImporting(true);
      message.info('Trendyol\'dan ürünler çekiliyor...');
      
      const response = await fetch('http://localhost:8000/import/trendyol/pull', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({})
      });
      
      const result = await response.json();
      
      if (result.ok) {
        message.success(`Trendyol'dan ${result.imported} ürün import edildi, ${result.updated} ürün güncellendi`);
        
        // Status'a ekle
        setImportStatus(prev => [{
          id: Date.now().toString(),
          type: 'trendyol',
          status: 'completed',
          progress: 100,
          message: `${result.imported} import, ${result.updated} güncelleme`,
          created_at: new Date().toISOString()
        }, ...prev]);
      } else {
        message.error('Hata: ' + result.error);
        setImportStatus(prev => [{
          id: Date.now().toString(),
          type: 'trendyol',
          status: 'error',
          progress: 0,
          message: result.error,
          created_at: new Date().toISOString()
        }, ...prev]);
      }
    } catch (error) {
      message.error('Bağlantı hatası: ' + error);
    } finally {
      setIsImporting(false);
    }
  };

  // WooCommerce'dan ürün çek
  const pullFromWooCommerce = async () => {
    try {
      setIsImporting(true);
      message.info('WooCommerce\'dan ürünler çekiliyor...');
      
      const response = await fetch('http://localhost:8000/import/woocommerce/pull', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({})
      });
      
      const result = await response.json();
      
      if (result.ok) {
        message.success(`WooCommerce'dan ${result.imported} ürün import edildi, ${result.updated} ürün güncellendi`);
        
        setImportStatus(prev => [{
          id: Date.now().toString(),
          type: 'woocommerce',
          status: 'completed',
          progress: 100,
          message: `${result.imported} import, ${result.updated} güncelleme`,
          created_at: new Date().toISOString()
        }, ...prev]);
      } else {
        message.error('Hata: ' + result.error);
        setImportStatus(prev => [{
          id: Date.now().toString(),
          type: 'woocommerce',
          status: 'error',
          progress: 0,
          message: result.error,
          created_at: new Date().toISOString()
        }, ...prev]);
      }
    } catch (error) {
      message.error('Bağlantı hatası: ' + error);
    } finally {
      setIsImporting(false);
    }
  };

  // CSV import
  const handleCsvUpload = async (file: File) => {
    try {
      setIsImporting(true);
      message.info('CSV dosyası işleniyor...');
      
      const formData = new FormData();
      formData.append('csv_file', file);
      
      const response = await fetch('http://localhost:8000/import/csv', {
        method: 'POST',
        body: formData
      });
      
      const result = await response.json();
      
      if (result.ok) {
        message.success(`${result.imported} ürün CSV'den import edildi`);
        
        setImportStatus(prev => [{
          id: Date.now().toString(),
          type: 'csv',
          status: 'completed',
          progress: 100,
          message: `${result.imported} ürün import edildi`,
          created_at: new Date().toISOString()
        }, ...prev]);
      } else {
        message.error('Hata: ' + result.error);
        setImportStatus(prev => [{
          id: Date.now().toString(),
          type: 'csv',
          status: 'error',
          progress: 0,
          message: result.error,
          created_at: new Date().toISOString()
        }, ...prev]);
      }
    } catch (error) {
      message.error('Bağlantı hatası: ' + error);
    } finally {
      setIsImporting(false);
    }
    
    return false; // Upload'ı otomatik yapma
  };

  // CSV template indir
  const downloadCsvTemplate = () => {
    const headers = ['sku', 'name', 'description', 'price', 'stock', 'brand', 'category'];
    const csvContent = [headers.join(','), 'TEST001,Test Ürün 1,Test açıklama,99.99,50,Test Marka,Elektronik'].join('\n');
    
    const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
    const link = document.createElement('a');
    link.href = URL.createObjectURL(blob);
    link.download = 'urun_import_template.csv';
    link.click();
  };

  const columns = [
    {
      title: 'Tip',
      dataIndex: 'type',
      key: 'type',
      render: (type: string) => {
        const colors = {
          trendyol: 'blue',
          woocommerce: 'green',
          csv: 'orange'
        };
        return <Tag color={colors[type as keyof typeof colors]}>{type.toUpperCase()}</Tag>;
      }
    },
    {
      title: 'Durum',
      dataIndex: 'status',
      key: 'status',
      render: (status: string) => {
        const colors = {
          pending: 'default',
          processing: 'processing',
          completed: 'success',
          error: 'error'
        };
        return <Tag color={colors[status as keyof typeof colors]}>{status.toUpperCase()}</Tag>;
      }
    },
    {
      title: 'İlerleme',
      dataIndex: 'progress',
      key: 'progress',
      render: (progress: number) => <Progress percent={progress} size="small" />,
    },
    {
      title: 'Mesaj',
      dataIndex: 'message',
      key: 'message',
    },
    {
      title: 'Tarih',
      dataIndex: 'created_at',
      key: 'created_at',
      render: (date: string) => new Date(date).toLocaleString('tr-TR'),
    }
  ];

  return (
    <div style={{ padding: '24px' }}>
      <h1>Ürün Import</h1>
      
      <div style={{ marginBottom: '24px' }}>
        <Alert
          message="Import İşlemleri"
          description="Marketplace'lerden ürünleri çekebilir veya CSV dosyasından toplu import yapabilirsiniz."
          type="info"
          showIcon
          style={{ marginBottom: '16px' }}
        />
      </div>

      <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: '16px', marginBottom: '24px' }}>
        {/* Trendyol Import */}
        <Card title="Trendyol Import" extra={<SyncOutlined />}>
          <p>Trendyol'dan tüm ürünleri çek ve local veritabanına kaydet</p>
          <Button 
            type="primary" 
            onClick={pullFromTrendyol}
            loading={isImporting}
            block
          >
            Trendyol'dan Çek
          </Button>
        </Card>

        {/* WooCommerce Import */}
        <Card title="WooCommerce Import" extra={<SyncOutlined />}>
          <p>WooCommerce'dan tüm ürünleri çek ve local veritabanına kaydet</p>
          <Button 
            type="primary" 
            onClick={pullFromWooCommerce}
            loading={isImporting}
            block
          >
            WooCommerce'dan Çek
          </Button>
        </Card>
      </div>

      {/* CSV Import */}
      <Card title="CSV Import" extra={<FileTextOutlined />} style={{ marginBottom: '24px' }}>
        <div style={{ marginBottom: '16px' }}>
          <Button 
            icon={<DownloadOutlined />} 
            onClick={downloadCsvTemplate}
            style={{ marginRight: '8px' }}
          >
            CSV Template İndir
          </Button>
          <span style={{ color: '#666' }}>
            Template'i indirin, ürün bilgilerinizi ekleyin ve yükleyin
          </span>
        </div>
        
        <Dragger
          name="csv_file"
          accept=".csv"
          beforeUpload={handleCsvUpload}
          showUploadList={false}
          disabled={isImporting}
        >
          <p className="ant-upload-drag-icon">
            <UploadOutlined />
          </p>
          <p className="ant-upload-text">CSV dosyasını buraya sürükleyin veya tıklayın</p>
          <p className="ant-upload-hint">
            Sadece .csv dosyaları kabul edilir
          </p>
        </Dragger>
      </Card>

      {/* Import Status */}
      <Card title="Import Durumu">
        <Table
          columns={columns}
          dataSource={importStatus}
          rowKey="id"
          pagination={false}
          size="small"
        />
      </Card>
    </div>
  );
}
