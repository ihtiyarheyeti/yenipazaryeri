import { Layout, Menu, Badge, Drawer, List, Tag, Select } from "antd";
import { Link, useLocation } from "react-router-dom";
import { useEffect, useState } from "react";
import { useTranslation } from "react-i18next";
import { api, getBranding, API_BASE, currentTenantId } from "../api";
import { BellOutlined, AppstoreOutlined, ShoppingOutlined, ApiOutlined, BranchesOutlined, TagsOutlined, UserOutlined, MailOutlined, SecurityScanOutlined, ExperimentOutlined, FileSearchOutlined, ShoppingCartOutlined, ImportOutlined } from "@ant-design/icons";

const { Sider, Header, Content } = Layout;

export default function AppLayout({children}:{children:any}){
  const loc = useLocation();
  const selected = [loc.pathname];
  const [perms, setPerms] = useState<string[]>([]);
  const [alerts,setAlerts]=useState<any[]>([]);
  const [open,setOpen]=useState(false);
  const [brand,setBrand]=useState<any>(null);
  const [notifs,setNotifs]=useState<any[]>([]);
  const [notifOpen,setNotifOpen]=useState(false);
  const { t, i18n } = useTranslation();

  async function loadAlerts(){
    try{
      const r=await api("/dashboard/alerts");
      setAlerts(r.items||[]);
    }catch{}
  }
  async function loadNotifications(){ 
    try {
      const r = await api('/notifications');
      setNotifs(r.items || []);
    } catch(e) {
      console.error('Notifications load error:', e);
    }
  }
  
  useEffect(() => { 
    api('/auth/permissions').then(r => setPerms(r.items || [])).catch(() => {}); 
  }, []);
  
  useEffect(()=>{ 
    loadAlerts(); 
    loadNotifications();
    const i=setInterval(loadAlerts, 15000); 
    const j=setInterval(loadNotifications, 20000);
    return ()=>{ clearInterval(i); clearInterval(j); }
  },[]);
  
  useEffect(()=>{ 
    (async()=>{ 
      const b=await getBranding(currentTenantId()); 
      setBrand(b);
      if(b?.theme_primary) document.documentElement.style.setProperty('--primary', b.theme_primary);
      if(b?.theme_accent)  document.documentElement.style.setProperty('--accent', b.theme_accent);
      document.body.classList.toggle('dark', b?.theme_mode==='dark');
    })(); 
  },[]);
  
  const errorCount=alerts.length;
  const notifCount=notifs.length;
  function can(p: string) { return perms.includes(p); }
  
  return (
    <Layout style={{minHeight:"100vh"}}>
      <Sider width={220} theme={brand?.theme_mode==='dark'?'dark':'light'}>
        <div style={{padding:12,display:"flex",alignItems:"center",gap:8}}>
          {brand?.logo_url ? <img src={`${API_BASE}${brand.logo_url}`} alt="logo" style={{height:28}}/> : <div style={{fontWeight:700}}>Woontegra Panel</div>}
        </div>
        <Menu 
          mode="inline" 
          selectedKeys={selected}
          items={[
            {
              key: "/",
              icon: <AppstoreOutlined/>,
              label: <Link to="/">Ana Sayfa</Link>
            },
            {
              key: "entegrasyon",
              icon: <ApiOutlined/>,
              label: "Entegrasyonlar",
              children: [
                {
                  key: "/connections",
                  label: <Link to="/connections">Bağlantılar</Link>
                },
                {
                  key: "/category-mapping",
                  label: <Link to="/category-mapping">Kategori Eşleme</Link>
                },
                {
                  key: "/catalog-map",
                  label: <Link to="/catalog-map">Katalog Eşleme</Link>
                },
                {
                  key: "/returns-cancels",
                  label: <Link to="/returns-cancels">İadeler & İptaller</Link>
                },
                {
                  key: "/ship-invoice",
                  label: <Link to="/ship-invoice">Kargo & Fatura</Link>
                },
                {
                  key: "entegrasyon-siparisler",
                  label: "Siparişler",
                  children: [
                    {
                      key: "/orders-woo",
                      label: <Link to="/orders-woo">Woo Siparişleri</Link>
                    },
                    {
                      key: "/orders-trendyol",
                      label: <Link to="/orders-trendyol">Trendyol Siparişleri</Link>
                    }
                  ]
                },
                {
                  key: "/reconcile-suggestions",
                  label: <Link to="/reconcile-suggestions">Uyum Önerileri</Link>
                },
                {
                  key: "/policies",
                  label: <Link to="/policies">Sistem Ayarları</Link>
                },
                {
                  key: "/reconcile",
                  label: <Link to="/reconcile">Uyum Kontrolü</Link>
                }
              ]
            },
            {
              key: "ana-siparisler",
              icon: <ShoppingCartOutlined/>,
              label: <Link to="/orders">Siparişler</Link>
            },
            {
              key: "kullanici",
              icon: <UserOutlined/>,
              label: "Kullanıcı Yönetimi",
              children: [
                {
                  key: "/users",
                  label: <Link to="/users">Kullanıcılar</Link>
                },
                {
                  key: "/roles",
                  label: <Link to="/roles">Roller & İzinler</Link>
                }
              ]
            },
            {
              key: "rapor",
              icon: <FileSearchOutlined/>,
              label: "Raporlar & Log",
              children: [
                {
                  key: "/audit",
                  label: <Link to="/audit">Audit Log</Link>
                },
                {
                  key: "/logs",
                  label: <Link to="/logs">İşlem Kayıtları</Link>
                },
                {
                  key: "/queue",
                  label: <Link to="/queue">Queue Yönetimi</Link>
                },
                {
                  key: "/batches",
                  label: <Link to="/batches">Batch Jobs</Link>
                }
              ]
            },
            {
              key: "products",
              icon: <ShoppingOutlined/>,
              label: "Ürünler",
              children: [
                {
                  key: "/products",
                  label: <Link to="/products">Tüm Ürünler</Link>
                },
                                 {
                   key: "/products-woo",
                   label: <Link to="/products-woo">WooCommerce Ürünleri</Link>
                 },
                 {
                   key: "/products-trendyol",
                   label: <Link to="/products-trendyol">Trendyol Ürünleri</Link>
                 }
              ]
            },
            {
              key: "/product-import",
              icon: <ImportOutlined/>,
              label: <Link to="/product-import">Ürün Import</Link>
            },
            {
              key: "/variants",
              icon: <TagsOutlined/>,
              label: <Link to="/variants">Varyantlar</Link>
            }
          ]}
        />
      </Sider>
      
      <Layout>
        <Header style={{background:"var(--header-bg)",padding:"0 16px",display:"flex",justifyContent:"space-between",alignItems:"center"}}>
          <div>Panel</div>
          <div style={{display:"flex",alignItems:"center",gap:16}}>
            <Select size="small" value={i18n.language} style={{width:90}}
              onChange={(v)=>{ i18n.changeLanguage(v); localStorage.setItem("lang", v); }}
              options={[{label:"Türkçe",value:"tr"},{label:"English",value:"en"}]}
            />
            <Badge count={notifCount} size="small">
              <BellOutlined style={{fontSize:18,cursor:"pointer"}} onClick={()=>setNotifOpen(true)}/>
            </Badge>
            <Badge count={errorCount} size="small">
              <BellOutlined style={{fontSize:18,cursor:"pointer",color:'red'}} onClick={()=>setOpen(true)}/>
            </Badge>
            <a href="/login" onClick={()=>{localStorage.clear()}}>Çıkış</a>
          </div>
        </Header>
        <Content style={{padding:16}}>{children}</Content>
        
        {/* Hata Bildirimleri Drawer */}
        <Drawer title="Hata Bildirimleri" open={open} onClose={()=>setOpen(false)} width={520}>
          <List
            dataSource={alerts}
            renderItem={(x:any)=>(
              <List.Item>
                <List.Item.Meta
                  title={<>{x.type} <Tag color="red">{x.status}</Tag></>}
                  description={<div><div style={{opacity:.8}}>{x.created_at}</div><div>{x.message||"-"}</div></div>}
                />
              </List.Item>
            )}
          />
        </Drawer>
        
        {/* Bildirimler Drawer */}
        <Drawer title="Bildirimler" open={notifOpen} onClose={()=>setNotifOpen(false)} width={520}>
          <List
            dataSource={notifs}
            renderItem={(n:any)=>(
              <List.Item>
                <List.Item.Meta
                  title={n.title}
                  description={<div><div style={{opacity:.8}}>{n.created_at}</div><div>{n.body}</div></div>}
                />
              </List.Item>
            )}
          />
        </Drawer>
      </Layout>
    </Layout>
  );
}
