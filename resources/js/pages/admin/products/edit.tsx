import { Head } from '@inertiajs/react';
import ProductController from '@/actions/App/Http/Controllers/Admin/ProductController';
import { ProductForm } from '@/components/app/product-form';
import Heading from '@/components/heading';
import { edit, index as productsIndex } from '@/routes/admin/products';
import type { ProductFormValues } from '@/types';

type ProductsEditProps = {
    product: ProductFormValues;
};

const ProductsEdit = ({ product }: ProductsEditProps) => (
    <>
        <Head title={`Edit ${product.name}`} />

        <div className="space-y-6 p-4">
            <Heading
                variant="small"
                title="Edit product"
                description={product.name}
            />

            <ProductForm
                action={ProductController.update.form(product.id)}
                product={product}
                submitLabel="Save changes"
            />
        </div>
    </>
);

ProductsEdit.layout = ({ product }: ProductsEditProps) => ({
    breadcrumbs: [
        {
            title: 'Products',
            href: productsIndex(),
        },
        {
            title: product.name,
            href: edit(product.id),
        },
    ],
});

export default ProductsEdit;
