import { Head } from '@inertiajs/react';
import ProductController from '@/actions/App/Http/Controllers/Admin/ProductController';
import { ProductForm } from '@/components/app/product-form';
import Heading from '@/components/heading';
import { create, index as productsIndex } from '@/routes/admin/products';

const ProductsCreate = () => (
    <>
        <Head title="New product" />

        <div className="space-y-6 p-4">
            <Heading
                variant="small"
                title="New product"
                description="Add an item you can later attach to an auction"
            />

            <ProductForm
                action={ProductController.store.form()}
                submitLabel="Create product"
            />
        </div>
    </>
);

ProductsCreate.layout = {
    breadcrumbs: [
        {
            title: 'Products',
            href: productsIndex(),
        },
        {
            title: 'New product',
            href: create(),
        },
    ],
};

export default ProductsCreate;
