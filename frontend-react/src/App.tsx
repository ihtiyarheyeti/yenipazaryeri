import { BrowserRouter, Routes, Route, Navigate } from "react-router-dom";
import AppLayout from "./layout/AppLayout";
import Dashboard from "./pages/Dashboard";
import Login from "./pages/Login";
import Products from "./pages/Products";
import ProductsWoo from "./pages/ProductsWoo";
import ProductsTrendyol from "./pages/ProductsTrendyol";
import ProductImages from "./pages/ProductImages";
import OrdersWoo from "./pages/OrdersWoo";
import OrdersTrendyol from "./pages/OrdersTrendyol";
import OrderDetail from "./pages/OrderDetail";
import WooProducts from "./pages/WooProducts";
import TrendyolProducts from "./pages/TrendyolProducts";
import Variants from "./pages/Variants";
import Connections from "./pages/Connections";
import CategoryMapping from "./pages/CategoryMapping";
import Users from "./pages/Users";
import Invite from "./pages/Invite";
import Audit from "./pages/Audit";
import Logs from "./pages/Logs";
import SmtpTest from "./pages/SmtpTest";
import Security from "./pages/Security";
import Queue from "./pages/Queue";
import Branding from "./pages/Branding";
import Roles from "./pages/Roles";
import InviteAccept from "./pages/InviteAccept";
import ForgotPassword from "./pages/ForgotPassword";
import ResetPassword from "./pages/ResetPassword";
import Batches from "./pages/Batches";
import CatalogMap from "./pages/CatalogMap";
import Orders from "./pages/Orders";
import ReturnsCancels from "./pages/ReturnsCancels";
import ShipInvoice from "./pages/ShipInvoice";
import ReconcileSuggestions from "./pages/ReconcileSuggestions";
import Policies from "./pages/Policies";
import Reconcile from "./pages/Reconcile";
import ProductImport from "./pages/ProductImport";

function Protected({children}:{children:any}){ 
  const t = localStorage.getItem("token"); 
  return t ? children : <Navigate to="/login"/>; 
}

export default function App(){
  return (
    <BrowserRouter
      future={{
        v7_startTransition: true,
        v7_relativeSplatPath: true
      }}
    >
      <Routes>
        <Route path="/login" element={<Login/>}/>
        <Route path="/" element={
          <Protected>
            <AppLayout>
              <Dashboard/>
            </AppLayout>
          </Protected>
        }/>
        <Route path="/products" element={
          <Protected>
            <AppLayout>
              <Products/>
            </AppLayout>
          </Protected>
        }/>
        <Route path="/products-woo" element={
          <Protected>
            <AppLayout>
              <ProductsWoo/>
            </AppLayout>
          </Protected>
        }/>
                 <Route path="/products-trendyol" element={
           <Protected>
             <AppLayout>
               <ProductsTrendyol/>
             </AppLayout>
           </Protected>
         }/>
         <Route path="/product/:id/images" element={
           <Protected>
             <AppLayout>
               <ProductImages/>
             </AppLayout>
           </Protected>
         }/>
        <Route path="/woo-products" element={
          <Protected>
            <AppLayout>
              <WooProducts/>
            </AppLayout>
          </Protected>
        }/>
        <Route path="/trendyol-products" element={
          <Protected>
            <AppLayout>
              <TrendyolProducts/>
            </AppLayout>
          </Protected>
        }/>
        <Route path="/variants" element={
          <Protected>
            <AppLayout>
              <Variants/>
            </AppLayout>
          </Protected>
        }/>
        <Route path="/connections" element={
          <Protected>
            <AppLayout>
              <Connections/>
            </AppLayout>
          </Protected>
        }/>
        <Route path="/category-mapping" element={
          <Protected>
            <AppLayout>
              <CategoryMapping/>
            </AppLayout>
          </Protected>
        }/>
        <Route path="/users" element={
          <Protected>
            <AppLayout>
              <Users/>
            </AppLayout>
          </Protected>
        }/>
        <Route path="/invite" element={
          <Protected>
            <AppLayout>
              <Invite/>
            </AppLayout>
          </Protected>
        }/>
        <Route path="/audit" element={
          <Protected>
            <AppLayout>
              <Audit/>
            </AppLayout>
          </Protected>
        }/>
        <Route path="/logs" element={
          <Protected>
            <AppLayout>
              <Logs/>
            </AppLayout>
          </Protected>
        }/>
        <Route path="/queue" element={
          <Protected>
            <AppLayout>
              <Queue/>
            </AppLayout>
          </Protected>
        }/>
        <Route path="/batches" element={
          <Protected>
            <AppLayout>
              <Batches/>
            </AppLayout>
          </Protected>
        }/>
        <Route path="/catalog-map" element={
          <Protected>
            <AppLayout>
              <CatalogMap/>
            </AppLayout>
          </Protected>
        }/>
        <Route path="/orders" element={
          <Protected>
            <AppLayout>
              <Orders/>
            </AppLayout>
          </Protected>
        }/>
        <Route path="/orders-woo" element={
          <Protected>
            <AppLayout>
              <OrdersWoo/>
            </AppLayout>
          </Protected>
        }/>
        <Route path="/orders-trendyol" element={
          <Protected>
            <AppLayout>
              <OrdersTrendyol/>
            </AppLayout>
          </Protected>
        }/>
        <Route path="/orders/:id" element={
          <Protected>
            <AppLayout>
              <OrderDetail/>
            </AppLayout>
          </Protected>
        }/>
        <Route path="/returns-cancels" element={
          <Protected>
            <AppLayout>
              <ReturnsCancels/>
            </AppLayout>
          </Protected>
        }/>
        <Route path="/ship-invoice" element={
          <Protected>
            <AppLayout>
              <ShipInvoice/>
            </AppLayout>
          </Protected>
        }/>
        <Route path="/reconcile-suggestions" element={
          <Protected>
            <AppLayout>
              <ReconcileSuggestions/>
            </AppLayout>
          </Protected>
        }/>
        <Route path="/policies" element={
          <Protected>
            <AppLayout>
              <Policies/>
            </AppLayout>
          </Protected>
        }/>
        <Route path="/reconcile" element={
          <Protected>
            <AppLayout>
              <Reconcile/>
            </AppLayout>
          </Protected>
        }/>
        <Route path="/smtp-test" element={
          <Protected>
            <AppLayout>
              <SmtpTest/>
            </AppLayout>
          </Protected>
        }/>
        <Route path="/security" element={
          <Protected>
            <AppLayout>
              <Security/>
            </AppLayout>
          </Protected>
        }/>
        <Route path="/branding" element={
          <Protected>
            <AppLayout>
              <Branding/>
            </AppLayout>
          </Protected>
        }/>
        <Route path="/roles" element={
          <Protected>
            <AppLayout>
              <Roles/>
            </AppLayout>
          </Protected>
        }/>
        <Route path="/invite" element={<InviteAccept/>}/>
        <Route path="/forgot-password" element={<ForgotPassword/>}/>
        <Route path="/reset-password" element={<ResetPassword/>}/>
        <Route path="/product-import" element={
          <Protected>
            <AppLayout>
              <ProductImport/>
            </AppLayout>
          </Protected>
        }/>
      </Routes>
    </BrowserRouter>
  );
}
