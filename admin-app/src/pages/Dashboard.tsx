import { useAuthStore } from '../store/authStore';

export const Dashboard: React.FC = () => {
  const { user } = useAuthStore();

  return (
    <div>
      <h1 className="text-3xl font-bold text-gray-800 mb-6">Dashboard</h1>

      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <div className="bg-white rounded-lg shadow p-6">
          <h3 className="text-gray-600 text-sm font-medium">Total Members</h3>
          <p className="text-3xl font-bold text-gray-800 mt-2">0</p>
        </div>

        <div className="bg-white rounded-lg shadow p-6">
          <h3 className="text-gray-600 text-sm font-medium">Total Savings</h3>
          <p className="text-3xl font-bold text-gray-800 mt-2">$0</p>
        </div>

        <div className="bg-white rounded-lg shadow p-6">
          <h3 className="text-gray-600 text-sm font-medium">Active Loans</h3>
          <p className="text-3xl font-bold text-gray-800 mt-2">0</p>
        </div>

        <div className="bg-white rounded-lg shadow p-6">
          <h3 className="text-gray-600 text-sm font-medium">Pending Approvals</h3>
          <p className="text-3xl font-bold text-gray-800 mt-2">0</p>
        </div>
      </div>

      <div className="mt-8 bg-white rounded-lg shadow p-6">
        <h2 className="text-xl font-semibold text-gray-800 mb-4">
          Welcome to Kitovu Hospital Staff Saving Scheme Admin
        </h2>
        <p className="text-gray-600">
          You are logged in as <strong>{user?.name}</strong> ({user?.role})
        </p>
        <p className="text-gray-600 mt-2">
          Use the sidebar to navigate to different sections of the application.
        </p>
      </div>
    </div>
  );
};
