import { Head } from '@inertiajs/react';
import AuctionController from '@/actions/App/Http/Controllers/Admin/AuctionController';
import { AuctionForm } from '@/components/app/auction-form';
import Heading from '@/components/heading';
import { create, index as auctionsIndex } from '@/routes/admin/auctions';

type AuctionsCreateProps = {
    types: string[];
    statuses: string[];
};

const AuctionsCreate = ({ types, statuses }: AuctionsCreateProps) => (
    <>
        <Head title="New auction" />

        <div className="space-y-6 p-4">
            <Heading
                variant="small"
                title="New auction"
                description="Set up an ongoing or live auction event"
            />

            <AuctionForm
                action={AuctionController.store.form()}
                types={types}
                statuses={statuses}
                submitLabel="Create auction"
            />
        </div>
    </>
);

AuctionsCreate.layout = {
    breadcrumbs: [
        {
            title: 'Auctions',
            href: auctionsIndex(),
        },
        {
            title: 'New auction',
            href: create(),
        },
    ],
};

export default AuctionsCreate;
