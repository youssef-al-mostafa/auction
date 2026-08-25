import { Head } from '@inertiajs/react';
import AuctionController from '@/actions/App/Http/Controllers/Admin/AuctionController';
import { AuctionForm } from '@/components/app/auction-form';
import Heading from '@/components/heading';
import { edit, index as auctionsIndex } from '@/routes/admin/auctions';
import type { AuctionFormValues } from '@/types';

type AuctionsEditProps = {
    auction: AuctionFormValues;
    types: string[];
    statuses: string[];
};

const AuctionsEdit = ({ auction, types, statuses }: AuctionsEditProps) => (
    <>
        <Head title={`Edit ${auction.title}`} />

        <div className="space-y-6 p-4">
            <Heading
                variant="small"
                title="Edit auction"
                description={auction.title}
            />

            <AuctionForm
                action={AuctionController.update.form(auction.slug)}
                auction={auction}
                types={types}
                statuses={statuses}
                submitLabel="Save changes"
            />
        </div>
    </>
);

AuctionsEdit.layout = ({ auction }: AuctionsEditProps) => ({
    breadcrumbs: [
        {
            title: 'Auctions',
            href: auctionsIndex(),
        },
        {
            title: auction.title,
            href: edit(auction.slug),
        },
    ],
});

export default AuctionsEdit;
