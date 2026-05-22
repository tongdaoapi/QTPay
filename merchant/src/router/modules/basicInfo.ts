export default {
  path: "/basicInfo",
  redirect: "/basicInfo/index",
  meta: {
    icon: "ep:avatar",
    title: "基本信息",
    rank: 1
  },
  children: [
    {
      path: "/basicInfo/index",
      name: "basicInfo",
      component: () => import("@/views/basicInfo/index.vue"),
      meta: {
        title: "基本信息"
      }
    }
  ]
} satisfies RouteConfigsTable;
