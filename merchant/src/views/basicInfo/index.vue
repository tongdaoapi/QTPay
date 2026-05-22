<script setup lang="ts">
import { onMounted, ref } from "vue";
import { getUserInfo } from "@/api/user";

defineOptions({
  name: "BasicInfo"
});

const form = ref({
  username: "",
  amount: ""
});

onMounted(() => {
  getUserInfo().then(res => {
    form.value = {
      username: res.data.user.username,
      amount: res.data.user.amount
    };
  });
});
</script>

<template>
  <el-card shadow="never" :body-style="{ height: 'calc(100vh - 260px)' }">
    <template #header>
      <div class="card-header">
        <span class="font-medium">基本信息</span>
      </div>
    </template>
    <el-form :model="form" label-width="auto" style="max-width: 600px">
      <el-form-item label="用户名">
        <el-input v-model="form.username" disabled />
      </el-form-item>
      <el-form-item label="账户余额">
        <el-input v-model="form.amount" disabled />
      </el-form-item>
    </el-form>
  </el-card>
</template>
