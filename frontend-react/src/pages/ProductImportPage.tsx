import { Card, Upload, Button, message } from "antd";

export default function ProductImportPage(){
  return (
    <Card title="Ürün CSV Import">
      <p>CSV kolonları: name,brand,category_path,sku,price,stock,attrs</p>
      <Upload name="file" action="/csv/products/import?tenant_id=1" onChange={(info)=>{
        if(info.file.status==="done"){ message.success("Import tamamlandı"); }
      }}>
        <Button type="primary">CSV Yükle</Button>
      </Upload>
    </Card>
  );
}
