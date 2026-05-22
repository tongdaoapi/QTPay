export default {
  path: "/apiInfo",
  redirect: "/apiInfo/index",
  meta: {
    icon: "ep:connection",
    title: "接口信息",
    rank: 2
  },
  children: [
    {
      path: "/apiInfo/index",
      name: "apiInfo",
      component: () => import("@/views/apiInfo/index.vue"),
      meta: {
        title: "接口信息"
      }
    }
  ]
} satisfies RouteConfigsTable;
