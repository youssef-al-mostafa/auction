import type {
    AuctionItemStatus,
    AuctionStatus,
    AuctionType,
    PaymentStatus,
    ProductStatus,
} from './models';

export type ProductImage = {
    id: number;
    url: string;
    name: string;
};

export type ProductListItem = {
    id: number;
    name: string;
    description: string | null;
    thumb: string | null;
    auction: string | null;
    status: ProductStatus;
};

export type ProductFormValues = {
    id: number;
    name: string;
    description: string | null;
    images: ProductImage[];
};

export type ProductOption = {
    id: number;
    name: string;
};

export type AuctionListItem = {
    id: number;
    slug: string;
    title: string;
    type: AuctionType;
    status: AuctionStatus;
    starts_at: string;
    ends_at: string | null;
    items_count: number;
};

export type AuctionFormValues = {
    id: number;
    slug: string;
    title: string;
    type: AuctionType;
    status: AuctionStatus;
    start_date: string;
    start_time: string;
    end_date: string | null;
    end_time: string | null;
};

export type AuctionSummary = {
    id: number;
    slug: string;
    title: string;
    type: AuctionType;
    status: AuctionStatus;
    starts_at: string;
};

export type ManagedAuctionItem = {
    id: number;
    position: number;
    name: string;
    thumb: string | null;
    starting_price: string;
    status: AuctionItemStatus;
};

export type RoomItem = {
    id: number;
    position: number;
    name: string;
    description: string | null;
    image: string | null;
    status: AuctionItemStatus;
    starting_price: string;
    current_bid: string | null;
    current_bidder: string | null;
    current_bidder_id: number | null;
    countdown_ends_at: string | null;
    countdown_seconds: number | null;
    sold_price: string | null;
    winner_name: string | null;
};

export type RoomBid = {
    id: number;
    amount: string;
    bidder: string;
    bidder_id: number;
    placed_at: string;
};

export type WonItem = {
    id: number;
    name: string;
    image: string | null;
    auction_title: string;
    sold_price: string | null;
    closed_at: string | null;
    payment_deadline: string | null;
    paid_at: string | null;
    payment_status: PaymentStatus | null;
};

export type ChatMessage = {
    id: number;
    thread_id: number;
    auction_id: number;
    body: string;
    author: string;
    author_id: number;
    from_admin: boolean;
    sent_at: string;
};

/** The one conversation an auction room shares. */
export type RoomChat = {
    thread_id: number | null;
    messages: ChatMessage[];
};

export type RoomAuction = {
    id: number;
    slug: string;
    title: string;
    type: AuctionType;
    status: AuctionStatus;
    starts_at: string;
};

/** The auction an item detail page belongs to. `ends_at` closes an ongoing lot. */
export type ItemPageAuction = RoomAuction & {
    ends_at: string | null;
};
