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
}