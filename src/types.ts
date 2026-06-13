export type UserRole = 'admin' | 'customer';

export interface User {
  id: string;
  email: string;
  role: UserRole;
  name: string;
}

export interface PasswordEntry {
  id: string;
  appName: string;
  loginUrl: string;
  username: string;
  encryptedPassword: string;
  userId: string;
  isFavorite: boolean;
  logoUrl?: string;
}

export interface SharedLink {
  id: string;
  token: string;
  customerEmail: string;
  expiresAt: string;
  isOneTime: boolean;
  isUsed: boolean;
  sharedItemIds: string[];
  createdBy: string;
}

export interface AuthState {
  user: User | null;
  isAuthenticated: boolean;
  isLoading: boolean;
}
