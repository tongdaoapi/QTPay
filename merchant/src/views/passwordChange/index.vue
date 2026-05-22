<script setup lang="ts">
import { ref } from "vue";
import { updateUserInfoPassword, updateUserInfoSecret } from "@/api/user";
import { ElMessage } from "element-plus";

defineOptions({
  name: "PasswordChange"
});

const loading = ref(false);

const form = ref({
  oldPassword: "",
  newPassword: "",
  confirmPassword: ""
});

const updateUserInfoPasswordEvent = () => {
  loading.value = true;
  updateUserInfoPassword(form.value)
    .then(res => {})
    .catch(e => {
      ElMessage.error(e.response.data.msg);
    })
    .finally(() => {
      loading.value = false;
    });
};
</script>

<template>
  <el-card shadow="never" :body-style="{ height: 'calc(100vh - 260px)' }">
    <template #header>
      <div class="card-header">
        <span class="font-medium">密码修改</span>
      </div>
    </template>
    <el-form :model="form" label-width="auto" style="max-width: 600px">
      <el-form-item label="旧密码">
        <el-input v-model="form.oldPassword" type="password" show-password />
      </el-form-item>
      <el-form-item label="新密码">
        <el-input v-model="form.newPassword" type="password" show-password />
      </el-form-item>
      <el-form-item label="确认密码">
        <el-input
          v-model="form.confirmPassword"
          type="password"
          show-password
        />
      </el-form-item>
      <el-form-item>
        <el-button
          type="primary"
          :loading="loading"
          @click="updateUserInfoPasswordEvent"
          >保存</el-button
        >
      </el-form-item>
    </el-form>
  </el-card>
</template>
