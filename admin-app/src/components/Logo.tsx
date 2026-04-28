import React from 'react';

interface LogoProps {
  size?: 'sm' | 'md' | 'lg';
  className?: string;
}

export const Logo: React.FC<LogoProps> = ({ size = 'md', className = '' }) => {
  const sizes = {
    sm: 'w-8 h-8',
    md: 'w-12 h-12',
    lg: 'w-16 h-16',
  };

  return (
    <div className={`${sizes[size]} ${className}`}>
      <svg viewBox="0 0 100 100" fill="none" xmlns="http://www.w3.org/2000/svg">
        {/* Circle background */}
        <circle cx="50" cy="50" r="48" fill="#2563eb" stroke="#1d4ed8" strokeWidth="2"/>
        
        {/* Dollar sign */}
        <path
          d="M50 20 L50 80 M35 30 Q35 25 40 25 L60 25 Q65 25 65 30 Q65 35 60 35 L40 35 M40 45 L60 45 M40 45 Q35 45 35 50 Q35 55 40 55 L60 55 Q65 55 65 60 Q65 65 60 65 L40 65 Q35 65 35 70"
          stroke="white"
          strokeWidth="4"
          strokeLinecap="round"
          strokeLinejoin="round"
          fill="none"
        />
        
        {/* Handshake elements - simplified hands */}
        <path
          d="M20 50 L30 45 L35 50 L30 55 Z"
          fill="white"
          opacity="0.8"
        />
        <path
          d="M80 50 L70 45 L65 50 L70 55 Z"
          fill="white"
          opacity="0.8"
        />
      </svg>
    </div>
  );
};

export default Logo;
