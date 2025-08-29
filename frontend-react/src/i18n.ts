import i18n from "i18next";
import { initReactI18next } from "react-i18next";

// Vite'da dynamic import kullan
const resources: any = {
  tr: { translation: {
    "approve": "Onayla",
    "reject": "Reddet", 
    "review": "İnceleme",
    "batch_jobs": "Batch İşleri",
    "notifications": "Bildirimler",
    "app_title": "Yenipazaryeri",
    "menu_products": "Ürünler",
    "menu_users": "Kullanıcılar",
    "menu_roles": "Roller & İzinler",
    "menu_audit": "Audit Log",
    "status_draft": "Taslak",
    "status_active": "Aktif",
    "status_archived": "Arşiv",
    "publish": "Yayınla",
    "archive": "Arşivle",
    "restore": "Taslağa Al",
    "bulk_actions": "Toplu İşlem",
    "validate_csv": "CSV Doğrula",
    "import_csv": "CSV İçe Aktar",
    "export_csv": "CSV Dışa Aktar",
    "validation_report": "Validasyon Raporu",
    "language": "Dil"
  }},
  en: { translation: {
    "approve": "Approve",
    "reject": "Reject",
    "review": "Review", 
    "batch_jobs": "Batch Jobs",
    "notifications": "Notifications",
    "app_title": "Yenipazaryeri",
    "menu_products": "Products",
    "menu_users": "Users",
    "menu_roles": "Roles & Permissions",
    "menu_audit": "Audit Log",
    "status_draft": "Draft",
    "status_active": "Active",
    "status_archived": "Archived",
    "publish": "Publish",
    "archive": "Archive",
    "restore": "Restore",
    "bulk_actions": "Bulk Actions",
    "validate_csv": "Validate CSV",
    "import_csv": "Import CSV",
    "export_csv": "Export CSV",
    "validation_report": "Validation Report",
    "language": "Language"
  }}
};

i18n.use(initReactI18next).init({
  resources,
  lng: localStorage.getItem("lang") || "tr",
  fallbackLng: "tr",
  interpolation: { escapeValue: false }
});

export default i18n;
