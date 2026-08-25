import { Gavel, Package, Trophy } from 'lucide-react';
import { index as auctionsIndex } from '@/routes/admin/auctions';
import { index as productsIndex } from '@/routes/admin/products';
import { index as wonItemsIndex } from '@/routes/won-items';
import type { NavItem, Permission } from '@/types';

export type NavConfigItem = NavItem & {
    /** Shown only to accounts holding this permission. */
    permission?: Permission;
    /** Hidden from accounts holding this permission. */
    unlessPermission?: Permission;
};

/**
 * Order matters: the first entry a role can see is where `/dashboard` sends it.
 */
export const mainNavItems: NavConfigItem[] = [
    {
        title: 'Auctions',
        href: auctionsIndex(),
        icon: Gavel,
        permission: 'manage-auctions',
    },
    {
        title: 'Products',
        href: productsIndex(),
        icon: Package,
        permission: 'manage-products',
    },
    {
        title: 'My Wins',
        href: wonItemsIndex(),
        icon: Trophy,
        unlessPermission: 'manage-auctions',
    },
];
