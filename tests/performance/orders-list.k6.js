import http from 'k6/http';
import { check } from 'k6';

const baseUrl = __ENV.BASE_URL || 'http://127.0.0.1:8000';
const vus = Number(__ENV.VUS || 100);
const duration = __ENV.DURATION || '30s';

export const options = {
  vus,
  duration,
  thresholds: {
    http_req_failed: ['rate<0.01'],
    'http_req_duration{endpoint:orders_index}': ['p(95)<300'],
  },
};

export default function () {
  const response = http.get(`${baseUrl}/api/orders?per_page=20`, {
    tags: { endpoint: 'orders_index' },
    headers: {
      Accept: 'application/json',
    },
  });

  check(response, {
    'status is 200': (r) => r.status === 200,
    'has success code': (r) => {
      const body = r.json();
      return body && body.code === 'ORDERS_FETCHED';
    },
    'has array data': (r) => {
      const body = r.json();
      return body && Array.isArray(body.data);
    },
  });
}
