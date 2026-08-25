import { Badge } from '@/components/ui/badge';
import { formatMoney } from '@/lib/money';
import type { RoomItem } from '@/types';

type LotListProps = {
    items: RoomItem[];
    currentId?: number | null;
    showResults?: boolean;
};

export const LotList = ({
    items,
    currentId = null,
    showResults = false,
}: LotListProps) => (
    <div className="rounded-xl border border-sidebar-border/70 dark:border-sidebar-border">
        <h3 className="border-b border-sidebar-border/40 p-4 font-medium">
            Lots ({items.length})
        </h3>

        <ul className="divide-y divide-sidebar-border/40">
            {items.map((item) => (
                <li
                    key={item.id}
                    className={`flex items-center justify-between gap-3 p-3 text-sm ${
                        item.id === currentId
                            ? 'font-medium'
                            : 'text-muted-foreground'
                    }`}
                >
                    <span>
                        {item.position}. {item.name}
                    </span>

                    <span className="flex items-center gap-3">
                        {showResults && item.sold_price && (
                            <span className="text-muted-foreground">
                                {formatMoney(item.sold_price)}
                                {item.winner_name
                                    ? ` · ${item.winner_name}`
                                    : ''}
                            </span>
                        )}

                        <Badge variant="secondary" className="capitalize">
                            {item.status.replace('_', ' ')}
                        </Badge>
                    </span>
                </li>
            ))}
        </ul>
    </div>
);
