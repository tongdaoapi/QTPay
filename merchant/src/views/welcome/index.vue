<script setup lang="ts">
import ReCol from "@/components/ReCol";
import { onMounted, ref } from "vue";
import { getIndex } from "@/api/user";
defineOptions({
  name: "Welcome"
});

const data = ref();

onMounted(() => {
  getIndex().then(res => {
    data.value = res.data;
  });
});
</script>

<template>
  <div v-if="data">
    <el-row :gutter="24" justify="space-around">
      <re-col class="mb-4.5" :value="8">
        <el-card class="line-card" shadow="never">
          <div class="flex justify-between items-center">
            <span class="text-md font-medium">余额</span>
            <div class="size-8 flex-c rounded-md">{{ data.amount }}</div>
          </div>
        </el-card>
      </re-col>
      <re-col class="mb-4.5" :value="8">
        <el-card class="line-card" shadow="never">
          <div class="flex justify-between items-center">
            <span class="text-md font-medium">今日总订单</span>
            <div class="size-8 flex-c rounded-md">
              {{ data.todayOrderCount }}
            </div>
          </div>
        </el-card>
      </re-col>
      <re-col class="mb-4.5" :value="8">
        <el-card class="line-card" shadow="never">
          <div class="flex justify-between items-center">
            <span class="text-md font-medium">今日总金额</span>
            <div class="size-8 flex-c rounded-md">
              {{ data.todayOrderAmount }}
            </div>
          </div>
        </el-card>
      </re-col>
      <re-col class="mb-4.5" :value="8">
        <el-card class="line-card" shadow="never">
          <div class="flex justify-between items-center">
            <span class="text-md font-medium">今日手续费</span>
            <div class="size-8 flex-c rounded-md">{{ data.todayOrderFee }}</div>
          </div>
        </el-card>
      </re-col>
      <re-col class="mb-4.5" :value="8">
        <el-card class="line-card" shadow="never">
          <div class="flex justify-between items-center">
            <span class="text-md font-medium">今日支付订单</span>
            <div class="size-8 flex-c rounded-md">
              {{ data.todayOrderPayCount }}
            </div>
          </div>
        </el-card>
      </re-col>
      <re-col class="mb-4.5" :value="8">
        <el-card class="line-card" shadow="never">
          <div class="flex justify-between items-center">
            <span class="text-md font-medium">今日支付金额</span>
            <div class="size-8 flex-c rounded-md">
              {{ data.todayOrderPayAmount }}
            </div>
          </div>
        </el-card>
      </re-col>
    </el-row>
  </div>
</template>
