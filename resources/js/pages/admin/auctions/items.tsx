import { Form, Head, router } from '@inertiajs/react';
import { ArrowDown, ArrowUp, ImageOff, Plus, Trash2 } from 'lucide-react';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { formatMoney } from '@/lib/money';
import { index as auctionsIndex } from '@/routes/admin/auctions';
import { destroy, move, store } from '@/routes/admin/auctions/items';
import type {
    AuctionSummary,
    ManagedAuctionItem,
    ProductOption,
} from '@/types';

type AuctionItemsProps = {
    auction: AuctionSummary;
    items: ManagedAuctionItem[];
    availableProducts: ProductOption[];
};

type AttachFormData = {
    product_id: string;
    starting_price: string;
};

const AuctionItems = ({
    auction,
    items,
    availableProducts,
}: AuctionItemsProps) => (
    <>
        <Head title={`Items — ${auction.title}`} />

        <div className="space-y-6 p-4">
            <Heading
                variant="small"
                title={auction.title}
                description={`${auction.type} auction · starts ${auction.starts_at}`}
            />

            <div className="rounded-xl border border-sidebar-border/70 p-6 dark:border-sidebar-border">
                <h2 className="mb-4 font-medium">Add an item</h2>

                {availableProducts.length === 0 ? (
                    <p className="text-sm text-muted-foreground">
                        Every product is already listed in an auction that has
                        not ended. Create a new product first.
                    </p>
                ) : (
                    <Form<AttachFormData>
                        {...store.form(auction.slug)}
                        options={{ preserveScroll: true }}
                        resetOnSuccess
                        className="flex flex-wrap items-end gap-4"
                    >
                        {({ processing, errors }) => (
                            <>
                                <div className="grid min-w-64 flex-1 gap-2">
                                    <Label htmlFor="product_id">Product</Label>

                                    <Select name="product_id">
                                        <SelectTrigger id="product_id">
                                            <SelectValue placeholder="Choose a product" />
                                        </SelectTrigger>

                                        <SelectContent>
                                            {availableProducts.map(
                                                (product) => (
                                                    <SelectItem
                                                        key={product.id}
                                                        value={String(
                                                            product.id,
                                                        )}
                                                    >
                                                        {product.name}
                                                    </SelectItem>
                                                ),
                                            )}
                                        </SelectContent>
                                    </Select>

                                    <InputError message={errors.product_id} />
                                </div>

                                <div className="grid w-48 gap-2">
                                    <Label htmlFor="starting_price">
                                        Starting price
                                    </Label>

                                    <Input
                                        id="starting_price"
                                        name="starting_price"
                                        type="number"
                                        step="0.01"
                                        min="0.01"
                                        placeholder="250.00"
                                        required
                                    />

                                    <InputError
                                        message={errors.starting_price}
                                    />
                                </div>

                                <Button disabled={processing}>
                                    <Plus />
                                    Add item
                                </Button>
                            </>
                        )}
                    </Form>
                )}
            </div>

            <div className="rounded-xl border border-sidebar-border/70 dark:border-sidebar-border">
                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead className="w-16">#</TableHead>
                            <TableHead className="w-20">Image</TableHead>
                            <TableHead>Product</TableHead>
                            <TableHead className="w-36">
                                Starting price
                            </TableHead>
                            <TableHead className="w-32">Status</TableHead>
                            <TableHead className="w-40" />
                        </TableRow>
                    </TableHeader>

                    <TableBody>
                        {items.length === 0 && (
                            <TableRow>
                                <TableCell
                                    colSpan={6}
                                    className="py-10 text-center text-muted-foreground"
                                >
                                    No items yet. Add one above — this order is
                                    the order they go under the hammer.
                                </TableCell>
                            </TableRow>
                        )}

                        {items.map((item, index) => (
                            <TableRow key={item.id}>
                                <TableCell className="text-muted-foreground">
                                    {index + 1}
                                </TableCell>

                                <TableCell>
                                    {item.thumb ? (
                                        <img
                                            src={item.thumb}
                                            alt={item.name}
                                            className="size-12 rounded-md border border-sidebar-border/70 object-cover dark:border-sidebar-border"
                                        />
                                    ) : (
                                        <div className="flex size-12 items-center justify-center rounded-md border border-dashed border-sidebar-border/70 text-muted-foreground dark:border-sidebar-border">
                                            <ImageOff className="size-4" />
                                        </div>
                                    )}
                                </TableCell>

                                <TableCell className="font-medium">
                                    {item.name}
                                </TableCell>

                                <TableCell>
                                    {formatMoney(item.starting_price)}
                                </TableCell>

                                <TableCell>
                                    <Badge
                                        variant="secondary"
                                        className="capitalize"
                                    >
                                        {item.status.replace('_', ' ')}
                                    </Badge>
                                </TableCell>

                                <TableCell>
                                    <div className="flex justify-end gap-1">
                                        <Button
                                            variant="ghost"
                                            size="icon-sm"
                                            disabled={index === 0}
                                            onClick={() =>
                                                router.patch(
                                                    move([
                                                        auction.slug,
                                                        item.id,
                                                    ]).url,
                                                    { direction: 'up' },
                                                    { preserveScroll: true },
                                                )
                                            }
                                        >
                                            <ArrowUp />
                                        </Button>

                                        <Button
                                            variant="ghost"
                                            size="icon-sm"
                                            disabled={
                                                index === items.length - 1
                                            }
                                            onClick={() =>
                                                router.patch(
                                                    move([
                                                        auction.slug,
                                                        item.id,
                                                    ]).url,
                                                    { direction: 'down' },
                                                    { preserveScroll: true },
                                                )
                                            }
                                        >
                                            <ArrowDown />
                                        </Button>

                                        <Button
                                            variant="ghost"
                                            size="sm"
                                            className="text-destructive hover:text-destructive"
                                            onClick={() =>
                                                router.delete(
                                                    destroy([
                                                        auction.slug,
                                                        item.id,
                                                    ]).url,
                                                    { preserveScroll: true },
                                                )
                                            }
                                        >
                                            <Trash2 />
                                            Remove
                                        </Button>
                                    </div>
                                </TableCell>
                            </TableRow>
                        ))}
                    </TableBody>
                </Table>
            </div>
        </div>
    </>
);

AuctionItems.layout = ({ auction }: AuctionItemsProps) => ({
    breadcrumbs: [
        {
            title: 'Auctions',
            href: auctionsIndex(),
        },
        {
            title: auction.title,
            href: '#',
        },
    ],
});

export default AuctionItems;
