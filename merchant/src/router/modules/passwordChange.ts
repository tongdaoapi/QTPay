export default {
  path: "/passwordChange",
  redirect: "/passwordChange/index",
  meta: {
    icon: "ep:lock",
    title: "密码修改",
    rank: 3
  },
  children: [
    {
      path: "/passwordChange/index",
      name: "passwordChange",
      component: () => import("@/views/passwordChange/index.vue"),
      meta: {
        title: "密码修改"
      }
    }
  ]
} satisfies RouteConfigsTable;
