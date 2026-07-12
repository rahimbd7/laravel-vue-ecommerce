// import api from '@/api/api'

// export const adminApi = {
//   // ===================== DASHBOARD =====================
//   getDashboard: () => api.get('/admin/dashboard'),
//   getRevenue: () => api.get('/admin/dashboard/revenue'),
//   getUsers: () => api.get('/admin/dashboard/users'),
//   getVendors: () => api.get('/admin/dashboard/vendors'),
//   getOrders: () => api.get('/admin/dashboard/orders'),
//   getCommissions: () => api.get('/admin/dashboard/commissions'),
//   getActivities: (limit = 20) => api.get('/admin/dashboard/activities', { params: { limit } }),
//   getTopVendors: (limit = 10) => api.get('/admin/dashboard/top-vendors', { params: { limit } }),
//   clearCache: () => api.post('/admin/dashboard/clear-cache'),
// }

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
}