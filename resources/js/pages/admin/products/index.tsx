import { Head, Link, router } from '@inertiajs/react';
import { ImageOff, Pencil, Plus, Trash2 } from 'lucide-react';
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
    create,
    destroy,
    edit,
    index as productsIndex,
} from '@/routes/admin/products';
import type { PaginationProps, ProductListItem, ProductStatus } from '@/types';

type ProductsIndexProps = {
    products: PaginationProps<ProductListItem>;
};

const productStatusLabel: Record<ProductStatus, string> = {
    available: 'Available',
    in_auction: 'In auction',
    sold: 'Sold',
};

const productStatusVariant: Record<
    ProductStatus,
    'default' | 'secondary' | 'outline'
> = {
    available: 'outline',
    in_auction: 'default',
    sold: 'secondary',
};

const ProductsIndex = ({ products }: ProductsIndexProps) => (
    <>
        <Head title="Products" />

        <div className="space-y-6 p-4">
            <div className="flex items-start justify-between gap-4">
                <Heading
                    variant="small"
                    title="Products"
                    description="Items available to put up for auction"
                />

                <Button asChild>
                    <Link href={create()}>
                        <Plus />
                        New product
                    </Link>
                </Button>
            </div>

            <div className="rounded-xl border border-sidebar-border/70 dark:border-sidebar-border">
                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead className="w-20">Image</TableHead>
                            <TableHead>Name</TableHead>
                            <TableHead>Description</TableHead>
                            <TableHead className="w-36">Status</TableHead>
                            <TableHead className="w-56">Auction</TableHead>
                            <TableHead className="w-40" />
                        </TableRow>
                    </TableHeader>

                    <TableBody>
                        {products.data.length === 0 && (
                            <TableRow>
                                <TableCell
                                    colSpan={6}
                                    className="py-10 text-center text-muted-foreground"
                                >
                                    No products yet. Create one to start
                                    building an auction.
                                </TableCell>
                            </TableRow>
                        )}

                        {products.data.map((product) => (
                            <TableRow key={product.id}>
                                <TableCell>
                                    {product.thumb ? (
                                        <img
                                            src={product.thumb}
                                            alt={product.name}
                                            className="size-12 rounded-md border border-sidebar-border/70 object-cover dark:border-sidebar-border"
                                        />
                                    ) : (
                                        <div className="flex size-12 items-center justify-center rounded-md border border-dashed border-sidebar-border/70 text-muted-foreground dark:border-sidebar-border">
                                            <ImageOff className="size-4" />
                                        </div>
                                    )}
                                </TableCell>

                                <TableCell className="font-medium">
                                    {product.name}
                                </TableCell>

                                <TableCell className="max-w-md truncate">
                                    {product.description ?? '—'}
                                </TableCell>

                                <TableCell>
                                    <Badge
                                        variant={
                                            productStatusVariant[product.status]
                                        }
                                    >
                                        {productStatusLabel[product.status]}
                                    </Badge>
                                </TableCell>

                                <TableCell>{product.auction ?? '—'}</TableCell>

                                <TableCell>
                                    <div className="flex justify-end gap-1">
                                        <Button
                                            variant="ghost"
                                            size="sm"
                                            asChild
                                        >
                                            <Link href={edit(product.id)}>
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
                                                        Delete {product.name}?
                                                    </AlertDialogTitle>
                                                    <AlertDialogDescription>
                                                        This cannot be undone. A
                                                        product already used in
                                                        an auction cannot be
                                                        deleted.
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
                                                                    product.id,
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

            <Pagination links={products.links} />
        </div>
    </>
);

ProductsIndex.layout = {
    breadcrumbs: [
        {
            title: 'Products',
            href: productsIndex(),
        },
    ],
};

export default ProductsIndex;
