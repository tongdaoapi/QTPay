export default {
  path: "/fundFlow",
  redirect: "/fundFlow/index",
  meta: {
    icon: "ep:list",
    title: "资金流水",
    rank: 6
  },
  children: [
    {
      path: "/fundFlow/index",
      name: "fundFlow",
      component: () => import("@/views/fundFlow/index.vue"),
      meta: {
        title: "资金流水"
      }
    }
  ]
} satisfies RouteConfigsTable;
