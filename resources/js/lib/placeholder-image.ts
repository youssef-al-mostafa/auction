// TODO: delete this once products carry real images (products.images jsonb).
// Every call site takes a product name and renders whatever this returns as an
// <img src>, so swapping in a real URL is a one-line change per call site.

const hueFromName = (name: string): number => {
    let hash = 0;

    for (let index = 0; index < name.length; index++) {
        hash = (hash << 5) - hash + name.charCodeAt(index);
        hash |= 0;
    }

    return Math.abs(hash) % 360;
};

const initialsFromName = (name: string): string =>
    name
        .replace(/[^a-zA-Z0-9 ]/g, ' ')
        .split(' ')
        .filter(Boolean)
        .slice(0, 2)
        .map((word) => word[0]!.toUpperCase())
        .join('');

/**
 * A deterministic gradient tile standing in for a product photo. The same name
 * always yields the same artwork, so the grid looks stable between visits.
 */
export const placeholderImage = (name: string): string => {
    const hue = hueFromName(name);
    const initials = initialsFromName(name);

    const svg = `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 400 400">
        <defs>
            <linearGradient id="g" x1="0" y1="0" x2="1" y2="1">
                <stop offset="0%" stop-color="hsl(${hue} 70% 62%)"/>
                <stop offset="100%" stop-color="hsl(${(hue + 45) % 360} 68% 42%)"/>
            </linearGradient>
        </defs>
        <rect width="400" height="400" fill="url(#g)"/>
        <text x="50%" y="50%" dy="0.35em" text-anchor="middle"
            font-family="system-ui, sans-serif" font-size="132" font-weight="600"
            fill="rgba(255,255,255,0.92)">${initials}</text>
    </svg>`;

    return `data:image/svg+xml;utf8,${encodeURIComponent(svg)}`;
};
