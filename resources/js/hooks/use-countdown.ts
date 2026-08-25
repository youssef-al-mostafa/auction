import { useEffect, useState } from 'react';

export type Remaining = {
    hours: string;
    minutes: string;
    seconds: string;
    totalSeconds: number;
    expired: boolean;
};

const pad = (value: number): string => String(value).padStart(2, '0');

const remainingFrom = (target: string | null, now: number): Remaining => {
    if (!target) {
        return {
            hours: '00',
            minutes: '00',
            seconds: '00',
            totalSeconds: 0,
            expired: true,
        };
    }

    const totalSeconds = Math.max(
        0,
        Math.floor((new Date(target).getTime() - now) / 1000),
    );

    return {
        hours: pad(Math.floor(totalSeconds / 3600)),
        minutes: pad(Math.floor((totalSeconds % 3600) / 60)),
        seconds: pad(totalSeconds % 60),
        totalSeconds,
        expired: totalSeconds === 0,
    };
};

/**
 * Ticks down toward an absolute server timestamp rather than counting a local
 * duration, so a hard refresh resumes at the correct remaining time and pausing
 * JavaScript cannot stall the clock.
 *
 * The current time is held in state and passed into the calculation. Deriving it
 * from Date.now() during render instead would let the React Compiler memoise the
 * result against an unchanged target, and the display would freeze.
 */
export const useCountdown = (target: string | null): Remaining => {
    const [now, setNow] = useState(() => Date.now());

    useEffect(() => {
        if (!target) {
            return;
        }

        const timer = window.setInterval(() => setNow(Date.now()), 250);

        return () => window.clearInterval(timer);
    }, [target]);

    return remainingFrom(target, now);
};
