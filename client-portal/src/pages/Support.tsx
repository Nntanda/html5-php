import React from 'react';

export const Support: React.FC = () => {
  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <h1 className="text-2xl sm:text-3xl font-bold text-gray-800">Help & Support</h1>
      </div>

      <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
        {/* Contact Information */}
        <div className="bg-white rounded-lg shadow-md p-6">
          <div className="flex items-center gap-3 mb-4">
            <svg
              className="w-8 h-8 text-green-600"
              fill="none"
              stroke="currentColor"
              viewBox="0 0 24 24"
            >
              <path
                strokeLinecap="round"
                strokeLinejoin="round"
                strokeWidth={2}
                d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"
              />
            </svg>
            <h2 className="text-xl font-semibold text-gray-800">Contact Us</h2>
          </div>
          <div className="space-y-3">
            <div>
              <p className="text-sm text-gray-600">Email</p>
              <p className="text-gray-800 font-medium">support@kitovuhospital.org</p>
            </div>
            <div>
              <p className="text-sm text-gray-600">Phone</p>
              <p className="text-gray-800 font-medium">+256 700 000 000</p>
            </div>
            <div>
              <p className="text-sm text-gray-600">Office Hours</p>
              <p className="text-gray-800 font-medium">Mon - Fri: 8:00 AM - 5:00 PM</p>
            </div>
          </div>
        </div>

        {/* FAQs */}
        <div className="bg-white rounded-lg shadow-md p-6">
          <div className="flex items-center gap-3 mb-4">
            <svg
              className="w-8 h-8 text-green-600"
              fill="none"
              stroke="currentColor"
              viewBox="0 0 24 24"
            >
              <path
                strokeLinecap="round"
                strokeLinejoin="round"
                strokeWidth={2}
                d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
              />
            </svg>
            <h2 className="text-xl font-semibold text-gray-800">FAQs</h2>
          </div>
          <div className="space-y-3">
            <div>
              <p className="text-sm font-medium text-gray-800">How do I apply for a loan?</p>
              <p className="text-sm text-gray-600">Navigate to "Apply for Loan" in the sidebar.</p>
            </div>
            <div>
              <p className="text-sm font-medium text-gray-800">How do I check my savings balance?</p>
              <p className="text-sm text-gray-600">Go to "My Savings" to view your balance.</p>
            </div>
            <div>
              <p className="text-sm font-medium text-gray-800">How do I update my profile?</p>
              <p className="text-sm text-gray-600">Click on "My Profile" to edit your information.</p>
            </div>
          </div>
        </div>

        {/* Quick Links */}
        <div className="bg-white rounded-lg shadow-md p-6">
          <div className="flex items-center gap-3 mb-4">
            <svg
              className="w-8 h-8 text-green-600"
              fill="none"
              stroke="currentColor"
              viewBox="0 0 24 24"
            >
              <path
                strokeLinecap="round"
                strokeLinejoin="round"
                strokeWidth={2}
                d="M13 10V3L4 14h7v7l9-11h-7z"
              />
            </svg>
            <h2 className="text-xl font-semibold text-gray-800">Quick Links</h2>
          </div>
          <div className="space-y-2">
            <a href="#" className="block text-green-600 hover:text-green-700 text-sm">
              → Scheme Bylaws
            </a>
            <a href="#" className="block text-green-600 hover:text-green-700 text-sm">
              → Loan Terms & Conditions
            </a>
            <a href="#" className="block text-green-600 hover:text-green-700 text-sm">
              → Privacy Policy
            </a>
            <a href="#" className="block text-green-600 hover:text-green-700 text-sm">
              → Terms of Service
            </a>
          </div>
        </div>

        {/* Submit a Ticket */}
        <div className="bg-white rounded-lg shadow-md p-6">
          <div className="flex items-center gap-3 mb-4">
            <svg
              className="w-8 h-8 text-green-600"
              fill="none"
              stroke="currentColor"
              viewBox="0 0 24 24"
            >
              <path
                strokeLinecap="round"
                strokeLinejoin="round"
                strokeWidth={2}
                d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z"
              />
            </svg>
            <h2 className="text-xl font-semibold text-gray-800">Submit a Ticket</h2>
          </div>
          <p className="text-sm text-gray-600 mb-4">
            Need help with something specific? Submit a support ticket and we'll get back to you.
          </p>
          <button className="w-full bg-green-600 text-white py-2 px-4 rounded-lg hover:bg-green-700 transition-colors">
            Create Support Ticket
          </button>
        </div>
      </div>
    </div>
  );
};

export default Support;
