import { Link } from '@inertiajs/react';
import { Award } from 'lucide-react';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import {
    Item,
    ItemContent,
    ItemDescription,
    ItemMedia,
    ItemTitle,
} from '@/components/ui/item';
import { Progress } from '@/components/ui/progress';
import { Separator } from '@/components/ui/separator';
import { useCountdown } from '@/hooks/use-countdown';
import { formatDeadline, windowRemainingPercent } from '@/lib/datetime';
import { formatMoney } from '@/lib/money';
import { placeholderImage } from '@/lib/placeholder-image';
import { index as wonItemsIndex } from '@/routes/won-items';
import type { WonItem } from '@/types';

type WinModalProps = {
    win: WonItem;
    open: boolean;
    onOpenChange: (open: boolean) => void;
    onPay: () => void;
    paying: boolean;
};

export const WinModal = ({
    win,
    open,
    onOpenChange,
    onPay,
    paying,
}: WinModalProps) => {
    const { hours, minutes, seconds, totalSeconds, expired } = useCountdown(
        win.payment_deadline,
    );

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent className="sm:max-w-md">
                <DialogHeader className="items-center text-center sm:text-center">
                    <div className="mb-2 flex size-14 items-center justify-center rounded-2xl bg-primary/10">
                        <Award className="size-7 text-primary" />
                    </div>
                    <DialogTitle className="text-2xl">
                        Congratulations! You Won!
                    </DialogTitle>
                    <DialogDescription>
                        To secure your item, complete the payment before the
                        deadline. Don&apos;t miss out!
                    </DialogDescription>
                </DialogHeader>

                <Item variant="outline">
                    <ItemMedia variant="image" className="size-14">
                        <img
                            src={win.image ?? placeholderImage(win.name)}
                            alt={win.name}
                        />
                    </ItemMedia>
                    <ItemContent>
                        <ItemTitle>{win.name}</ItemTitle>
                        <ItemDescription>{win.auction_title}</ItemDescription>
                    </ItemContent>
                </Item>

                <Separator />

                <div className="grid grid-cols-2 gap-4 text-center">
                    <div>
                        <p className="text-xs font-medium text-muted-foreground">
                            Winning Bid
                        </p>
                        <p className="mt-1 text-lg font-bold">
                            {formatMoney(win.sold_price)}
                        </p>
                    </div>
                    <div>
                        <p className="text-xs font-medium text-muted-foreground">
                            Payment Deadline
                        </p>
                        <p className="mt-1 text-lg font-bold">
                            {formatDeadline(win.payment_deadline)}
                        </p>
                    </div>
                </div>

                <Progress
                    value={windowRemainingPercent(
                        win.closed_at,
                        win.payment_deadline,
                        totalSeconds,
                    )}
                />

                <DialogFooter className="gap-2 sm:flex-col">
                    <Button
                        className="w-full"
                        disabled={expired || paying}
                        onClick={onPay}
                    >
                        {expired
                            ? 'Payment window closed'
                            : `Complete Payment (${hours}:${minutes}:${seconds})`}
                    </Button>
                    <Button asChild variant="outline" className="w-full">
                        <Link
                            href={wonItemsIndex()}
                            onClick={() => onOpenChange(false)}
                        >
                            View My Winning Bids
                        </Link>
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
};
