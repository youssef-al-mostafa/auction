import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Item,
    ItemActions,
    ItemContent,
    ItemDescription,
    ItemFooter,
    ItemMedia,
    ItemTitle,
} from '@/components/ui/item';
import { Progress } from '@/components/ui/progress';
import { useCountdown } from '@/hooks/use-countdown';
import { formatDeadline, windowRemainingPercent } from '@/lib/datetime';
import { formatMoney } from '@/lib/money';
import { placeholderImage } from '@/lib/placeholder-image';
import type { PaymentStatus, WonItem } from '@/types';

const statusLabels: Record<PaymentStatus, string> = {
    pending: 'Awaiting payment',
    paid: 'Paid',
    expired: 'Deadline passed',
};

const statusVariants: Record<
    PaymentStatus,
    'default' | 'secondary' | 'destructive'
> = {
    pending: 'default',
    paid: 'secondary',
    expired: 'destructive',
};

type WonItemRowProps = {
    item: WonItem;
    onPay: () => void;
    paying: boolean;
};

export const WonItemRow = ({ item, onPay, paying }: WonItemRowProps) => {
    const { hours, minutes, seconds, totalSeconds, expired } = useCountdown(
        item.paid_at ? null : item.payment_deadline,
    );

    const status = item.payment_status;
    const settled = status === 'paid';
    const payable = status === 'pending' && !expired;

    return (
        <Item variant="outline" className="flex-wrap">
            <ItemMedia variant="image" className="size-16">
                <img
                    src={item.image ?? placeholderImage(item.name)}
                    alt={item.name}
                />
            </ItemMedia>

            <ItemContent>
                <ItemTitle className="text-base">{item.name}</ItemTitle>
                <ItemDescription>{item.auction_title}</ItemDescription>
                {status && (
                    <Badge
                        variant={statusVariants[status]}
                        className="mt-1 w-fit"
                    >
                        {statusLabels[status]}
                    </Badge>
                )}
            </ItemContent>

            <ItemContent className="items-end text-right">
                <span className="text-xs text-muted-foreground">
                    Winning bid
                </span>
                <span className="text-xl font-bold text-primary">
                    {formatMoney(item.sold_price)}
                </span>
            </ItemContent>

            <ItemActions>
                <Button disabled={!payable || paying} onClick={onPay}>
                    {settled ? 'Paid' : payable ? 'Complete Payment' : 'Closed'}
                </Button>
            </ItemActions>

            <ItemFooter className="flex-col items-stretch gap-2">
                <div className="flex items-center justify-between text-xs text-muted-foreground">
                    <span>{settled ? 'Paid on' : 'Payment deadline'}</span>
                    <span className="font-medium text-foreground">
                        {formatDeadline(
                            settled ? item.paid_at : item.payment_deadline,
                        )}
                    </span>
                </div>

                {payable && (
                    <>
                        <Progress
                            value={windowRemainingPercent(
                                item.closed_at,
                                item.payment_deadline,
                                totalSeconds,
                            )}
                        />
                        <p className="text-right text-xs text-muted-foreground tabular-nums">
                            {hours}:{minutes}:{seconds} left to pay
                        </p>
                    </>
                )}
            </ItemFooter>
        </Item>
    );
};
