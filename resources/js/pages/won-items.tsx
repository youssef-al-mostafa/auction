import { Head, Link } from '@inertiajs/react';
import { Trophy } from 'lucide-react';
import { WonItemRow } from '@/components/app/won-item-row';
import { Button } from '@/components/ui/button';
import {
    Empty,
    EmptyContent,
    EmptyDescription,
    EmptyHeader,
    EmptyMedia,
    EmptyTitle,
} from '@/components/ui/empty';
import { ItemGroup } from '@/components/ui/item';
import { usePayForWin } from '@/hooks/use-pay-for-win';
import { browse } from '@/routes';
import { index as wonItemsIndex } from '@/routes/won-items';
import type { WonItem } from '@/types';

type WonItemsProps = {
    items: WonItem[];
};

const WonItems = ({ items }: WonItemsProps) => {
    const { payFor, payingId } = usePayForWin();

    return (
        <>
            <Head title="My Wins" />

            <div className="flex flex-col gap-6 p-4">
                <div className="space-y-1">
                    <h1 className="text-2xl font-semibold tracking-tight">
                        My Wins
                    </h1>
                    <p className="text-sm text-muted-foreground">
                        Lots you won under the hammer, and what is still owed on
                        them.
                    </p>
                </div>

                {items.length === 0 ? (
                    <Empty className="border border-dashed">
                        <EmptyHeader>
                            <EmptyMedia variant="icon">
                                <Trophy />
                            </EmptyMedia>
                            <EmptyTitle>No wins yet</EmptyTitle>
                            <EmptyDescription>
                                Win a lot in a live auction and it will appear
                                here with two hours to complete payment.
                            </EmptyDescription>
                        </EmptyHeader>
                        <EmptyContent>
                            <Button asChild variant="outline">
                                <Link href={browse()}>Browse auctions</Link>
                            </Button>
                        </EmptyContent>
                    </Empty>
                ) : (
                    <ItemGroup className="gap-4">
                        {items.map((item) => (
                            <WonItemRow
                                key={item.id}
                                item={item}
                                onPay={() => payFor(item.id)}
                                paying={payingId === item.id}
                            />
                        ))}
                    </ItemGroup>
                )}
            </div>
        </>
    );
};

WonItems.layout = {
    breadcrumbs: [
        {
            title: 'My Wins',
            href: wonItemsIndex(),
        },
    ],
};

export default WonItems;
