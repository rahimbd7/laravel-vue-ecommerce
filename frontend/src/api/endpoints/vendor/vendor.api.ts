import api from '../../api'

export const vendorApi = {
    // Dashboard
    getDashboard: () => 
        api.get('/vendor/dashboard'),
    
    // Orders
    getOrders: (params = {}) => 
        api.get('/vendor/dashboard/orders', { params }),
    
    getOrderStats: () => 
        api.get('/vendor/dashboard/orders/stats'),
    
    getRecentOrders: (limit = 10) => 
        api.get('/vendor/dashboard/orders/recent', { params: { limit } }),
    
    // ✅ Get single order details
    getOrder: (orderId: number) => 
        api.get(`/vendor/dashboard/orders/${orderId}`),
    
    updateOrderStatus: (orderId: number, data: { 
        status: string; 
        tracking_number?: string; 
        carrier?: string 
    }) => 
        api.put(`/vendor/dashboard/orders/${orderId}/status`, data),
    //vendor coupons
    // Coupons
  getCoupons: (params?: any) => api.get('/vendor/coupons', { params }),
  getCoupon: (id: string) => api.get(`/vendor/coupons/${id}`),
  createCoupon: (data: any) => api.post('/vendor/coupons', data),
  updateCoupon: (id: string, data: any) => api.put(`/vendor/coupons/${id}`, data),
  deleteCoupon: (id: string) => api.delete(`/vendor/coupons/${id}`),
  toggleCouponStatus: (id: string) => api.post(`/vendor/coupons/${id}/toggle-status`),
  getCouponAnalytics: (params?: any) => api.get('/vendor/coupons/analytics', { params }),
  exportCoupons: (params?: any) => api.get('/vendor/coupons/export', { 
    params,
    responseType: 'blob' 
  }),
}