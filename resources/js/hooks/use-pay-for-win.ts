import { router } from '@inertiajs/react';
import { useState } from 'react';
import { pay } from '@/routes/won-items';

export const usePayForWin = (onPaid?: (itemId: number) => void) => {
    const [payingId, setPayingId] = useState<number | null>(null);

    const payFor = (itemId: number): void => {
        setPayingId(itemId);

        router.post(
            pay(itemId).url,
            {},
            {
                preserveScroll: true,
                onSuccess: () => onPaid?.(itemId),
                onFinish: () => setPayingId(null),
            },
        );
    };

    return { payFor, payingId };
};
