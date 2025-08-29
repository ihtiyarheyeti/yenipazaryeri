import ProductForm from "../components/ProductForm";
import ProductList from "../components/ProductList";

export default function ProductsPage() {
  return (
    <div className="grid gap-6">
      <ProductForm/>
      <ProductList/>
    </div>
  );
}
