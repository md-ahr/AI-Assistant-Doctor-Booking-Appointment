import type { ImgHTMLAttributes } from 'react';

export default function AppLogoIcon({
    alt = 'lucidense.ai',
    className,
    ...props
}: ImgHTMLAttributes<HTMLImageElement>) {
    return (
        <img
            src="/logo-lucidense.png"
            alt={alt}
            className={className}
            {...props}
        />
    );
}
