export type AuctionType = 'ongoing' | 'live';

export type AuctionStatus = 'draft' | 'scheduled' | 'running' | 'ended';

export type AuctionItemStatus =
    'pending' | 'active' | 'counting_down' | 'sold' | 'unsold';

export type PaymentStatus = 'pending' | 'paid' | 'expired';

export type ProductStatus = 'available' | 'in_auction' | 'sold';

export type Auction = {
    id: number;
    title: string;
    slug: string;
    type: AuctionType;
    status: AuctionStatus;
    starts_at: string;
    ends_at: string | null;
    auction_items_count?: number;
};

export type AuctionItem = {
    id: number;
    auction_id: number;
    product_id: number;
    position: number;
    starting_price: string;
    status: AuctionItemStatus;
    auction?: Auction;
    product?: Product;
    // TODO: populate from the bids table once it exists — the storefront cards
    // fall back to starting_price while this is undefined.
    current_bid?: string | null;
};

/** Flattened item + product, as the public storefront cards consume it. */
export type StorefrontItem = {
    id: number;
    name: string;
    description: string | null;
    image: string | null;
    status: AuctionItemStatus;
    starting_price: string;
    current_bid: string | null;
    auction: {
        title: string;
        slug: string;
        type: AuctionType;
        status: AuctionStatus;
        starts_at: string | null;
        ends_at: string | null;
    };
};

export type Product = {
    id: number;
    name: string;
    description: string | null;
    current_auction_item?: AuctionItem | null;
    created_at: string;
    updated_at: string;
};
