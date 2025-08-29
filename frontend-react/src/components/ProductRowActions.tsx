import { Button, Dropdown, message, Tooltip } from "antd";
import { EllipsisOutlined } from "@ant-design/icons";
import { api } from "../api";

export function ProductRowActions({row}:{row:any}){
  const origin = row.origin_mp; // woo | trendyol | local
  const mainLabel = origin==='woo' ? 'Eşle & Trendyol\'a Gönder'
                   : origin==='trendyol' ? 'Eşle & Woo\'ya Gönder'
                   : 'Yayınla';

  const onMain = async () => {
    try{
      if(origin==='woo'){
        const r=await api(`/products/${row.id}/push/trendyol`,{method:'POST'});
        if(r?.ok) message.success('Trendyol\'a gönderildi');
        else{
          if(r?.needs_mapping) { message.warning(r?.error||'Eşleme gerekiyor'); window.location.href='/catalog-map'; }
          else message.error(r?.error||'Hata');
        }
      } else if(origin==='trendyol'){
        const r=await api(`/products/${row.id}/push/woo`,{method:'POST'});
        if(r?.ok) message.success('WooCommerce\'e gönderildi');
        else{
          if(r?.needs_mapping) { message.warning(r?.error||'Eşleme gerekiyor'); window.location.href='/catalog-map'; }
          else message.error(r?.error||'Hata');
        }
      } else {
        message.info('Local ürünü yayınlama akışı (gelecek sürüm).');
      }
    }catch(e){ message.error('İşlem başarısız'); }
  };

  const handleMenu = async (key:string) => {
    if(key==='images'){ window.location.href=`/product/${row.id}/images`; return; }
    if(key==='logs'){ window.location.href=`/logs?product_id=${row.id}`; return; }
    if(key==='variants'){ const r=await api(`/products/${row.id}/create-woo-variations`,{method:'POST'}); message.info(`Woo varyant oluştur: ${r?.created||0}`); return; }
    if(key==='archive'){ /* backend uçların varsa çağır */ message.success('Arşivlendi (mock)'); return; }
    if(key==='draft'){ /* taslağa al */ message.success('Taslağa alındı (mock)'); return; }
    if(key==='approve'){ /* onayla */ message.success('Onaylandı (mock)'); return; }
    if(key==='reject'){ /* reddet */ message.success('Reddedildi (mock)'); return; }
  };

  const menu = {
    items: [
      { key:'images',   label:'Görseller' },
      { key:'logs',     label:'Loglar' },
      { key:'variants', label:'Woo Varyant Oluştur' },
      { type:'divider' },
      { key:'archive',  label:'Arşivle' },
      { key:'draft',    label:'Taslağa Al' },
      { key:'approve',  label:'Onayla' },
      { key:'reject',   label:'Reddet' },
    ],
    onClick: ({key}:{key:string}) => handleMenu(key)
  };

  return (
    <>
      <Tooltip title={mainLabel}><Button type="primary" onClick={onMain}>{mainLabel}</Button></Tooltip>
      <Dropdown menu={menu}><Button style={{marginLeft:8}} icon={<EllipsisOutlined/>}/></Dropdown>
    </>
  );
}
