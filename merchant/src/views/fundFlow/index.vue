<script setup lang="ts">
import { onMounted, ref } from "vue";
import { getFundFlowList, getPaymentProductList } from "@/api/user";

defineOptions({
  name: "PaymentProduct"
});

const loading = ref(false);
const data = ref([]);
const columns = ref([
  {
    label: "平台订单编号",
    prop: "order_sn"
  },
  {
    label: "商户订单编号",
    prop: "merchant_order_sn"
  },
  {
    label: "支付金额",
    prop: "amount"
  },
  {
    label: "手续费",
    prop: "fee"
  },
  {
    label: "到账金额",
    prop: "price"
  },
  {
    label: "下单时间",
    prop: "created_at",
    formatter: row => {
      return row.created_at ? row.created_at : "-";
    }
  },
  {
    label: "支付状态",
    prop: "pay_status",
    formatter: row => {
      return row.pay_status == 1 ? "已支付" : "未支付";
    }
  },
  {
    label: "支付时间",
    prop: "pay_at",
    formatter: row => {
      return row.pay_at ? row.pay_at : "-";
    }
  },
  {
    label: "回调状态",
    prop: "callback_status",
    formatter: row => {
      return row.callback_status == 1
        ? "回调成功"
        : row.callback_status == 2
          ? "回调失败"
          : "未回调";
    }
  },
  {
    label: "回调时间",
    prop: "callback_at",
    formatter: row => {
      return row.callback_at ? row.callback_at : "-";
    }
  }
]);
const pagination = ref({
  pageSize: 10,
  currentPage: 1,
  pageSizes: [10, 15, 20],
  total: 0,
  align: "right",
  background: true,
  size: "default"
});
const loadingConfig = ref({
  text: "正在加载第一页...",
  viewBox: "-10, -10, 50, 50",
  spinner: `
        <path class="path" d="
          M 30 15
          L 28 17
          M 25.61 25.61
          A 15 15, 0, 0, 1, 15 30
          A 15 15, 0, 1, 1, 27.99 7.5
          L 15 15
        " style="stroke-width: 4px; fill: rgba(0, 0, 0, 0)"/>
      `
});

onMounted(() => {
  getFundFlowListData();
});

const getFundFlowListData = () => {
  loadingConfig.value.text = `正在加载第${pagination.value.currentPage}页`;
  loading.value = true;
  getFundFlowList({
    pageSize: pagination.value.pageSize,
    page: pagination.value.currentPage
  })
    .then(res => {
      data.value = res.data.data;
      pagination.value.total = res.data.total;
    })
    .finally(() => {
      loading.value = false;
    });
};

const onSizeChange = size => {
  pagination.value.pageSize = size;
  pagination.value.currentPage = 1;
  getFundFlowListData();
};

const onCurrentChange = current => {
  pagination.value.currentPage = current;
  getFundFlowListData();
};
</script>

<template>
  <el-card shadow="never">
    <template #header>
      <div class="card-header">
        <span class="font-medium">资金流水</span>
      </div>
    </template>
    <pure-table
      border
      row-key="id"
      alignWhole="center"
      showOverflowTooltip
      :loading="loading"
      :loading-config="loadingConfig"
      :data="data"
      :columns="columns"
      :pagination="pagination"
      @page-size-change="onSizeChange"
      @page-current-change="onCurrentChange"
    />
  </el-card>
</template>
