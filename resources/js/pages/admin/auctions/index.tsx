import { Head, Link, router } from '@inertiajs/react';
import {
    ListOrdered,
    MessagesSquare,
    Pencil,
    Plus,
    Radio,
    Trash2,
} from 'lucide-react';
import { Pagination } from '@/components/core/pagination';
import Heading from '@/components/heading';
import {
    AlertDialog,
    AlertDialogAction,
    AlertDialogCancel,
    AlertDialogContent,
    AlertDialogDescription,
    AlertDialogFooter,
    AlertDialogHeader,
    AlertDialogTitle,
    AlertDialogTrigger,
} from '@/components/ui/alert-dialog';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import {
    chat,
    create,
    destroy,
    edit,
    index as auctionsIndex,
    live,
} from '@/routes/admin/auctions';
import { index as auctionItems } from '@/routes/admin/auctions/items';
import type { AuctionListItem, AuctionStatus, PaginationProps } from '@/types';

type AuctionsIndexProps = {
    auctions: PaginationProps<AuctionListItem>;
};

const statusVariant: Record<
    AuctionStatus,
    'default' | 'secondary' | 'outline' | 'destructive'
> = {
    draft: 'outline',
    scheduled: 'secondary',
    running: 'default',
    ended: 'destructive',
};

const AuctionsIndex = ({ auctions }: AuctionsIndexProps) => (
    <>
        <Head title="Auctions" />

        <div className="space-y-6 p-4">
            <div className="flex items-start justify-between gap-4">
                <Heading
                    variant="small"
                    title="Auctions"
                    description="Ongoing and live auction events"
                />

                <Button asChild>
                    <Link href={create()}>
                        <Plus />
                        New auction
                    </Link>
                </Button>
            </div>

            <div className="rounded-xl border border-sidebar-border/70 dark:border-sidebar-border">
                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead>Title</TableHead>
                            <TableHead className="w-28">Type</TableHead>
                            <TableHead className="w-32">Status</TableHead>
                            <TableHead className="w-44">Starts</TableHead>
                            <TableHead className="w-20">Items</TableHead>
                            <TableHead className="w-72" />
                        </TableRow>
                    </TableHeader>

                    <TableBody>
                        {auctions.data.length === 0 && (
                            <TableRow>
                                <TableCell
                                    colSpan={6}
                                    className="py-10 text-center text-muted-foreground"
                                >
                                    No auctions yet.
                                </TableCell>
                            </TableRow>
                        )}

                        {auctions.data.map((auction) => (
                            <TableRow key={auction.id}>
                                <TableCell className="font-medium">
                                    {auction.title}
                                </TableCell>

                                <TableCell className="capitalize">
                                    {auction.type}
                                </TableCell>

                                <TableCell>
                                    <Badge
                                        variant={statusVariant[auction.status]}
                                        className="capitalize"
                                    >
                                        {auction.status}
                                    </Badge>
                                </TableCell>

                                <TableCell>{auction.starts_at}</TableCell>

                                <TableCell>{auction.items_count}</TableCell>

                                <TableCell>
                                    <div className="flex justify-end gap-1">
                                        {auction.type === 'live' && (
                                            <Button
                                                variant="ghost"
                                                size="sm"
                                                asChild
                                            >
                                                <Link href={live(auction.slug)}>
                                                    <Radio />
                                                    Live
                                                </Link>
                                            </Button>
                                        )}

                                        <Button
                                            variant="ghost"
                                            size="sm"
                                            asChild
                                        >
                                            <Link href={chat(auction.slug)}>
                                                <MessagesSquare />
                                                Chat
                                            </Link>
                                        </Button>

                                        <Button
                                            variant="ghost"
                                            size="sm"
                                            asChild
                                        >
                                            <Link
                                                href={auctionItems(
                                                    auction.slug,
                                                )}
                                            >
                                                <ListOrdered />
                                                Items
                                            </Link>
                                        </Button>

                                        <Button
                                            variant="ghost"
                                            size="sm"
                                            asChild
                                        >
                                            <Link href={edit(auction.slug)}>
                                                <Pencil />
                                                Edit
                                            </Link>
                                        </Button>

                                        <AlertDialog>
                                            <AlertDialogTrigger asChild>
                                                <Button
                                                    variant="ghost"
                                                    size="sm"
                                                    className="text-destructive hover:text-destructive"
                                                >
                                                    <Trash2 />
                                                    Delete
                                                </Button>
                                            </AlertDialogTrigger>

                                            <AlertDialogContent>
                                                <AlertDialogHeader>
                                                    <AlertDialogTitle>
                                                        Delete {auction.title}?
                                                    </AlertDialogTitle>
                                                    <AlertDialogDescription>
                                                        This also removes its{' '}
                                                        {auction.items_count}{' '}
                                                        item(s) from the
                                                        auction. The products
                                                        themselves are kept.
                                                    </AlertDialogDescription>
                                                </AlertDialogHeader>

                                                <AlertDialogFooter>
                                                    <AlertDialogCancel>
                                                        Cancel
                                                    </AlertDialogCancel>
                                                    <AlertDialogAction
                                                        onClick={() =>
                                                            router.delete(
                                                                destroy(
                                                                    auction.slug,
                                                                ).url,
                                                                {
                                                                    preserveScroll: true,
                                                                },
                                                            )
                                                        }
                                                    >
                                                        Delete
                                                    </AlertDialogAction>
                                                </AlertDialogFooter>
                                            </AlertDialogContent>
                                        </AlertDialog>
                                    </div>
                                </TableCell>
                            </TableRow>
                        ))}
                    </TableBody>
                </Table>
            </div>

            <Pagination links={auctions.links} />
        </div>
    </>
);

AuctionsIndex.layout = {
    breadcrumbs: [
        {
            title: 'Auctions',
            href: auctionsIndex(),
        },
    ],
};

export default AuctionsIndex;
