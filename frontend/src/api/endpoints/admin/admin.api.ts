import api from '@/api/api'

export const adminApi = {
  // ===================== DASHBOARD =====================
  getDashboard: () => api.get('/admin/dashboard'),
  getRevenue: () => api.get('/admin/dashboard/revenue'),
  getUsers: () => api.get('/admin/dashboard/users'),
  getVendors: () => api.get('/admin/dashboard/vendors'),
  getOrders: () => api.get('/admin/dashboard/orders'),
  getCommissions: () => api.get('/admin/dashboard/commissions'),
  getActivities: (limit = 20) => api.get('/admin/dashboard/activities', { params: { limit } }),
  getTopVendors: (limit = 10) => api.get('/admin/dashboard/top-vendors', { params: { limit } }),
  clearCache: () => api.post('/admin/dashboard/clear-cache'),

  // ===================== USER MANAGEMENT =====================
  // GET /admin/users - List all users (paginated)
  getUsersList: (params?: any) => api.get('/admin/users', { params }),
  
  // POST /admin/users - Create new user
  createUser: (data: any) => api.post('/admin/users', data),
  
  // GET /admin/users/{id} - Get single user
  getUser: (id: string) => api.get(`/admin/users/${id}`),
  
  // PUT /admin/users/{id} - Update user
  updateUser: (id: string, data: any) => api.put(`/admin/users/${id}`, data),
  
  // DELETE /admin/users/{id} - Delete user (soft delete)
  deleteUser: (id: string) => api.delete(`/admin/users/${id}`),
  
  // POST /admin/users/{id}/restore - Restore soft-deleted user
  restoreUser: (id: string) => api.post(`/admin/users/${id}/restore`),
  
  // GET /admin/users/{id}/orders - Get user orders
  getUserOrders: (id: string, params?: any) => api.get(`/admin/users/${id}/orders`, { params }),
  
  // GET /admin/users/{id}/activities - Get user activities
  getUserActivities: (id: string, params?: any) => api.get(`/admin/users/${id}/activities`, { params }),
  
  // GET /admin/users/{id}/stats - Get user statistics
  getUserStats: (id: string) => api.get(`/admin/users/${id}/stats`),
  
  // GET /admin/users/{id}/activities/export - Export user activities as CSV
  exportUserActivities: (id: string, params?: any) => api.get(`/admin/users/${id}/activities/export`, { 
    params,
    responseType: 'blob' 
  }),
  
  // POST /admin/users/bulk-delete - Bulk delete users
  bulkDeleteUsers: (data: { user_ids: number[] }) => api.post('/admin/users/bulk-delete', data),
  
  // POST /admin/users/bulk-status - Bulk update user status
  bulkUpdateStatus: (data: { user_ids: number[], status: string }) => api.post('/admin/users/bulk-status', data),

  getOrdersList: (params?: any) => api.get('/admin/orders', { params }),
  getOrderStats: () => api.get('/admin/orders/stats'),
  getOrder: (id: string) => api.get(`/admin/orders/${id}`),
  getOrderTimeline: (id: string) => api.get(`/admin/orders/${id}/timeline`),
  getVendorOrders: (vendorId: string, params?: any) => api.get(`/admin/orders/vendor/${vendorId}`, { params }),
  updateOrderStatus: (id: string, data: { 
    status: string, 
    tracking_number?: string, 
    carrier?: string, 
    notes?: string 
  }) => api.put(`/admin/orders/${id}/status`, data),
  cancelOrder: (id: string, data: { reason: string }) => api.post(`/admin/orders/${id}/cancel`, data),
  exportOrders: (params?: any) => api.get('/admin/orders/export', { 
    params,
    responseType: 'blob' 
  }),


  getProductsList: (params?: any) => api.get('/admin/dashboard/products', { params }),
getProductStats: () => api.get('/admin/dashboard/products/stats'),
getProduct: (id: string) => api.get(`/admin/dashboard/products/${id}`),
updateProduct: (id: string, data: any) => api.put(`/admin/dashboard/products/${id}`, data),
deleteProduct: (id: string) => api.delete(`/admin/dashboard/products/${id}`),
restoreProduct: (id: string) => api.post(`/admin/dashboard/products/${id}/restore`),
toggleProductVisibility: (id: string) => api.put(`/admin/dashboard/products/${id}/toggle-visibility`),
bulkProductAction: (data: { product_ids: number[], action: string }) => api.post('/admin/dashboard/products/bulk-action', data),
getVendorProducts: (vendorId: string, params?: any) => api.get(`/admin/dashboard/products/vendor/${vendorId}`, { params }),
exportProducts: (params?: any) => api.get('/admin/dashboard/products/export', { 
    params,
    responseType: 'blob' 
}),

getCategoriesList: (params?: any) => api.get('/categories', { params }),
getAllCategoriesByAdmin: () => api.get('/admin/categories'),
getCategoriesListByAdmin: (id: string) => api.get(`/admin/categories/${id}`),
createCategory: (data: any) => api.post('/admin/categories', data),
updateCategory: (id: string, data: any) => api.patch(`admin/categories/${id}`, data),
deleteCategory: (id: string) => api.delete(`/admin/categories/${id}`),
exportCategories: (params?: any) => api.get('/admin/categories/export', { 
    params,
    responseType: 'blob' 
}),

 // Coupons
  getCoupons: (params?: any) => api.get('/admin/coupons', { params }),
  getCoupon: (id: string) => api.get(`/admin/coupons/${id}`),
  createCoupon: (data: any) => api.post('/admin/coupons', data),
  updateCoupon: (id: string, data: any) => api.put(`/admin/coupons/${id}`, data),
  deleteCoupon: (id: string) => api.delete(`/admin/coupons/${id}`),
  toggleCouponStatus: (id: string) => api.post(`/admin/coupons/${id}/toggle-status`),
  getCouponAnalytics: (params?: any) => api.get('/admin/coupons/analytics', { params }),
   exportCoupons: (params?: any) => api.get('/admin/coupons/export', { 
    params,
    responseType: 'blob' 
  }),

}

