<script setup lang="ts">
import { onMounted, ref } from "vue";
import {
  getGoogleVerificationCode,
  unbindGoogleVerificationCode,
  updateGoogleVerificationCode,
  updateUserInfoPassword,
  updateUserInfoSecret
} from "@/api/user";
import { ElLoading, ElMessage, ElMessageBox } from "element-plus";

defineOptions({
  name: "ApiInfo"
});

const googleVerificationData = ref();
const loading = ref(false);

const form = ref({
  secret: ""
});

onMounted(() => {
  getGoogleVerificationCodeData();
});

const getGoogleVerificationCodeData = () => {
  getGoogleVerificationCode().then(res => {
    googleVerificationData.value = res.data;
  });
};

const updateGoogleVerificationCodeEvent = () => {
  loading.value = true;
  updateGoogleVerificationCode(form.value)
    .then(res => {
      googleVerificationData.value = res.data;
    })
    .catch(e => {
      ElMessage.error(e.response.data.msg);
    })
    .finally(() => {
      loading.value = false;
    });
};

const unbindGoogleVerificationCodeEvent = () => {
  ElMessageBox.confirm("确定取消绑定谷歌验证码？", "提示").then(() => {
    const loading = ElLoading.service({
      lock: true,
      text: "Loading",
      background: "rgba(0, 0, 0, 0.7)"
    });
    unbindGoogleVerificationCode()
      .then(res => {
        googleVerificationData.value = res.data;
      })
      .finally(() => {
        form.value.secret = "";
        loading.close();
      });
  });
};
</script>

<template>
  <el-card
    shadow="never"
    v-if="
      googleVerificationData && googleVerificationData.googleVerification_secret
    "
  >
    <template #header>
      <div class="card-header">
        <span class="font-medium">谷歌验证码</span>
      </div>
    </template>
    <el-result icon="success" title="已绑定谷歌验证码">
      <template #extra>
        <div class="flex">
          <el-button type="danger" @click="unbindGoogleVerificationCodeEvent"
            >取消绑定</el-button
          >
        </div>
      </template>
    </el-result>
  </el-card>

  <el-card
    shadow="never"
    :body-style="{ height: 'calc(100vh - 260px)' }"
    v-else
  >
    <template #header>
      <div class="card-header">
        <span class="font-medium">谷歌验证码</span>
      </div>
    </template>
    <el-form
      :model="form"
      label-width="auto"
      style="max-width: 600px"
      v-if="googleVerificationData"
    >
      <el-form-item label="二维码">
        <el-image
          style="width: 100px; height: 100px"
          :src="googleVerificationData.qr_code_url"
          fit="fill"
        />
      </el-form-item>
      <el-form-item label="秘钥">
        <el-input v-model="googleVerificationData.secret" disabled />
      </el-form-item>
      <el-form-item label="验证码">
        <el-input v-model="form.secret" />
      </el-form-item>
      <el-form-item>
        <el-button
          type="primary"
          :loading="loading"
          @click="updateGoogleVerificationCodeEvent"
          >保存</el-button
        >
      </el-form-item>
    </el-form>
  </el-card>
</template>
