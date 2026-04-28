import React, { useState, useEffect } from 'react';
import { apiClient } from '../api/client';

interface ConfigValue {
  key: string;
  value: string;
  description: string;
}

export const Config: React.FC = () => {
  const [config, setConfig] = useState<Record<string, ConfigValue>>({});
  const [formData, setFormData] = useState<Record<string, string>>({});
  const [isLoading, setIsLoading] = useState(false);
  const [isSaving, setIsSaving] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [success, setSuccess] = useState<string | null>(null);
  const [validationErrors, setValidationErrors] = useState<Record<string, string>>({});

  useEffect(() => {
    fetchConfig();
  }, []);

  const fetchConfig = async () => {
    setIsLoading(true);
    setError(null);
    try {
      const response = await apiClient.get<Record<string, ConfigValue>>('/config');
      setConfig(response.data);
      const initialFormData: Record<string, string> = {};
      Object.entries(response.data).forEach(([key, val]) => {
        initialFormData[key] = (val as ConfigValue).value;
      });
      setFormData(initialFormData);
    } catch (err: any) {
      setError(err.response?.data?.message || 'Failed to fetch configuration');
    } finally {
      setIsLoading(false);
    }
  };

  const validateForm = (): boolean => {
    const errors: Record<string, string> = {};
    
    Object.entries(formData).forEach(([key, value]) => {
      if (!value || (typeof value === 'string' && value.trim() === '')) {
        errors[key] = 'This field is required';
      } else if (key.includes('rate') || key.includes('limit') || key.includes('fee')) {
        const numValue = parseFloat(value as string);
        if (isNaN(numValue) || numValue < 0) {
          errors[key] = 'Must be a valid positive number';
        }
      }
    });

    setValidationErrors(errors);
    return Object.keys(errors).length === 0;
  };

  const handleInputChange = (key: string, value: string) => {
    setFormData((prev: Record<string, string>) => ({
      ...prev,
      [key]: value
    }));
    if (validationErrors[key]) {
      setValidationErrors((prev: Record<string, string>) => ({
        ...prev,
        [key]: ''
      }));
    }
  };

  const handleSave = async () => {
    if (!validateForm()) {
      return;
    }

    setIsSaving(true);
    setError(null);
    setSuccess(null);
    try {
      await apiClient.put('/config', formData);
      setSuccess('Configuration updated successfully');
      setTimeout(() => setSuccess(null), 3000);
    } catch (err: any) {
      setError(err.response?.data?.message || 'Failed to save configuration');
    } finally {
      setIsSaving(false);
    }
  };

  const handleReset = () => {
    const initialFormData: Record<string, string> = {};
    Object.entries(config).forEach(([key, val]) => {
      initialFormData[key] = (val as ConfigValue).value;
    });
    setFormData(initialFormData);
    setValidationErrors({});
  };

  return (
    <div>
      <h1 className="text-3xl font-bold text-gray-800 mb-6">System Configuration</h1>

      {error && (
        <div className="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
          {error}
        </div>
      )}

      {success && (
        <div className="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
          {success}
        </div>
      )}

      <div className="bg-white rounded-lg shadow p-6">
        {isLoading ? (
          <div className="text-center text-gray-500 py-8">Loading configuration...</div>
        ) : Object.keys(config).length === 0 ? (
          <div className="text-center text-gray-500 py-8">No configuration found</div>
        ) : (
          <>
            <div className="space-y-6">
              {Object.entries(config).map(([key, configItem]) => (
                <div key={key} className="border-b pb-6 last:border-b-0">
                  <label className="block text-sm font-medium text-gray-700 mb-2">
                    {(configItem as ConfigValue).description || key}
                  </label>
                  <p className="text-xs text-gray-500 mb-2">Key: {key}</p>
                  <input
                    type="text"
                    value={formData[key] || ''}
                    onChange={(e: React.ChangeEvent<HTMLInputElement>) => handleInputChange(key, e.target.value)}
                    className={`w-full px-3 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 ${
                      validationErrors[key] ? 'border-red-500' : 'border-gray-300'
                    }`}
                    placeholder={`Enter ${key}`}
                  />
                  {validationErrors[key] && (
                    <p className="text-red-500 text-sm mt-1">{validationErrors[key]}</p>
                  )}
                </div>
              ))}
            </div>

            <div className="flex gap-3 mt-8">
              <button
                onClick={handleSave}
                disabled={isSaving}
                className="px-6 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 disabled:opacity-50"
              >
                {isSaving ? 'Saving...' : 'Save Changes'}
              </button>
              <button
                onClick={handleReset}
                disabled={isSaving}
                className="px-6 py-2 bg-gray-300 text-gray-700 rounded-md hover:bg-gray-400 disabled:opacity-50"
              >
                Reset
              </button>
            </div>
          </>
        )}
      </div>
    </div>
  );
};
