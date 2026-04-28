import axios, { AxiosError } from 'axios';

export interface ApiError {
  message: string;
  errors?: Record<string, string[]>;
  statusCode?: number;
}

/**
 * Extract error message from API response
 */
export const getErrorMessage = (error: unknown): string => {
  if (axios.isAxiosError(error)) {
    const axiosError = error as AxiosError<ApiError>;
    
    // Check if response has error message
    if (axiosError.response?.data?.message) {
      return axiosError.response.data.message;
    }
    
    // Check for validation errors
    if (axiosError.response?.data?.errors) {
      const errors = axiosError.response.data.errors;
      const firstError = Object.values(errors)[0];
      return Array.isArray(firstError) ? firstError[0] : String(firstError);
    }
    
    // Network errors
    if (axiosError.code === 'ERR_NETWORK') {
      return 'Network error. Please check your connection.';
    }
    
    // Timeout errors
    if (axiosError.code === 'ECONNABORTED') {
      return 'Request timeout. Please try again.';
    }
    
    // Status code based messages
    if (axiosError.response?.status === 401) {
      return 'Session expired. Please login again.';
    }
    
    if (axiosError.response?.status === 403) {
      return 'You do not have permission to perform this action.';
    }
    
    if (axiosError.response?.status === 404) {
      return 'Resource not found.';
    }
    
    if (axiosError.response?.status === 500) {
      return 'Server error. Please try again later.';
    }
    
    // Generic axios error
    return axiosError.message || 'An unexpected error occurred';
  }
  
  // Non-axios errors
  if (error instanceof Error) {
    return error.message;
  }
  
  return 'An unexpected error occurred';
};

/**
 * Extract validation errors from API response
 */
export const getValidationErrors = (error: unknown): Record<string, string> | null => {
  if (axios.isAxiosError(error)) {
    const axiosError = error as AxiosError<ApiError>;
    
    if (axiosError.response?.data?.errors) {
      const errors = axiosError.response.data.errors;
      const formattedErrors: Record<string, string> = {};
      
      Object.keys(errors).forEach((key) => {
        const errorArray = errors[key];
        formattedErrors[key] = Array.isArray(errorArray) ? errorArray[0] : String(errorArray);
      });
      
      return formattedErrors;
    }
  }
  
  return null;
};

/**
 * Check if error is authentication error
 */
export const isAuthError = (error: unknown): boolean => {
  if (axios.isAxiosError(error)) {
    return error.response?.status === 401;
  }
  return false;
};

/**
 * Check if error is authorization error
 */
export const isAuthorizationError = (error: unknown): boolean => {
  if (axios.isAxiosError(error)) {
    return error.response?.status === 403;
  }
  return false;
};

/**
 * Check if error is validation error
 */
export const isValidationError = (error: unknown): boolean => {
  if (axios.isAxiosError(error)) {
    return error.response?.status === 422;
  }
  return false;
};

/**
 * Format error for display in toast/alert
 */
export const formatErrorForDisplay = (error: unknown): {
  title: string;
  message: string;
  type: 'error' | 'warning';
} => {
  const message = getErrorMessage(error);
  
  if (isAuthError(error)) {
    return {
      title: 'Authentication Error',
      message,
      type: 'warning',
    };
  }
  
  if (isAuthorizationError(error)) {
    return {
      title: 'Permission Denied',
      message,
      type: 'warning',
    };
  }
  
  if (isValidationError(error)) {
    return {
      title: 'Validation Error',
      message,
      type: 'error',
    };
  }
  
  return {
    title: 'Error',
    message,
    type: 'error',
  };
};
