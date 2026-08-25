export const formatDeadline = (timestamp: string | null): string => {
    if (!timestamp) {
        return '—';
    }

    const parsed = new Date(timestamp);

    if (Number.isNaN(parsed.getTime())) {
        return '—';
    }

    return parsed.toLocaleString(undefined, {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
};

export const formatTime = (timestamp: string | null): string => {
    if (!timestamp) {
        return '';
    }

    const parsed = new Date(timestamp);

    if (Number.isNaN(parsed.getTime())) {
        return '';
    }

    return parsed.toLocaleTimeString(undefined, {
        hour: '2-digit',
        minute: '2-digit',
    });
};

/**
 * Measures the window from the two server timestamps, so the client never
 * hardcodes a length the server owns.
 */
export const windowRemainingPercent = (
    openedAt: string | null,
    closesAt: string | null,
    remainingSeconds: number,
): number => {
    if (!openedAt || !closesAt) {
        return 0;
    }

    const span =
        (new Date(closesAt).getTime() - new Date(openedAt).getTime()) / 1000;

    if (!Number.isFinite(span) || span <= 0) {
        return 0;
    }

    return Math.min(100, Math.max(0, (remainingSeconds / span) * 100));
};
