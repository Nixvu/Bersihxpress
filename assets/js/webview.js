// Inject WebView styles
document.addEventListener('DOMContentLoaded', function() {
    // Add WebView specific meta tags
    const meta = document.createElement('meta');
    meta.name = 'viewport';
    meta.content = 'width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no';
    document.head.appendChild(meta);

    // Add WebView specific stylesheet
    const link = document.createElement('link');
    link.rel = 'stylesheet';
    link.href = '/assets/css/webview-styles.css';
    document.head.appendChild(link);

    // Adjust font sizes based on screen width
    const adjustFontSizes = () => {
        const width = window.innerWidth;
        const root = document.documentElement;
        
        if (width <= 320) { // iPhone SE size
            root.style.setProperty('--base-font-size', '12px');
        } else if (width <= 375) { // iPhone X/11 Pro size
            root.style.setProperty('--base-font-size', '13px');
        } else if (width <= 414) { // iPhone 11 Pro Max size
            root.style.setProperty('--base-font-size', '14px');
        } else {
            root.style.setProperty('--base-font-size', '16px');
        }
    };

    // Initial adjustment
    adjustFontSizes();

    // Adjust on resize
    window.addEventListener('resize', adjustFontSizes);
});