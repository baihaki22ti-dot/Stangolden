import axios from "axios";

axios.defaults.baseURL = "http://localhost:8000"; // root
axios.defaults.withCredentials = true;

axios.interceptors.request.use(config => {
  const token = localStorage.getItem('auth_token');
  if (token) {
    config.headers = config.headers || {};
    config.headers.Authorization = `Bearer ${token}`;
  }
  return config;
});
export default axios;