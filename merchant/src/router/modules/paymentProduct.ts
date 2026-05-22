export default {
  path: "/paymentProduct",
  redirect: "/paymentProduct/index",
  meta: {
    icon: "ep:credit-card",
    title: "支付产品",
    rank: 5
  },
  children: [
    {
      path: "/paymentProduct/index",
      name: "paymentProduct",
      component: () => import("@/views/paymentProduct/index.vue"),
      meta: {
        title: "支付产品"
      }
    }
  ]
} satisfies RouteConfigsTable;
