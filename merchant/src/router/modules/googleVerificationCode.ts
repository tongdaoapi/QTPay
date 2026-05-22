export default {
  path: "/googleVerificationCode",
  redirect: "/googleVerificationCode/index",
  meta: {
    icon: "ep:iphone",
    title: "谷歌验证码",
    rank: 4
  },
  children: [
    {
      path: "/googleVerificationCode/index",
      name: "googleVerificationCode",
      component: () => import("@/views/googleVerificationCode/index.vue"),
      meta: {
        title: "谷歌验证码"
      }
    }
  ]
} satisfies RouteConfigsTable;
