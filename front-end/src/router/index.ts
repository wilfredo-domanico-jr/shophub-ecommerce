import { createRouter, createWebHistory } from "vue-router";

// Layouts
import DefaultLayout from "../layouts/DefaultLayout.vue";
import AuthenticationLayout from "../layouts/AuthenticationLayout.vue";
import AdminLayout from "../layouts/AdminLayout.vue";

// Public Pages
import Home from "../views/Home.vue";
import Shop from "../views/Shop.vue";
import ProductDetail from "../views/ProductDetail.vue";
import InfoPage from "../views/InfoPage.vue";

// Auth
import Login from "../views/Auth/Login.vue";

// Admin Pages
import AdminDashboard from "../views/Admin/Dashboard.vue";
import AdminProducts from "../views/Admin/Products.vue";
import AdminOrders from "../views/Admin/Orders.vue";
import AdminCategories from "../views/Admin/Categories.vue";
import AdminUsers from "../views/Admin/Users.vue";

// Misc
import NotFound from "../views/NotFound.vue";

const routes = [
  // =========================
  // PUBLIC WEBSITE
  // =========================
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
    ],
  },

  // =========================
  // AUTH (LOGIN / REGISTER)
  // =========================
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

  // =========================
  // ADMIN DASHBOARD
  // =========================
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
    ],
  },

  // =========================
  // 404 PAGE (MUST BE LAST)
  // =========================
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
  const isLoginPage = to.name === "Login";

  if (!requiresAdmin && !isLoginPage) {
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

  if (isLoginPage && auth.isAdmin) {
    return { name: "AdminDashboard" };
  }

  return true;
});

export default router;