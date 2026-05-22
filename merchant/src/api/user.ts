import { http } from "@/utils/http";

export const getLogin = data => {
  return http.request("post", "/api/user/login", { data });
};

export const refreshTokenApi = data => {
  return http.request("post", "/refresh-token", { data });
};

export const getUserInfo = () => {
  return http.request("get", "api/user/getUserInfo");
};

export const updateUserInfoSecret = () => {
  return http.request("put", "api/user/updateUserInfoSecret");
};

export const updateUserInfoIpWhiteList = data => {
  return http.request("put", "api/user/updateUserInfoIpWhiteList", { data });
};

export const updateUserInfoPassword = data => {
  return http.request("put", "api/user/updateUserInfoPassword", { data });
};

export const getGoogleVerificationCode = () => {
  return http.request("get", "api/user/getGoogleVerificationCode");
};

export const updateGoogleVerificationCode = data => {
  return http.request("post", "api/user/updateGoogleVerificationCode", {
    data
  });
};

export const unbindGoogleVerificationCode = () => {
  return http.request("put", "api/user/unbindGoogleVerificationCode");
};

export const getPaymentProductList = params => {
  return http.request("get", "api/user/getPaymentProductList", { params });
};

export const getFundFlowList = params => {
  return http.request("get", "api/user/getFundFlowList", { params });
};

export const getIndex = () => {
  return http.request("get", "api/user/index");
};
