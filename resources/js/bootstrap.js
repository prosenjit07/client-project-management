import axios from 'axios';
window.axios = axios;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
window.route = (name, params = {}, absolute = true) => {
  // minimal helper; in production recommend ziggy
  return name;
}
