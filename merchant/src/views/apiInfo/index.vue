<script setup lang="ts">
import { onMounted, ref } from "vue";
import {
  getUserInfo,
  updateUserInfoIpWhiteList,
  updateUserInfoSecret
} from "@/api/user";
import { ElLoading, ElMessageBox } from "element-plus";

defineOptions({
  name: "ApiInfo"
});

const form = ref({
  appid: "",
  secret: "",
  ip_white_list: ""
});

const secretType = ref("password");
const isIpWhiteList = ref(true);
const tempIpWhiteList = ref("");

onMounted(() => {
  getUserInfo().then(res => {
    setUserInfo(res);
  });
});

const resetSecret = () => {
  ElMessageBox.confirm("确定重置秘钥？", "提示").then(() => {
    const loading = ElLoading.service({
      lock: true,
      text: "Loading",
      background: "rgba(0, 0, 0, 0.7)"
    });
    updateUserInfoSecret()
      .then(res => {
        setUserInfo(res);
      })
      .finally(() => {
        loading.close();
      });
  });
};

const updateIpWhiteListEvent = () => {
  const loading = ElLoading.service({
    lock: true,
    text: "Loading",
    background: "rgba(0, 0, 0, 0.7)"
  });
  updateUserInfoIpWhiteList({
    ip_white_list: form.value.ip_white_list
  })
    .then(res => {
      setUserInfo(res);
    })
    .finally(() => {
      loading.close();
      isIpWhiteList.value = true;
    });
};

const setUserInfo = res => {
  form.value = {
    appid: res.data.user.appid,
    secret: res.data.user.secret,
    ip_white_list: res.data.user.ip_white_list
  };
  tempIpWhiteList.value = res.data.user.ip_white_list;
};
</script>

<template>
  <el-card shadow="never" :body-style="{ height: 'calc(100vh - 260px)' }">
    <template #header>
      <div class="card-header">
        <span class="font-medium">接口信息</span>
      </div>
    </template>
    <el-form :model="form" label-width="auto" style="max-width: 600px">
      <el-form-item label="appid">
        <el-input v-model="form.appid" disabled style="width: 530px" />
      </el-form-item>
      <el-form-item label="secret">
        <div style="display: flex; align-items: center">
          <el-input
            v-model="form.secret"
            :type="secretType"
            disabled
            style="width: 530px"
          />
          <el-button
            type="primary"
            style="margin-left: 12px"
            @click="
              secretType == 'text'
                ? (secretType = 'password')
                : (secretType = 'text')
            "
            >{{ secretType == "text" ? "隐藏" : "查看" }}</el-button
          >
          <el-button type="danger" @click="resetSecret">重置</el-button>
        </div>
      </el-form-item>
      <el-form-item label="IP白名单">
        <div style="display: flex; align-items: center">
          <el-input
            v-model="form.ip_white_list"
            placeholder="多个IP用逗号隔开"
            :disabled="isIpWhiteList"
            style="width: 530px"
          />
          <template v-if="isIpWhiteList">
            <el-button
              type="primary"
              style="margin-left: 12px"
              @click="isIpWhiteList = false"
              >编辑</el-button
            >
          </template>
          <template v-if="!isIpWhiteList">
            <el-button
              type="primary"
              style="margin-left: 12px"
              @click="
                isIpWhiteList = false;
                updateIpWhiteListEvent();
              "
              >保存</el-button
            >
            <el-button
              type="danger"
              @click="
                isIpWhiteList = true;
                form.ip_white_list = tempIpWhiteList;
              "
              >取消</el-button
            >
          </template>
        </div>
      </el-form-item>
    </el-form>
  </el-card>
</template>
