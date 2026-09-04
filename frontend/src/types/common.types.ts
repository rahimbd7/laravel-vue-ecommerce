// API Response wrapper
export interface ApiResponse<T = any> {
  status: 'success' | 'error';
  message: string;
  data: T;
  errors?: Record<string, string[]>;
}

// Pagination
export interface PaginatedResponse<T> {
  current_page: number;
  data: T[];
  first_page_url: string;
  from: number;
  last_page: number;
  last_page_url: string;
  links: Array<{
    url: string | null;
    label: string;
    active: boolean;
  }>;
  next_page_url: string | null;
  path: string;
  per_page: number;
  prev_page_url: string | null;
  to: number;
  total: number;
}

// Address
export interface Address {
  address: string;
  city: string;
  state?: string | null;
  postal_code?: string | null;
  country: string;
}

// Metadata
export interface Metadata {
  ip_address?: string;
  user_agent?: string;
}

export const enum UserRole {
  Admin = "admin",
  Vendor = "vendor",
  Customer = "customer",
}