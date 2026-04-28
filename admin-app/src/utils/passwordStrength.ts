export interface PasswordStrength {
  score: number; // 0-4
  label: string;
  color: string;
  suggestions: string[];
}

export const calculatePasswordStrength = (password: string): PasswordStrength => {
  let score = 0;
  const suggestions: string[] = [];

  if (!password) {
    return {
      score: 0,
      label: 'Too weak',
      color: 'text-red-500',
      suggestions: ['Password is required'],
    };
  }

  // Length check
  if (password.length >= 8) score++;
  else suggestions.push('Use at least 8 characters');

  if (password.length >= 12) score++;

  // Character variety checks
  const hasLowercase = /[a-z]/.test(password);
  const hasUppercase = /[A-Z]/.test(password);
  const hasNumber = /\d/.test(password);
  const hasSpecial = /[@$!%*?&#]/.test(password);

  if (hasLowercase && hasUppercase) {
    score++;
  } else {
    suggestions.push('Use both uppercase and lowercase letters');
  }

  if (hasNumber) {
    score++;
  } else {
    suggestions.push('Include at least one number');
  }

  if (hasSpecial) {
    score++;
  } else {
    suggestions.push('Include at least one special character (@$!%*?&#)');
  }

  // Determine label and color
  let label = 'Too weak';
  let color = 'text-red-500';

  if (score >= 5) {
    label = 'Strong';
    color = 'text-green-500';
  } else if (score >= 4) {
    label = 'Good';
    color = 'text-blue-500';
  } else if (score >= 3) {
    label = 'Fair';
    color = 'text-yellow-500';
  }

  return { score, label, color, suggestions };
};

export const getPasswordStrengthBarColor = (score: number): string => {
  if (score >= 4) return 'bg-green-500';
  if (score >= 3) return 'bg-blue-500';
  if (score >= 2) return 'bg-yellow-500';
  return 'bg-red-500';
};
