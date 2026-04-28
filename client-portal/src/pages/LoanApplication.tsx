import React from 'react';

export const LoanApplication: React.FC = () => {
  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <h1 className="text-2xl sm:text-3xl font-bold text-gray-800">Apply for Loan</h1>
      </div>

      <div className="bg-white rounded-lg shadow-md p-6">
        <div className="text-center py-12">
          <svg
            className="w-16 h-16 mx-auto text-green-600 mb-4"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
          >
            <path
              strokeLinecap="round"
              strokeLinejoin="round"
              strokeWidth={2}
              d="M12 4v16m8-8H4"
            />
          </svg>
          <h2 className="text-xl font-semibold text-gray-800 mb-2">Loan Application</h2>
          <p className="text-gray-600">
            Apply for a new loan. This feature is coming soon.
          </p>
        </div>
      </div>
    </div>
  );
};

export default LoanApplication;
