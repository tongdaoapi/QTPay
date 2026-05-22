<script setup lang="ts">
import { onMounted, ref } from "vue";
import { getPaymentProductList } from "@/api/user";

defineOptions({
  name: "PaymentProduct"
});

const loading = ref(false);
const data = ref([]);
const columns = ref([
  {
    label: "产品ID",
    prop: "product_id",
    formatter: row => {
      return row.product_id ? row.product_id : "-";
    }
  },
  {
    label: "产品名称",
    prop: "name"
  },
  {
    label: "最小支付金额",
    prop: "min_amount"
  },
  {
    label: "最大支付金额",
    prop: "max_amount"
  },
  {
    label: "费率",
    prop: "rate",
    formatter: row => {
      return row.rate ? row.rate + "%" : "-";
    }
  },
  {
    label: "状态",
    prop: "status",
    formatter: row => {
      return row.status == 1 ? "开启" : "关闭";
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
  getPaymentProductListData();
});

const getPaymentProductListData = () => {
  loadingConfig.value.text = `正在加载第${pagination.value.currentPage}页`;
  loading.value = true;
  getPaymentProductList({
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
  getPaymentProductListData();
};

const onCurrentChange = current => {
  pagination.value.currentPage = current;
  getPaymentProductListData();
};
</script>

<template>
  <el-card shadow="never">
    <template #header>
      <div class="card-header">
        <span class="font-medium">支付产品</span>
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
