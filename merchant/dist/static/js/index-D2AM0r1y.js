import{d as f,r as t,o as _,aL as h,c as P,a as b,w as s,b as C,e as i,f as c}from"./index-M-RnxbGC.js";const S=f({name:"PaymentProduct",__name:"index",setup(x){const n=t(!1),r=t([]),d=t([{label:"产品ID",prop:"product_id",formatter:e=>e.product_id?e.product_id:"-"},{label:"产品名称",prop:"name"},{label:"最小支付金额",prop:"min_amount"},{label:"最大支付金额",prop:"max_amount"},{label:"费率",prop:"rate",formatter:e=>e.rate?e.rate+"%":"-"},{label:"状态",prop:"status",formatter:e=>e.status==1?"开启":"关闭"}]),a=t({pageSize:10,currentPage:1,pageSizes:[10,15,20],total:0,align:"right",background:!0,size:"default"}),l=t({text:"正在加载第一页...",viewBox:"-10, -10, 50, 50",spinner:`
        <path class="path" d="
          M 30 15
          L 28 17
          M 25.61 25.61
          A 15 15, 0, 0, 1, 15 30
          A 15 15, 0, 1, 1, 27.99 7.5
          L 15 15
        " style="stroke-width: 4px; fill: rgba(0, 0, 0, 0)"/>
      `});_(()=>{o()});const o=()=>{l.value.text=`正在加载第${a.value.currentPage}页`,n.value=!0,h({pageSize:a.value.pageSize,page:a.value.currentPage}).then(e=>{r.value=e.data.data,a.value.total=e.data.total}).finally(()=>{n.value=!1})},p=e=>{a.value.pageSize=e,a.value.currentPage=1,o()},g=e=>{a.value.currentPage=e,o()};return(e,u)=>{const m=i("pure-table"),v=i("el-card");return b(),P(v,{shadow:"never"},{header:s(()=>[...u[0]||(u[0]=[c("div",{class:"card-header"},[c("span",{class:"font-medium"},"支付产品")],-1)])]),default:s(()=>[C(m,{border:"","row-key":"id",alignWhole:"center",showOverflowTooltip:"",loading:n.value,"loading-config":l.value,data:r.value,columns:d.value,pagination:a.value,onPageSizeChange:p,onPageCurrentChange:g},null,8,["loading","loading-config","data","columns","pagination"])]),_:1})}}});export{S as default};
