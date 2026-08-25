import { Head, router } from '@inertiajs/react';
import { SearchX } from 'lucide-react';
import { useState } from 'react';
import { AuctionItemCard } from '@/components/app/auction-item-card';
import { Pagination } from '@/components/core/pagination';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { browse } from '@/routes';
import type { PaginationProps, StorefrontItem } from '@/types';

type BrowseProps = {
    items: PaginationProps<StorefrontItem>;
    filters: {
        search: string;
        per_page: number;
    };
    perPageOptions: number[];
};

const Browse = ({ items, filters, perPageOptions }: BrowseProps) => {
    const [term, setTerm] = useState(filters.search);

    const apply = (overrides: Record<string, string | number>) => {
        router.get(
            browse().url,
            {
                search: term,
                per_page: filters.per_page,
                ...overrides,
            },
            { preserveState: true, preserveScroll: true, replace: true },
        );
    };

    return (
        <>
            <Head title="All lots" />

            <div className="mx-auto max-w-7xl space-y-6 px-4 py-10">
                <div>
                    <h1 className="text-2xl font-bold tracking-tight">
                        All lots
                    </h1>
                    <p className="text-sm text-muted-foreground">
                        {items.total} lot{items.total === 1 ? '' : 's'} open for
                        bidding
                    </p>
                </div>

                <div className="flex flex-wrap items-center gap-3 rounded-xl border bg-card p-3">
                    <form
                        onSubmit={(event) => {
                            event.preventDefault();
                            apply({ search: term });
                        }}
                        className="flex min-w-56 flex-1 items-center gap-2"
                    >
                        <Input
                            value={term}
                            onChange={(event) => setTerm(event.target.value)}
                            placeholder="Search for products"
                            aria-label="Search for products"
                        />
                        <Button type="submit" variant="secondary">
                            Search
                        </Button>
                    </form>

                    <div className="flex items-center gap-2">
                        <span className="text-sm text-muted-foreground">
                            Items per page
                        </span>

                        <Select
                            value={String(filters.per_page)}
                            onValueChange={(value) =>
                                apply({ per_page: value })
                            }
                        >
                            <SelectTrigger className="w-20">
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                {perPageOptions.map((option) => (
                                    <SelectItem
                                        key={option}
                                        value={String(option)}
                                    >
                                        {option}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                    </div>
                </div>

                {items.data.length === 0 ? (
                    <div className="flex flex-col items-center gap-3 rounded-xl border border-dashed py-20 text-center">
                        <SearchX className="size-8 text-muted-foreground" />
                        <p className="font-medium">No lots match that search</p>
                        <p className="text-sm text-muted-foreground">
                            Try a different term, or clear the search to see
                            everything.
                        </p>
                        {filters.search && (
                            <Button
                                variant="secondary"
                                onClick={() => {
                                    setTerm('');
                                    apply({ search: '' });
                                }}
                            >
                                Clear search
                            </Button>
                        )}
                    </div>
                ) : (
                    <div className="grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
                        {items.data.map((item) => (
                            <AuctionItemCard key={item.id} item={item} />
                        ))}
                    </div>
                )}

                <Pagination links={items.links} />
            </div>
        </>
    );
};

export default Browse;
