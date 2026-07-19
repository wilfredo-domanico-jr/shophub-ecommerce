import { createRouter, createWebHistory } from "vue-router";

// Layouts
import DefaultLayout from "../layouts/DefaultLayout.vue";
import AuthenticationLayout from "../layouts/AuthenticationLayout.vue";
import AdminLayout from "../layouts/AdminLayout.vue";

// Public Pages
import Home from "../views/Home.vue";
import Shop from "../views/Shop.vue";
import ProductDetail from "../views/ProductDetail.vue";
import Vouchers from "../views/Vouchers.vue";
import InfoPage from "../views/InfoPage.vue";

// Auth
import Login from "../views/Auth/Login.vue";
import CustomerLogin from "../views/Auth/CustomerLogin.vue";
import Register from "../views/Auth/Register.vue";
import ForgotPassword from "../views/Auth/ForgotPassword.vue";
import ResetPassword from "../views/Auth/ResetPassword.vue";
import AuthCallback from "../views/Auth/AuthCallback.vue";
import Unsubscribe from "../views/Unsubscribe.vue";

// Account Pages
import AccountProfile from "../views/Account/Profile.vue";
import MyOrders from "../views/Account/MyOrders.vue";

// Admin Pages
import AdminDashboard from "../views/Admin/Dashboard.vue";
import AdminProducts from "../views/Admin/Products.vue";
import AdminOrders from "../views/Admin/Orders.vue";
import AdminCategories from "../views/Admin/Categories.vue";
import AdminUsers from "../views/Admin/Users.vue";
import AdminCareers from "../views/Admin/Careers.vue";
import AdminVouchers from "../views/Admin/Vouchers.vue";
import AdminFlashSales from "../views/Admin/FlashSales.vue";
import AdminNewsletters from "../views/Admin/Newsletters.vue";

// Misc
import NotFound from "../views/NotFound.vue";

const routes = [
  // Public site
  {
    path: "/",
    component: DefaultLayout,
    children: [
      {
        path: "",
        name: "Home",
        component: Home,
      },
      {
        path: "products",
        name: "Shop",
        component: Shop,
      },
      {
        path: "products/:slug",
        name: "ProductDetail",
        component: ProductDetail,
      },
      {
        path: "vouchers",
        name: "Vouchers",
        component: Vouchers,
      },
      {
        path: "help-center",
        name: "HelpCenter",
        component: InfoPage,
        props: { slug: "help-center" },
      },
      {
        path: "returns-refunds",
        name: "ReturnsRefunds",
        component: InfoPage,
        props: { slug: "returns-refunds" },
      },
      {
        path: "shipping-info",
        name: "ShippingInfo",
        component: InfoPage,
        props: { slug: "shipping-info" },
      },
      {
        path: "our-story",
        name: "OurStory",
        component: InfoPage,
        props: { slug: "our-story" },
      },
      {
        path: "careers",
        name: "Careers",
        component: InfoPage,
        props: { slug: "careers" },
      },
      {
        path: "press-media",
        name: "PressMedia",
        component: InfoPage,
        props: { slug: "press-media" },
      },
      {
        path: "privacy-policy",
        name: "PrivacyPolicy",
        component: InfoPage,
        props: { slug: "privacy-policy" },
      },

      // Customer auth
      {
        path: "login",
        name: "CustomerLogin",
        component: CustomerLogin,
        meta: { guestOnly: true, minimalChrome: true },
      },
      {
        path: "register",
        name: "Register",
        component: Register,
        meta: { guestOnly: true },
      },
      {
        path: "forgot-password",
        name: "ForgotPassword",
        component: ForgotPassword,
        meta: { guestOnly: true },
      },
      {
        path: "reset-password",
        name: "ResetPassword",
        component: ResetPassword,
      },
      {
        // Social login lands here with ?token= or ?error= from the backend.
        // No guestOnly — the guard must never race the token handling.
        path: "auth/callback",
        name: "AuthCallback",
        component: AuthCallback,
        meta: { minimalChrome: true },
      },
      {
        // Newsletter unsubscribe links land here with ?token=.
        path: "unsubscribe",
        name: "Unsubscribe",
        component: Unsubscribe,
        meta: { minimalChrome: true },
      },

      // Customer account
      {
        path: "account",
        name: "AccountProfile",
        component: AccountProfile,
        meta: { requiresAuth: true },
      },
      {
        path: "account/orders",
        name: "MyOrders",
        component: MyOrders,
        meta: { requiresAuth: true },
      },
    ],
  },

  // Auth
  {
    path: "/admin/login",
    component: AuthenticationLayout,
    children: [
      {
        path: "",
        name: "Login",
        component: Login,
      },
    ],
  },

  // Admin
  {
    path: "/admin",
    component: AdminLayout,
    meta: { requiresAdmin: true },
    children: [
      {
        path: "",
        name: "AdminDashboard",
        component: AdminDashboard,
      },
      {
        path: "products",
        name: "AdminProducts",
        component: AdminProducts,
      },
      {
        path: "orders",
        name: "AdminOrders",
        component: AdminOrders,
      },
      {
        path: "categories",
        name: "AdminCategories",
        component: AdminCategories,
      },
      {
        path: "users",
        name: "AdminUsers",
        component: AdminUsers,
      },
      {
        path: "careers",
        name: "AdminCareers",
        component: AdminCareers,
      },
      {
        path: "vouchers",
        name: "AdminVouchers",
        component: AdminVouchers,
      },
      {
        path: "flash-sales",
        name: "AdminFlashSales",
        component: AdminFlashSales,
      },
      {
        path: "newsletters",
        name: "AdminNewsletters",
        component: AdminNewsletters,
      },
    ],
  },

  // Catch-all 404 — keep last
  {
    path: "/:pathMatch(.*)*",
    name: "NotFound",
    component: NotFound,
  },
];

const router = createRouter({
  history: createWebHistory(),
  routes,
});

router.beforeEach(async (to) => {
  const requiresAdmin = to.matched.some((record) => record.meta.requiresAdmin);
  const requiresAuth = to.matched.some((record) => record.meta.requiresAuth);
  const guestOnly = to.matched.some((record) => record.meta.guestOnly);
  const isAdminLoginPage = to.name === "Login";

  if (!requiresAdmin && !requiresAuth && !guestOnly && !isAdminLoginPage) {
    return true;
  }

  const { useAuthStore } = await import("../stores/auth");
  const auth = useAuthStore();

  if (!auth.initialized) {
    await auth.fetchUser();
  }

  if (requiresAdmin && !auth.isAdmin) {
    return { name: "Login" };
  }

  if (isAdminLoginPage && auth.isAdmin) {
    return { name: "AdminDashboard" };
  }

  if (requiresAuth && !auth.isLoggedIn) {
    return { name: "CustomerLogin", query: { redirect: to.fullPath } };
  }

  if (guestOnly && auth.isLoggedIn) {
    return { name: "AccountProfile" };
  }

  return true;
});

export default router;