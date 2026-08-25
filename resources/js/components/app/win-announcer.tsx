import { usePage } from '@inertiajs/react';
import { useEcho } from '@laravel/echo-react';
import { useState } from 'react';
import { WinModal } from '@/components/app/win-modal';
import { usePayForWin } from '@/hooks/use-pay-for-win';
import type { WonItem } from '@/types';

type AuctionWonPayload = {
    win: WonItem;
    server_time: string;
};

const DISMISSED_KEY = 'auction.dismissed-wins';

const dismissedIds = (): number[] => {
    try {
        const raw = window.sessionStorage.getItem(DISMISSED_KEY);

        return raw ? (JSON.parse(raw) as number[]) : [];
    } catch {
        return [];
    }
};

const rememberDismissed = (itemId: number): void => {
    try {
        window.sessionStorage.setItem(
            DISMISSED_KEY,
            JSON.stringify([...dismissedIds(), itemId]),
        );
    } catch {
        // A browser refusing session storage just means the modal can reappear.
    }
};

const WinListener = ({
    userId,
    pendingWin,
}: {
    userId: number;
    pendingWin: WonItem | null;
}) => {
    const [announced, setAnnounced] = useState<WonItem | null>(null);
    const [dismissed, setDismissed] = useState<number[]>(dismissedIds);
    const { payFor, payingId } = usePayForWin();

    useEcho<AuctionWonPayload>(
        `user.${userId}`,
        '.auction.won',
        (payload) => setAnnounced(payload.win),
        [userId],
    );

    const win = announced ?? pendingWin;

    if (!win || dismissed.includes(win.id)) {
        return null;
    }

    const close = () => {
        rememberDismissed(win.id);
        setDismissed((existing) => [...existing, win.id]);
        setAnnounced(null);
    };

    return (
        <WinModal
            win={win}
            open
            onOpenChange={(open) => {
                if (!open) {
                    close();
                }
            }}
            onPay={() => payFor(win.id)}
            paying={payingId === win.id}
        />
    );
};

export const WinAnnouncer = () => {
    const { auth, pendingWin } = usePage().props;

    if (!auth.user) {
        return null;
    }

    return <WinListener userId={auth.user.id} pendingWin={pendingWin} />;
};
