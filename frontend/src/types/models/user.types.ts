// src/types/models/user.types.ts

export interface User {
  id: number;
  uuid: string;
  name: string;
  email: string;
  role: 'admin' | 'vendor' | 'customer';
  email_verified_at: string | null;
  created_at: string;
  updated_at: string;
}

export interface UserProfile {
  id: number;
  user_uuid: string;
  phone: string | null;
  avatar: string | null;
  address: string | null;
  city: string | null;
  state: string | null;
  postal_code: string | null;
  country: string | null;
  date_of_birth: string | null;
}

export interface UserWithProfile extends User {
  profile: UserProfile | null;
}

// Auth
export interface LoginRequest {
  email: string;
  password: string;
}

export interface LoginResponse {
  token_type: string;
  token: string;
  user: User;
}

export interface RegisterRequest {
  name: string;
  email: string;
  password: string;
  password_confirmation?: string;
}