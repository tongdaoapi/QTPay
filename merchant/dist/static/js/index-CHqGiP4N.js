import{d as b,r as t,o as m,v,c as h,a as k,w as c,b as C,e as u,f as p}from"./index-VSxmZBoR.js";const x=b({name:"PaymentProduct",__name:"index",setup(y){const l=t(!1),o=t([]),i=t([{label:"平台订单编号",prop:"order_sn"},{label:"商户订单编号",prop:"merchant_order_sn"},{label:"支付金额",prop:"amount"},{label:"手续费",prop:"fee"},{label:"到账金额",prop:"price"},{label:"下单时间",prop:"created_at",formatter:a=>a.created_at?a.created_at:"-"},{label:"支付状态",prop:"pay_status",formatter:a=>a.pay_status==1?"已支付":"未支付"},{label:"支付时间",prop:"pay_at",formatter:a=>a.pay_at?a.pay_at:"-"},{label:"回调状态",prop:"callback_status",formatter:a=>a.callback_status==1?"回调成功":a.callback_status==2?"回调失败":"未回调"},{label:"回调时间",prop:"callback_at",formatter:a=>a.callback_at?a.callback_at:"-"}]),e=t({pageSize:10,currentPage:1,pageSizes:[10,15,20],total:0,align:"right",background:!0,size:"default"}),r=t({text:"正在加载第一页...",viewBox:"-10, -10, 50, 50",spinner:`
        <path class="path" d="
          M 30 15
          L 28 17
          M 25.61 25.61
          A 15 15, 0, 0, 1, 15 30
          A 15 15, 0, 1, 1, 27.99 7.5
          L 15 15
        " style="stroke-width: 4px; fill: rgba(0, 0, 0, 0)"/>
      `});m(()=>{n()});const n=()=>{r.value.text=`正在加载第${e.value.currentPage}页`,l.value=!0,v({pageSize:e.value.pageSize,page:e.value.currentPage}).then(a=>{o.value=a.data.data,e.value.total=a.data.total}).finally(()=>{l.value=!1})},d=a=>{e.value.pageSize=a,e.value.currentPage=1,n()},g=a=>{e.value.currentPage=a,n()};return(a,s)=>{const _=u("pure-table"),f=u("el-card");return k(),h(f,{shadow:"never"},{header:c(()=>[...s[0]||(s[0]=[p("div",{class:"card-header"},[p("span",{class:"font-medium"},"资金流水")],-1)])]),default:c(()=>[C(_,{border:"","row-key":"id",alignWhole:"center",showOverflowTooltip:"",loading:l.value,"loading-config":r.value,data:o.value,columns:i.value,pagination:e.value,onPageSizeChange:d,onPageCurrentChange:g},null,8,["loading","loading-config","data","columns","pagination"])]),_:1})}}});export{x as default};
